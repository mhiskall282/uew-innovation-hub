# Logging and Diagnostics

> **Audience:** Developers, operators · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Logging and monitoring](../09-deployment-and-operations/logging-and-monitoring.md), [Health checks](../09-deployment-and-operations/health-checks.md), [Audit and monitoring (admin)](../03-administration/audit-and-monitoring.md), [Debugging guide](../05-developer-guide/debugging-guide.md)

Where the application writes information, how to read it, and which commands show the real state of a running instance. There are three sources: the Laravel log, the audit log (`activity_logs` table) and the host's platform logs.

## Laravel log

Configured in `config/logging.php` and the environment.

| Variable | Local default (`.env.example`) | Render (`render.yaml`) |
| --- | --- | --- |
| `LOG_CHANNEL` | `stack` | `stderr` |
| `LOG_STACK` | `single` | not set |
| `LOG_LEVEL` | `debug` | `warning` |
| `LOG_DEPRECATIONS_CHANNEL` | `null` | not set |

- Local: one file, `storage/logs/laravel.log`. `LOG_CHANNEL=daily` would add rotation (`LOG_DAILY_DAYS`, default 14).
- Render: logs go to the container's standard error and appear in the Render dashboard under the service's **Logs** tab. PHP's own errors also go to `/dev/stderr` (`docker/php.ini`: `log_errors = On`, `display_errors = Off`).
- At `LOG_LEVEL=warning` Render shows warnings and above. Informational messages are not recorded. Temporarily set `LOG_LEVEL=debug` to investigate, then set it back.

### Read the log

```powershell
Get-Content storage\logs\laravel.log -Tail 100
Get-Content storage\logs\laravel.log -Wait -Tail 20
Select-String -Path storage\logs\laravel.log -Pattern "ERROR" | Select-Object -Last 20
```

```bash
tail -n 100 storage/logs/laravel.log
tail -f storage/logs/laravel.log
grep ERROR storage/logs/laravel.log | tail -n 20
```

Live, readable output with colour and filtering:

```powershell
php artisan pail
```

`composer run dev` starts `pail` alongside the server, queue listener and Vite.

### Messages the application writes on purpose

| Message | Meaning | Source |
| --- | --- | --- |
| "Password change notice could not be queued." | The queue or mail layer failed. The password change itself succeeded | `UserService::notifyPasswordChange` |
| "Staff notification could not be queued." | A new-application or new-enquiry notice failed to queue | `StaffNotifier` |
| Exception from `report($e)` in `SettingsService::all()` | Settings could not be read (database down or not migrated). Defaults are served | `SettingsService` |
| Full e-mail text | `MAIL_MAILER=log` is active | Laravel log mailer |

> **Warning:** With the log mailer, verification codes and reset links are written to the log. The application never logs codes in any other way. Protect the log, and do not paste it into tickets without removing them.

### What not to log or share

Never copy `.env`, `APP_KEY`, SMTP passwords, `DB_URL`, S3 keys or one-time codes into issues, chat or this documentation. A tester password was committed in git history (commit `87df882`). Treat it as public.

## Audit log

In-app, append-only, written by `AuditLogger` for people's actions: sign-ins, failed sign-ins, account changes, decisions, exports of reports, settings changes. Read it under **Audit log** (Hub Director). Details, action list and gaps are in [audit and monitoring](../03-administration/audit-and-monitoring.md).

Use it to answer "who changed this and when". Use the Laravel log to answer "why did the server fail".

Query it directly when the UI is not enough:

```powershell
php artisan tinker --execute="echo App\Models\ActivityLog::count();"
php artisan tinker
```

The first command prints the number of entries. Then, at the interactive prompt, list the latest ten:

```php
App\Models\ActivityLog::latest('id')->take(10)->get(['id','action','description','ip_address','created_at'])->toArray();
```

