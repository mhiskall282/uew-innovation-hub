# Testing Guide

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `d1296b1` (plus the API/health changes listed in [DOCUMENTATION-CHANGELOG](../DOCUMENTATION-CHANGELOG.md))
> **Related:** [development-workflow](development-workflow.md), [debugging-guide](debugging-guide.md), `../10-testing-and-quality/testing-strategy.md`, `../10-testing-and-quality/end-to-end-testing.md`

This page is the canonical list of test commands. It covers PHPUnit (unit and feature tests), running against PostgreSQL the way CI does, and Playwright browser tests. For strategy and quality gates see `../10-testing-and-quality/testing-strategy.md`.

## Overview

| Suite | Location | Runner | Database | Count |
| --- | --- | --- | --- | --- |
| Unit | `tests/Unit` (`DomainTest.php`) | PHPUnit 11 | In-memory SQLite | |
| Feature | `tests/Feature` (and subfolders `Api`, `Auth`, `Portal`, `Site`, `Workspace`) | PHPUnit 11 | In-memory SQLite (`RefreshDatabase`) | 197 tests pass in total at time of writing |
| Browser | `tests/e2e` (`journeys`, `a11y`, `responsive` specs) | Playwright | `database/e2e.sqlite`, rebuilt each run | |

`phpunit.xml` defines two test suites (`Unit`, `Feature`) and sets these environment values: `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `DB_URL=` (empty), `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`, `BCRYPT_ROUNDS=4`, `BROADCAST_CONNECTION=null`.

> **Warning:** PHPUnit's `<env>` values do not override variables that already exist in the process environment. If `DB_DATABASE` or `DB_URL` is set in your shell, the tests use it, and `RefreshDatabase` then wipes that database. CI relies on this behaviour to point the tests at PostgreSQL. Locally, make sure the variables are unset (see below).

## PHPUnit

Full run:

```powershell
php artisan test
# or
composer test                      # runs config:clear first, then php artisan test
# or exactly what CI runs
php vendor/bin/phpunit --no-progress
```

```bash
php artisan test
composer test
php vendor/bin/phpunit --no-progress
```

Filtered runs:

```powershell
php artisan test --filter GalleryTest                       # one class
php artisan test --filter test_every_state_changing_workspace_route_enforces_its_permission
php artisan test tests/Feature/Workspace/PrivilegesTest.php # one file
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --stop-on-failure
php vendor/bin/phpunit --filter GalleryTest
php vendor/bin/phpunit tests/Feature/GalleryTest.php --testdox
```

```bash
php artisan test --filter GalleryTest
php vendor/bin/phpunit --filter GalleryTest --testdox
```

`php artisan test --parallel` does not work: the `brianium/paratest` package is not in `composer.json`.

### Useful environment switches

| Variable | Effect |
| --- | --- |
| `SMOKE_DEBUG=1` | `RouteSmokeTest` prints each route's status to stderr |

```powershell
$env:SMOKE_DEBUG = '1'; php artisan test --filter RouteSmokeTest; Remove-Item Env:SMOKE_DEBUG
```

```bash
SMOKE_DEBUG=1 php artisan test --filter RouteSmokeTest
```

### Clean environment check

```powershell
Get-ChildItem Env:DB_* ; Get-ChildItem Env:APP_ENV    # should print nothing, or only values you expect
Remove-Item Env:DB_DATABASE, Env:DB_URL, Env:DB_CONNECTION -ErrorAction SilentlyContinue
```

```bash
env | grep -E '^(DB_|APP_ENV)'      # should print nothing
unset DB_DATABASE DB_URL DB_CONNECTION
```

### Running the suite on PostgreSQL (as CI does)

CI starts a `postgres:16` service with database `uew_test` and throwaway credentials defined in `.github/workflows/ci.yml`, then runs the suite with these variables:

```text
DB_CONNECTION=pgsql
DB_URL=pgsql://uew:uew@127.0.0.1:5432/uew_test
DB_DATABASE=uew_test
```

To reproduce locally, start a PostgreSQL 16 instance with an empty database, and set the variables for one command:

```powershell
$env:DB_CONNECTION='pgsql'; $env:DB_URL='pgsql://USER:PASSWORD@127.0.0.1:5432/uew_test'; $env:DB_DATABASE='uew_test'
php vendor/bin/phpunit --no-progress
Remove-Item Env:DB_CONNECTION, Env:DB_URL, Env:DB_DATABASE
```

```bash
DB_CONNECTION=pgsql DB_URL='pgsql://USER:PASSWORD@127.0.0.1:5432/uew_test' DB_DATABASE=uew_test php vendor/bin/phpunit --no-progress
```

> **Warning:** Use a scratch database. `RefreshDatabase` drops and recreates tables. Never point this at a database with data you want to keep.

CI also checks that migrations run up, down one step, and up again on PostgreSQL, then seeds:

```bash
php artisan migrate --force
php artisan migrate:rollback --step=1 --force
php artisan migrate --force
php artisan db:seed --force
```

## Writing PHPUnit tests

- Feature tests extend `Tests\TestCase` and use `RefreshDatabase`.
- Helpers on `TestCase`: `staff(Role $role = Role::Officer, array $attributes = [])`, `signInAs(Role $role)`, `pdf($name)` (a minimal valid PDF upload).
- Factories: `UserFactory` has `role()`, `admin()`, `inactive()`, `member()`. Others exist for `Program`, `ProgramApplication`, `Project`, `Post`, `Publication`, `Organization`, `GrantApplication`.
- Use `Mail::fake()` when a flow sends e-mail. Use `Storage::fake('gallery')` or the disk the code writes to.
- Method names: `test_<behaviour_in_snake_case>`.

Tests that guard the whole application:

| Test | What it does | When you must touch it |
| --- | --- | --- |
| `tests/Feature/Workspace/PrivilegesTest.php` | Loops over every state-changing `/workspace` route with a `can:` middleware and checks that each role lacking the permission is refused and guests are redirected to sign in | Adding a workspace route. It picks up new routes by itself. Update the explicit assertions when a role matrix changes |
| `tests/Feature/RouteSmokeTest.php` | Seeds demo data, requests every named GET route as a guest and each role, and fails on any HTTP 500 | A new GET route with a model binding needs an entry in the `$bindings` array in `urls()` or it is skipped |
| `tests/Feature/SecurityHardeningTest.php`, `SecurityHeadersTest.php` | Security behaviour and headers | Changing middleware or headers |
| `tests/Feature/Api/PublicApiTest.php` | `/api/v1` responses | Changing API output |

Security note for fixtures: do not put strings that resemble web shells or malware into test files. Windows Defender flagged one earlier fixture (commit `9f3bdee`). See [common-development-issues](common-development-issues.md).

## Playwright browser tests

Files: `playwright.config.ts`, `tests/e2e/*.spec.ts`, `tests/e2e/helpers.ts`, `tests/e2e/serve.mjs`.

How it works:

1. Playwright starts `node tests/e2e/serve.mjs` as its web server.
2. `serve.mjs` refuses to run unless `DB_DATABASE` ends with `e2e.sqlite`. Playwright sets it to `database/e2e.sqlite`.
3. It deletes and recreates that file, then runs `php artisan migrate:fresh --seed --force` and `php artisan config:clear`.
4. It starts PHP's built-in server from `public/` on `127.0.0.1:8010` (override with `E2E_PORT`) using Laravel's `server.php` router.
5. Playwright waits for `http://127.0.0.1:8010/up`, then runs the specs.

The environment for the server (set in `playwright.config.ts`): `APP_ENV=local`, `APP_DEBUG=false`, `DB_CONNECTION=sqlite`, `CACHE_STORE=array`, `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`, `SEED_PASSWORD` (a throwaway test value defined in `playwright.config.ts`), `SEARCH_INDEXING=false`. Your dev database is never touched.

Settings: one worker (PHP's built-in server handles one request at a time), `retries: 1` only when `CI` is set, 60 s test timeout, HTML report only in CI.

Prerequisites: PHP and Node installed, `npm install` done, **assets built** (`npm run build`, because the pages need `public/build/manifest.json`), and browsers installed:

```powershell
npm install
npm run build
npx playwright install chromium            # one browser is enough locally
npx playwright install --with-deps chromium firefox webkit   # what CI installs (--with-deps needs Linux)
```

```bash
npm install && npm run build
npx playwright install chromium
```

Run:

```powershell
npm run test:e2e                         # all five projects: chromium, firefox, webkit, mobile-android, mobile-ios
npm run test:e2e:chromium                # Chromium project only (journeys, a11y, responsive)
npx playwright test --project=firefox
npx playwright test tests/e2e/journeys.spec.ts --project=chromium
npx playwright test -g "sign in"         # filter by test title
npx playwright test --ui                 # interactive UI
npx playwright show-report               # open the last HTML report (written in CI mode)
```

```bash
npm run test:e2e
npm run test:e2e:chromium
```

Projects (from `playwright.config.ts`):

| Project | Device | Specs |
| --- | --- | --- |
| `chromium` | Desktop Chrome | all |
| `firefox` | Desktop Firefox | all except `responsive` |
| `webkit` | Desktop Safari | all except `responsive` |
| `mobile-android` | Pixel 7 | `journeys` only |
| `mobile-ios` | iPhone 14 | `journeys` only |

### E2E_CHANNEL and E2E_PORT

- `E2E_CHANNEL=msedge` runs the `chromium` and `mobile-android` projects in the locally installed Microsoft Edge instead of a downloaded Chromium. CI leaves it unset. Useful on Windows when the Playwright download is blocked.
- `E2E_PORT` changes the server port (default 8010).

```powershell
$env:E2E_CHANNEL = 'msedge'; npm run test:e2e:chromium; Remove-Item Env:E2E_CHANNEL
$env:E2E_PORT = '8020'; npm run test:e2e:chromium; Remove-Item Env:E2E_PORT
```

```bash
E2E_CHANNEL=msedge npm run test:e2e:chromium
E2E_PORT=8020 npm run test:e2e:chromium
```

### Helpers

`tests/e2e/helpers.ts` exports `PASSWORD` (the throwaway value matching `SEED_PASSWORD` in the Playwright config), `signIn(page, email)`, `watchConsole(page)` (fails on JS errors and CSP violations), `expectNoHorizontalScroll`, `expectImagesLoaded`. Accessibility checks use `@axe-core/playwright` in `a11y.spec.ts`.

Two diagnostic scripts (not part of the test run) exist: `node tests/e2e/find-overflow.mjs <baseUrl> <width>` lists elements that cause horizontal overflow, and `node tests/e2e/measure-performance.mjs <baseUrl>` measures load timings. Both expect an app already running at the URL (for example `php artisan serve`) and demo data. They use `E2E_CHANNEL` too.

### Artefacts

Failures write screenshots and traces to `test-results/` (`screenshot: 'only-on-failure'`, `trace: 'retain-on-failure'`), and CI writes `playwright-report/`. Both are git-ignored.

## Test data

Demo content comes from `database/seeders/DemoContentSeeder.php` and `DemoProgrammesSeeder.php`; all passwords come from `SEED_PASSWORD`. See `../10-testing-and-quality/test-data-management.md`.

## Not available

- No code-coverage tooling is configured (CI sets `coverage: none`).
- No static analysis tool (PHPStan, Larastan) is installed. Recommended, not implemented.
