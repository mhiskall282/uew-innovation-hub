# Health Checks

> **Audience:** Operators, developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Docker guide](docker-guide.md), [Logging and monitoring](logging-and-monitoring.md), [Deployment problems](../11-troubleshooting/deployment-problems.md), [Rollback procedures](rollback-procedures.md)

The Hub has one health endpoint, `GET /up`, provided by Laravel. Render and the Docker `HEALTHCHECK` both call it. It tells you that the PHP application boots. It does not tell you that the database, queue, storage or mail work.

## The `/up` route

| Item | Value |
| --- | --- |
| Registered in | [`bootstrap/app.php`](../../bootstrap/app.php): `->withRouting(..., health: '/up')` |
| Method and URL | `GET /up` |
| Success | HTTP 200 with a small HTML page |
| Failure | HTTP 500 if the application cannot boot, or if a `DiagnosingHealth` listener throws (the page then shows the exception message, unless debug is on, in which case the exception propagates) |
| Authentication | None |
| Middleware | None. `ApplicationBuilder::buildRoutingCallback` registers it as a bare `Route::get` outside the `web` and `api` groups (`vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php`), so it starts no session, sends no `SecurityHeaders`, and the route itself queries no database. (Provider boot still calls `MailSettings::apply()`, which reads the `settings` table inside a `try` and swallows any error, so a dead database does not fail `/up`.) |
| Also dispatches | `Illuminate\Foundation\Events\DiagnosingHealth`, which an application can listen to in order to add checks. **No listener is registered in this repository** (searched `app/`, `routes/`, `bootstrap/`). |

## What it checks

- PHP starts, Composer autoload works, `bootstrap/app.php` runs.
- Laravel's service providers register (`AppServiceProvider::boot()` runs, including `MailSettings::apply()`, which is wrapped in a `try` and never fails the boot).
- Apache is accepting connections on `$PORT` and routing to `public/index.php`.
- In production, `APP_KEY` must be set because the entrypoint refuses to start without it.

## What it does not check

| Not checked | Consequence |
| --- | --- |
| Database connectivity | The database can be down or expired and `/up` still returns 200 while every page returns 500. The entrypoint's `migrate` step fails earlier in a bad boot, but that only covers boot time. |
| Pending migrations | A schema behind the code still passes. |
| Queue worker | The `queue:work` loop can die, and `/up` stays green. |
| Failed jobs or queue depth | Not visible. |
| Object storage (S3 or R2) | Credentials can be wrong and `/up` stays green. |
| SMTP | Not tested. Use **Site settings > E-mail delivery** to send a test message. |
| Disk space | Not tested. |

Recommended, not implemented: register a listener for `DiagnosingHealth` that runs `DB::select('select 1')`, or add a separate authenticated or token-protected `/health/deep` route. Until then, use a monitor that also fetches a database-backed public URL such as `/api/v2/reference` or the home page.

## Consumers of `/up`

| Consumer | Configuration | Behaviour |
| --- | --- | --- |
| Render | `healthCheckPath: /up` in [`render.yaml`](../../render.yaml) | Render calls the path during a deploy. A new instance receives traffic only after it answers. If it never answers, Render reports the deploy as failed and keeps the previous instance running. Check the Render docs for the exact timeout and retry policy. |
| Docker | `HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3` in the [`Dockerfile`](../../Dockerfile), command `curl -fsS http://127.0.0.1:${PORT}/up` | `docker ps` shows `healthy` or `unhealthy`. Docker alone does not restart an unhealthy container. |
| Playwright | `webServer.url` in the Playwright config | Waits for `/up` before running browser tests. |
| CI | none | The `docker` job builds the image but never starts it. |
| External monitors | none | No uptime service is configured. |

The Docker start period is 60 seconds. A boot that runs many migrations can take longer, so the container may show `unhealthy` briefly. On the Render free plan, the first request after sleeping also takes about a minute, and `/up` answers slowly during that wake-up.

## Manual checks

bash:

```bash
curl -fsS -o /dev/null -w "%{http_code}\n" https://uew-innovation-hub.onrender.com/up
curl -fsS -D - -o /dev/null https://uew-innovation-hub.onrender.com/api/v2/reference | head -20
```

PowerShell:

```powershell
(Invoke-WebRequest -UseBasicParsing https://uew-innovation-hub.onrender.com/up).StatusCode
(Invoke-WebRequest -UseBasicParsing https://uew-innovation-hub.onrender.com/api/v2/reference).Headers['API-Version']
```

The second request exercises the database (reference data) and shows the `API-Version: v2` header from `ApiVersion` middleware.

## A fuller manual checklist after a deploy

1. `GET /up` returns 200.
2. The home page loads and shows content from the database.
3. `GET /api/v2/projects?limit=1` returns JSON with `data` and `meta`.
4. Sign in as staff and open the workspace dashboard.
5. In Render, confirm the last log lines show no `migrate` or `hub:create-admin` error.
6. Queue: from the Render shell run `php artisan queue:failed` (see [Logging and monitoring](logging-and-monitoring.md)). Zero rows means no failed jobs.

## Troubleshooting a failing `/up`

| Symptom | Likely cause | Where to look |
| --- | --- | --- |
| 500 on `/up` right after deploy | Missing or invalid `APP_KEY`, or a config error | Render logs, entrypoint output |
| Container restarts in a loop | Entrypoint step failed under `set -e`: migration error, weak `ADMIN_PASSWORD`, database unreachable | Render logs, [Docker guide](docker-guide.md) boot table |
| Timeout on first request | Free plan waking from sleep | Wait about a minute |
| 200 on `/up` but pages return 500 | Database unreachable or expired | Render database page, `DB_URL` |
