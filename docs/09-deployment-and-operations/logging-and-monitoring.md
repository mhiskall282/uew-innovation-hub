# Logging and Monitoring

> **Audience:** Operators, developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Health checks](health-checks.md), [Audit and monitoring (administration)](../03-administration/audit-and-monitoring.md), [Security monitoring](../08-security/security-monitoring.md), [Logging and diagnostics](../11-troubleshooting/logging-and-diagnostics.md)

Where the Hub writes logs, what the audit log records, how to see failed queue jobs, and how to tell which API version a client uses. The Hub has logging but no monitoring or alerting. Gaps are collected at the end.

## Application logs

Configured in [`config/logging.php`](../../config/logging.php) and driven by environment variables.

| Environment | `LOG_CHANNEL` | `LOG_LEVEL` | Where logs go |
| --- | --- | --- | --- |
| Local (`.env.example`) | `stack` with `LOG_STACK=single` | `debug` | `storage/logs/laravel.log` |
| Render (`render.yaml`) | `stderr` | `warning` | Container stderr, shown in the Render **Logs** tab |
| Vercel (`vercel.json`) | `stderr` | default `debug` | Vercel function logs |

- The `stderr` channel is a Monolog `StreamHandler` writing to `php://stderr`, with `LOG_STDERR_FORMATTER` optional.
- `docker/php.ini` sends PHP errors to `/dev/stderr` with `display_errors=Off`.
- Apache sends its access log to `/dev/stdout` (`CustomLog /dev/stdout combined`) and its error log to `/dev/stderr` (`docker/apache-vhost.conf`). So the Render log mixes access lines, PHP errors and Laravel log lines.
- With `LOG_LEVEL=warning` on Render, `info` and `debug` messages are dropped. Raise it to `info` temporarily when diagnosing a problem, and redeploy or restart, because config is cached at boot.
- Other channels exist in the config but are not enabled: `daily` (`LOG_DAILY_DAYS`, default 14), `slack` (`LOG_SLACK_WEBHOOK_URL`), `papertrail`, `syslog`, `errorlog`, `null`, `emergency`.
- Render's free plan keeps only a limited log history in the dashboard. Check Render's current retention before relying on it.

### Reading logs

| Where | How |
| --- | --- |
| Local | `Get-Content storage\logs\laravel.log -Tail 100 -Wait` (PowerShell), `tail -f storage/logs/laravel.log` (bash), or `php artisan pail` (installed as a dev dependency, also started by `composer dev`) |
| Render | Dashboard > service > **Logs**. Filter by text. |
| Docker | `docker logs -f <container>` |

With `MAIL_MAILER=log`, e-mails (including sign-in and reset codes) are written to the log. On Render's test site this means verification codes appear in the log stream, which is a way to read them but also a reason to treat those logs as sensitive.

### What is logged

Laravel reports unhandled exceptions with `report()`. The API error handler in `bootstrap/app.php` turns exceptions into JSON for `api/*` requests. The Hub adds no custom request logging and no structured (JSON) log format, and no correlation or request ID.

## Audit log

Business actions are recorded in the `activity_logs` table by `app/Services/AuditLogger.php`. Each row stores the action, a description, the subject, the acting user and the IP address taken server-side from the session and request. Staff with the audit permission view it in the workspace, with a filter by area. The administrator view is described in [Audit and monitoring](../03-administration/audit-and-monitoring.md).

| Fact | Detail |
| --- | --- |
| Areas in the filter | 18: `achievement`, `album`, `application`, `auth`, `broadcast`, `grant`, `member`, `message`, `organization`, `page`, `post`, `program`, `project`, `publication`, `report`, `settings`, `template`, `user` (`AuditLogController::AREAS`) |
| Exports are audited | `member.exported` (members CSV), `report.projects_exported` (projects CSV), `application.exported` (applications CSV), `report.exported` (PDF) |
| Broadcast failures | `broadcast.failed`, written by `SendBroadcast::failed()` when a queued broadcast fails. The broadcast status becomes `failed` (`BroadcastStatus::Failed`) instead of staying at `sending`. The error text is truncated to 300 characters. |
| Demo account password changes | `hub:secure-demo-accounts` records each change |
| Retention | Not defined. No command prunes `activity_logs`. |

