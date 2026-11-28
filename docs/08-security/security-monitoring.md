# Security Monitoring

> **Audience:** operators, Hub Director · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Audit and monitoring](../03-administration/audit-and-monitoring.md), [Logging and monitoring](../09-deployment-and-operations/logging-and-monitoring.md), [Security incident response](security-incident-response.md), [Logging and diagnostics](../11-troubleshooting/logging-and-diagnostics.md)

This page lists what security-relevant events the system records, where they go, what a person can review, and what is missing. The system records events. It does not alert anyone.

## What is recorded

### Audit log (`activity_logs`)

Written by [`AuditLogger::record()`](../../app/Services/AuditLogger.php) with actor, action, description, IP address, JSON properties and subject. The Hub Director (permission `audit.view`) reads it at `/workspace/audit` (`workspace.audit.index`) and can search it.

Security-relevant actions found in code:

| Action | Written by | Meaning |
| --- | --- | --- |
| `auth.login` | `SessionController` | Successful sign-in |
| `auth.login_failed` | `LoginRequest` | Wrong password, unknown e-mail or inactive account (one message, same action) |
| `auth.default_password_refused` | `LoginRequest` | Sign-in with a default password blocked |
| `auth.code_failed` | `VerificationService` | Wrong one-time code |
| `auth.logout` | `SessionController` | Sign-out |
| `auth.password_changed`, `auth.password_reset` | `UserService` | Own change, reset by code or link |
| `auth.sessions_revoked` | `UserService` | Sign out of other devices |
| `member.verified` | `MemberService` | E-mail verified, by code or link |
| `user.created`, `user.updated`, `user.deactivated`, `user.activated`, `user.deleted` | `UserService` | Staff account lifecycle. Role changes carry old and new values |
| `settings.updated` | `SettingsService` | Site and mail settings (password redacted) |
| `album.*`, project, post, programme, application, publication, partner actions | Services | Content changes, with a field-level diff for updates |
| `report.exported` | `Workspace\ReportController::pdf` | Hub report PDF (`pdf_export_downloads_and_is_audited`) |
| `report.projects_exported` | `ReportExportService::projectsCsv` | Project portfolio CSV, with the active filters |
| `application.exported` | `ApplicationService::exportCsv` | Applications CSV for one programme |
| `member.exported` | `MemberController::export` | Member list CSV (contains personal data), with the active filters |
| `broadcast.failed` | `SendBroadcast::failed` | A broadcast job threw and the broadcast moved to `failed` (first 300 characters of the error) |


The three CSV export actions and `broadcast.failed` were added in commit `d1296b1`. `report.exported` already existed. The Audit log page filters by area using the 18 action prefixes in `AuditLogController::AREAS`: achievement, album, application, auth, broadcast, grant, member, message, organization, page, post, program, project, publication, report, settings, template, user.

Not recorded: successful reads of applications or documents, sign-ins by default-password-blocked accounts beyond the refusal entry, and API access (v1 and v2).

Properties of the log: append-only by application code, no edit or delete UI, no database-level protection, no integrity chaining, and no pruning. An attacker with database access can alter it.

### Other tables

- `users.last_login_at`, `users.last_login_ip`: last successful sign-in per account.
- `verification_codes`: issue time, expiry, `attempts`, `consumed_at`. Useful to spot guessing against one account.
- `sessions`: active sessions per user (`user_id`, `ip_address`, `user_agent`, `last_activity`).
- `failed_jobs`: failed queue jobs, including mail.
- `contact_messages`, `application_messages`: user-submitted content.

### Application and server logs

- `LOG_CHANNEL=stderr`, `LOG_LEVEL=warning` on Render ([`render.yaml`](../../render.yaml)). Laravel writes warnings and errors to the container's stderr, which Render shows in the service **Logs** tab. Apache writes access and error logs to `/dev/stdout` and `/dev/stderr` ([`apache-vhost.conf`](../../docker/apache-vhost.conf)); PHP logs to `/dev/stderr` ([`php.ini`](../../docker/php.ini)).
- Locally `LOG_CHANNEL=stack` with `LOG_STACK=single`, written to `storage/logs/laravel.log`. A `daily` channel exists (`LOG_DAILY_DAYS`, default 14).
- Laravel Pail (`php artisan pail`) tails logs in development; it is in `require-dev`.
- Rate-limit hits return HTTP 429 (`ThrottleRequestsException`) and appear in the Apache access log. Login lockouts raise the `Lockout` event; nothing listens to it.
- `MAIL_MAILER=log` (the hosted demo) writes the full e-mail to the log, including one-time codes and signed links. Treat the log stream as sensitive.

## What a person should review

| Frequency | Check | How |
| --- | --- | --- |
| Daily while the demo is public | Failed sign-ins and default-password refusals | `/workspace/audit`, search `auth.login_failed`, `auth.default_password_refused` |
| Weekly | New or changed staff accounts and role changes | Search `user.` |
| Weekly | Sessions of staff accounts | `sessions` table by `user_id` (no UI) |
| Weekly | `failed_jobs` | `php artisan queue:failed` |
| After each deploy | Render logs for `ERROR` and `WARNING` lines | Render dashboard |
| Monthly | `composer audit`, `npm audit`, `scripts/security-scan.ps1` | See [dependency security](dependency-security.md) |

Useful queries (PostgreSQL), run read-only from the Render database shell:

```sql
-- Failed sign-ins in the last 24 hours, grouped by IP
SELECT ip_address, count(*) FROM activity_logs
WHERE action = 'auth.login_failed' AND created_at > now() - interval '24 hours'
GROUP BY ip_address ORDER BY count(*) DESC;

-- Staff changes in the last 7 days
SELECT created_at, action, description FROM activity_logs
WHERE action LIKE 'user.%' AND created_at > now() - interval '7 days' ORDER BY id DESC;
```

## Health checks

`/up` is Laravel's built-in health route (`bootstrap/app.php`). Render uses it as `healthCheckPath`, and the Docker `HEALTHCHECK` calls it every 30 seconds. It confirms the app boots, not that the database, mail or queue work. A queue worker runs in the same container (`entrypoint.sh`), restarted by a shell loop; if it stops, mail queued with `queue()` is delayed while verification codes (sent synchronously) are unaffected.

## Gaps

| Gap | Impact | Recommendation (not implemented) |
| --- | --- | --- |
| No alerting on repeated failed sign-ins, lockouts, role changes or 5xx errors | Incidents are noticed late | Forward Render logs to a log service with alert rules, or add a listener on `Lockout` and a notification for `user.updated` role changes |
| No centralised log storage | Render keeps a limited log window (**Requires confirmation:** retention on the free plan) | Log drain to an external service |
| No scheduled pruning of `activity_logs`, `verification_codes`, `notifications`, `failed_jobs`, expired `sessions` beyond the 2% lottery sweep | Tables grow; old PII persists | Add scheduled `model:prune` or custom commands in `routes/console.php` (currently only `inspire`). No scheduler process is run in the container either |
| Audit log not tamper-evident | Weak evidence after a database compromise | Write-once table permissions or external copy |
| Document reads and API access are not audited (exports are, since `d1296b1`) | Gaps in accountability | Add `AuditLogger` calls for document downloads. API access is public, so request logs are the only record. |
| No uptime monitor | Render free instances sleep when idle | External ping on `/up` |