Avoid `$variables` inside a double-quoted `--execute` string in PowerShell, because PowerShell expands them before PHP sees them. Use the interactive prompt for anything longer.

## Health check

`GET /up` is Laravel's built-in health route (`bootstrap/app.php`: `health: '/up'`). It returns 200 when the framework boots. It does not test the database, the queue or mail. Render uses it as `healthCheckPath`, and the `Dockerfile` has a `HEALTHCHECK` (interval 30 seconds, timeout 5 seconds, start period 60 seconds, 3 retries).

```powershell
Invoke-WebRequest https://uew-innovation-hub.onrender.com/up -UseBasicParsing | Select-Object StatusCode
```

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://uew-innovation-hub.onrender.com/up
```

## Commands that show the real state

| Question | Command |
| --- | --- |
| What environment, drivers and versions? | `php artisan about` |
| What does config say? | `php artisan config:show database`, `config:show mail`, `config:show queue`, `config:show hub` |
| Which routes exist, with middleware? | `php artisan route:list` (add `--path=workspace`) |
| Are migrations applied? | `php artisan migrate:status` |
| What commands exist? | `php artisan list hub` |
| Waiting and failed jobs? | `php artisan queue:failed`, count `jobs` in tinker |
| Is the cache working? | `php artisan cache:clear` (clears it) |
| Clear every cache | `php artisan optimize:clear` |
| Test the database connection | `php artisan tinker --execute="DB::connection()->getPdo(); echo 'ok';"` |
| PHP settings in use | `php -i \| Select-String upload_max_filesize` |
| PHP extensions | `php -m` |

`config:show` prints configuration values, so do not run `config:show mail` in a shared screen share. It can include the SMTP user name and password from the environment.

## Debug mode

`APP_DEBUG=true` shows stack traces in the browser. Use it only locally.

> **Warning:** Do not enable debug on the live site. If you must, enable it briefly, reproduce, then turn it off. Prefer reading the log.

For API requests, `APP_DEBUG=true` puts the exception message in the JSON `message` of a 500.

## Tracing a specific failure

1. Get the time and the user's action.
2. Find the matching audit entry (shows who and what).
3. Find the log entry at that time (shows the error).
4. Reproduce locally with the same role. Test accounts come from `DemoContentSeeder` (the password comes from `SEED_PASSWORD`).
5. Write a failing test in `tests/Feature` and fix.

## Running tests for diagnosis

```powershell
php artisan test --filter=GalleryTest
composer test
```

CI runs the suite on SQLite and PostgreSQL, checks migrations up/down/up and compiles Blade templates, so a failure there often shows the cause. See [testing guide](../05-developer-guide/testing-guide.md).

## E-mail diagnosis

1. **Site settings > System** shows `Mail transport`.
2. **Site settings > E-mail delivery > Send test e-mail** reports the SMTP error text.
3. `scripts/email-flow-test.php` is a developer script for the e-mail flow. **Requires confirmation:** its usage. Read the top of the file before running it.
4. Check the `jobs` and `failed_jobs` tables for queued mail.

## Sign-in diagnosis

`scripts/signin-check.ps1` exists for checking sign-in. **Requires confirmation:** what it needs and what it prints. Read it before running it, and never run it against an account whose password you should not type into a script.

## Windows quirks that hide information

- PowerShell 5.1 shows only the last lines of long output in some hosts. Pipe to `Out-File` with an explicit encoding, or use `Select-Object -First`.
- `>` in Windows PowerShell 5.1 writes UTF-16. Logs and exports saved with `>` then look corrupted in other tools. Use `Out-File -Encoding utf8`.
- Antivirus can quarantine files in the temp folder during tests. See [installation problems](installation-problems.md#antivirus-alerts-on-source-files).

## Recommended, not implemented

- An error tracker (Sentry or similar) and alerts on failed jobs.
- A health route that checks the database and queue.
- A failed-jobs and queue screen in the workspace.
- Log retention rules for `activity_logs`.