The audit log is in the database, so it survives redeploys as long as the database does.

## Queue and failed jobs

- Queue driver: `database` (`jobs` table). Failed jobs go to the `failed_jobs` table (`QUEUE_FAILED_DRIVER` default `database-uuids`).
- The worker started by `docker/entrypoint.sh` runs `queue:work --sleep=3 --tries=3 --max-time=3600 --memory=128`. `SendBroadcast` sets `tries = 1`.
- There is no workspace screen for `failed_jobs`. Use artisan from the Render shell or locally:

```bash
php artisan queue:failed
php artisan queue:retry <uuid>
php artisan queue:retry all
php artisan queue:flush        # deletes all failed jobs
```

> **Warning:** `queue:flush` permanently deletes failed job records. Read them with `queue:failed` first.

- The worker's own output goes to the container stdout and stderr.
- A broadcast stuck in `sending` is now marked `failed` and audited. A worker that died silently, or a sleeping free-plan service, still leaves jobs waiting in `jobs`. Check the row count: `php artisan tinker --execute="echo DB::table('jobs')->count();"`.

## Tracking API clients

Every response from `/api/v1`, `/api/v2` and `/api` carries an `API-Version` header, set by [`ApiVersion`](../../app/Http/Middleware/ApiVersion.php). When a version is being retired (`API_V1_DEPRECATED_AT`, `API_V1_SUNSET_AT`), `Deprecation`, `Link rel="deprecation"` and `Sunset` headers are added.

- The header is a response header, so it helps clients and proxies. The application does **not** record which version each client calls. Apache's access log in the Render stream contains the URL (`/api/v1/...` or `/api/v2/...`), so counting v1 requests means searching the log for `/api/v1/` before setting a sunset date.
- Rate limiting is per IP (`api` limiter, `API_RATE_LIMIT_PER_MINUTE`). A `429` appears in the access log and in the `rate_limited` v2 error code.
- Behind Render, client IPs are correct only with `TRUSTED_PROXIES=*` (set in `render.yaml`).

## Monitoring that exists

| Item | Type |
| --- | --- |
| `GET /up` health route | Used by Render and the Docker `HEALTHCHECK` ([Health checks](health-checks.md)) |
| Render dashboard | Deploy status, CPU and memory graphs, log stream, restart events |
| Audit log | Business activity |
| `php artisan pail` | Live log tail in development |

## Gaps

| Gap | Impact | Suggestion (recommended, not implemented) |
| --- | --- | --- |
| No uptime monitor | Downtime on the sleeping free plan or after a crash goes unnoticed | An external HTTP monitor on `/up` and `/api/v2/reference` |
| No alerting | Failed deploys, 500s, queue failures reach nobody | Render notifications to e-mail or Slack, and the Laravel `slack` log channel for `critical` |
| No error tracker | Exceptions exist only as log lines | Sentry or Flare |
| No metrics | No request rate, latency or queue depth history | Platform metrics or an APM |
| No log retention beyond the platform | Render free plan history is short | A log drain (for example Papertrail, config exists) |
| No view of `failed_jobs` in the workspace | Operators need shell access | A staff screen, or a scheduled report |
| No pruning of `activity_logs`, `failed_jobs`, `sessions`, `cache`, `jobs` | Tables grow without limit | A scheduler plus `model:prune`, `queue:prune-failed`, `queue:prune-batches` (there is no scheduler today) |
| `/up` does not check the database | Green health with a dead database | See [Health checks](health-checks.md) |
| No request or correlation ID | Hard to tie a user report to a log line | Add a request ID middleware |
| Mail log channel | `MAIL_MAILER=log` on the test site writes message bodies, including codes, to logs | Configure SMTP before inviting real users |
