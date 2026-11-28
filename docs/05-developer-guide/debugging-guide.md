# Debugging Guide

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [common-development-issues](common-development-issues.md), [testing-guide](testing-guide.md), `../11-troubleshooting/logging-and-diagnostics.md`, `../08-security/security-monitoring.md`

Where to look first when something breaks locally, and the tools available in this repository. Only tools that are installed or built into Laravel are listed.

## Quick triage

| Symptom | First check |
| --- | --- |
| White page or generic 500 | `storage/logs/laravel.log` (last stack trace) |
| 419 "Page expired" | Missing `@csrf`, expired session, or `SESSION_DRIVER=database` without the `sessions` table (run `php artisan migrate`) |
| 403 | The user's role lacks the permission. Check `Role::permissions()` in `app/Enums/Role.php`, the route's `can:` middleware and the policy |
| 404 on a record | Route model binding. Slugs for albums and other models come from `HasUniqueSlug`; check `getRouteKeyName()` |
| 429 | A `throttle:` middleware. Limits are in `routes/web.php` and `AppServiceProvider::registerRateLimiters` |
| "Vite manifest not found" | Run `npm run build` or start `npm run dev` |
| `LazyLoadingViolationException` | A relationship was used without eager loading. Add `with()` or `load()` |
| E-mail not arriving | `MAIL_MAILER=log`: read `storage/logs/laravel.log`. Queued? Run `php artisan queue:work` |
| Page works but console shows CSP errors | `app/Http/Middleware/SecurityHeaders.php` allows only same-origin scripts, no inline `<script>` |

## Logs

- Local logs go to `storage/logs/laravel.log` (`LOG_CHANNEL=stack`, `LOG_STACK=single` in `.env.example`, `LOG_LEVEL=debug`).
- Production logs go to stderr (`LOG_CHANNEL=stderr` in `render.yaml` and `vercel.json`). Read them in the Render dashboard.

```powershell
Get-Content storage\logs\laravel.log -Tail 50 -Wait     # follow the log
Select-String -Path storage\logs\laravel.log -Pattern 'ERROR' | Select-Object -Last 10
php artisan pail                                         # needs the pcntl extension; not on native Windows
```

```bash
tail -f storage/logs/laravel.log
php artisan pail
```

The log file can grow large. Delete or truncate it freely locally. It is git-ignored.

## Error pages and debug mode

`APP_DEBUG=true` (the `.env.example` default) shows Laravel's error page with the stack trace. Playwright runs with `APP_DEBUG=false`, so test failures show the production error views in `resources/views/errors/`. The API (`/api/*`) always returns a JSON envelope `{"success": false, "message": ...}`; with debug on a 500 includes the exception message (`bootstrap/app.php`).

> **Warning:** Never set `APP_DEBUG=true` on a hosted site.

## Tinker

```powershell
php artisan tinker
```

```php
>>> App\Models\User::count()
>>> App\Enums\Role::Officer->permissions()
>>> App\Models\User::first()->hasPermission(App\Enums\Permission::ProjectsManage)
>>> App\Models\Album::with('photos')->latest()->first()
```

Tinker writes to your local database. Do not create or delete records in a database you care about.

## Routes and permissions

```powershell
php artisan route:list --name=workspace.albums
php artisan route:list --path=api/v1
php artisan route:list --json
```

To see which permissions a role holds, call `Role::<Case>->permissions()` in Tinker or read `app/Enums/Role.php`. To test a policy in isolation, write a feature test using `signInAs(Role::Intern)` and assert the status code.

## Database

```powershell
php artisan db:show                  # connection, tables, sizes
php artisan db:table users           # columns and indexes
php artisan migrate:status
php artisan tinker                   # DB::table('jobs')->count()
```

SQLite files can also be opened with any SQLite browser. Close it before running tests that use the same file. The test suite itself uses `:memory:`, so it never touches `database/database.sqlite`.

To print the SQL a code path runs, add `DB::listen(fn ($q) => logger($q->sql, $q->bindings));` temporarily in Tinker or a test, and remove it before committing.

## Queue and mail

```powershell
php artisan queue:work --once        # process one job
php artisan queue:failed             # list failed jobs
php artisan queue:retry all
```

With `QUEUE_CONNECTION=database` the jobs live in the `jobs` table and failures in `failed_jobs`. Tests use `sync` and `MAIL_MAILER=array`, so assert with `Mail::fake()`.

## Audit log

State-changing actions are recorded in `activity_logs` by `app/Services/AuditLogger.php`. To see what happened to a record, open the audit log in the workspace (needs the `audit.view` permission, held by the Hub Director) or query `App\Models\ActivityLog` in Tinker. This is the quickest way to find who changed a record and from which IP.

## Debugging tests

```powershell
php artisan test --filter YourTest --stop-on-failure
php vendor/bin/phpunit --filter YourTest --testdox
```

- Print a response: `$this->get('/x')->dump();` or `->dumpHeaders()`. Add `$this->withoutExceptionHandling();` at the top of a test to see the real exception instead of a 500 page.
- A test passes alone but fails in the full run: look for shared state such as a `static` property (`UserFactory::$password` is one) or a cache key left behind. Cache is `array` in tests, so it resets per process, not per test.
- A test touches the wrong database: check for `DB_DATABASE` or `DB_URL` in your shell ([testing-guide](testing-guide.md)).

## Debugging browser tests

```powershell
npx playwright test --project=chromium --headed
npx playwright test --debug
npx playwright show-trace test-results\<folder>\trace.zip
```

The server for Playwright prints PHP errors into the terminal. The e2e database is rebuilt on every run, so you can open `database/e2e.sqlite` after a run to see the state. Use `node tests/e2e/find-overflow.mjs <baseUrl> <width>` for layout overflow.

## Frontend

- Browser dev tools console: CSP violations show as errors. Alpine expressions evaluate through `'unsafe-eval'`, which the policy allows.
- Class not applied: Tailwind v4 scans `resources/**/*.blade.php`, `resources/**/*.js`, `app/Enums/*.php` and compiled views (see the `@source` lines in `resources/css/app.css`). A class built from string pieces in PHP outside those folders is not generated. Use full class names.
- Stale assets: delete `public/build` and `public/hot`, then run `npm run build`.

## Not installed

Laravel Telescope, Debugbar and Xdebug configuration are not part of the project. Installing a debugger locally is fine, but do not add it to `composer.json` without agreement.
