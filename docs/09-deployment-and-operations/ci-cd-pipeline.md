# CI/CD Pipeline

> **Audience:** Developers, operators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Build and release process](build-and-release-process.md), [Testing guide](../05-developer-guide/testing-guide.md), [Docker guide](docker-guide.md), [Rollback procedures](rollback-procedures.md)

Continuous integration is one GitHub Actions workflow, [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml), named **CI**. Continuous deployment is Render's `autoDeploy` on `main`, which is configured in [`render.yaml`](../../render.yaml) and is **not** part of the workflow. The two run side by side.

```mermaid
flowchart TD
    Push["Push to main, pull request, or manual run"] --> CI
    subgraph CI["GitHub Actions: CI"]
        lint["lint: Code style (Pint)"]
        assets["assets: Front-end build"]
        security["security: Dependency audit"]
        assets --> tests["tests: sqlite and pgsql"]
        assets --> e2e["e2e: Playwright, 5 targets"]
        lint --> docker["docker: image build, no push"]
        tests --> docker
    end
    Push -->|"push to main only"| Render["Render autoDeploy: build Dockerfile"]
    Render --> Boot["entrypoint.sh: optimize, migrate, seed pages"]
    Boot --> Health["Health check /up"]
    Health --> Live["Live site"]
    CI -. "gate only if Wait for CI or branch protection is configured" .-> Render
```

> **Requires confirmation:** whether Render waits for CI. `render.yaml` has `autoDeploy: true` and nothing about CI. The comment at the top of `ci.yml` says Render deploys "once this workflow is green" and tells the team to enable "Wait for CI" or branch protection on `main`. Check the Render service settings (Auto-Deploy: "After CI checks pass" versus "On commit") and the GitHub branch protection rules. If neither is set, Render deploys every push to `main` whether CI passes or fails.

## Triggers and global settings

| Setting | Value |
| --- | --- |
| Triggers | `push` to `main`; every `pull_request`; `workflow_dispatch` (manual) |
| Concurrency | group `ci-${{ github.ref }}`, `cancel-in-progress: true`. A new push to the same ref cancels the older run. |
| Permissions | `contents: read` |
| Environment | `PHP_VERSION: '8.4'`, `NODE_VERSION: '22'` |
| Runner | `ubuntu-latest` for every job |
| Action versions | `actions/checkout@v5`, `shivammathur/setup-php@v2`, `actions/setup-node@v4`, `actions/upload-artifact@v4`, `actions/download-artifact@v4`, `docker/setup-buildx-action@v3`, `docker/build-push-action@v6` |

## Jobs

| Job id | Display name | Needs | Purpose |
| --- | --- | --- | --- |
| `lint` | Code style | none | Laravel Pint in test mode |
| `assets` | Front-end build | none | Vite build, uploads `build` artifact |
| `tests` | Tests (sqlite) and Tests (pgsql) | `assets` | PHPUnit on both databases, plus migration round trip and Blade compile on pgsql |
| `e2e` | Browser tests (Chromium, Firefox, WebKit, mobile) | `assets` | Playwright |
| `security` | Dependency audit | none | `composer audit` and `npm audit` |
| `docker` | Docker image builds | `lint`, `tests` | Builds the production image without pushing |

### `lint` (Code style)

1. `actions/checkout@v5`.
2. `shivammathur/setup-php@v2` with PHP 8.4, `coverage: none`.
3. `composer install --no-interaction --prefer-dist --no-progress`.
4. `vendor/bin/pint --test`. Fails if any file needs formatting. Fix locally with `vendor/bin/pint`.

### `assets` (Front-end build)

1. Checkout.
2. `actions/setup-node@v4`, Node 22, `cache: npm` (npm download cache keyed on `package-lock.json`).
3. `npm ci`.
4. `npm run build`.
5. `actions/upload-artifact@v4`: artifact name `build`, path `public/build`, `retention-days: 3`.

The artifact is consumed by `tests` and `e2e`, so they do not rebuild the assets.

### `tests` (matrix: `sqlite`, `pgsql`)

`fail-fast: false`, so one database failing does not cancel the other. The job declares a **service container** `postgres` on both matrix legs, but only the `pgsql` leg uses it.

| Service container | Value |
| --- | --- |
| Image | `postgres:16` |
| Env | `POSTGRES_DB=uew_test`, `POSTGRES_USER=uew`, `POSTGRES_PASSWORD=uew` (throw-away CI credentials) |
| Ports | `5432:5432` |
| Health check | `pg_isready -U uew`, interval 5 s, timeout 5 s, 10 retries |

Steps:

1. Checkout.
2. `setup-php@v2`: PHP 8.4, extensions `pdo_sqlite, pdo_pgsql, intl, gd, zip`, `coverage: none`.
3. `actions/download-artifact@v4` name `build` to `public/build`.
4. `composer install --no-interaction --prefer-dist --no-progress`.
5. **Prepare environment**: `cp .env.example .env` and `php artisan key:generate`.
6. **Use PostgreSQL** (only when `matrix.database == 'pgsql'`): appends `DB_CONNECTION=pgsql`, `DB_URL=pgsql://uew:uew@127.0.0.1:5432/uew_test` and `DB_DATABASE=uew_test` to `$GITHUB_ENV`.
7. **Migrations run cleanly (up, down, up)** (pgsql only): `php artisan migrate --force`, `php artisan migrate:rollback --step=1 --force`, `php artisan migrate --force`, `php artisan db:seed --force`. This proves that the latest migration batch has a working `down()` and that seeding works on PostgreSQL.
8. **Blade templates compile**: `php artisan view:cache`, then `php -l` on every compiled file in `storage/framework/views`, then `php artisan view:clear`.
9. **Test suite**: `php vendor/bin/phpunit --no-progress` with `DB_CONNECTION` set to the matrix value, `DB_DATABASE` set to `uew_test` for pgsql and `:memory:` for sqlite, and `DB_URL` set for pgsql only.

The suite had 194 tests passing at commit `d1296b1`. Test layout and commands: [Testing guide](../05-developer-guide/testing-guide.md).

### `e2e` (Browser tests)

`timeout-minutes: 40`.

1. Checkout.
2. `setup-php@v2`: PHP 8.4, extensions `pdo_sqlite, intl, gd, zip`.
3. `setup-node@v4`: Node 22, `cache: npm`.
4. Download artifact `build` to `public/build`.
5. `composer install ...`.
6. `npm ci`.
7. Prepare environment (`cp .env.example .env`, `php artisan key:generate`).
8. `npx playwright install --with-deps chromium firefox webkit`.
9. `npx playwright test`. `playwright.config.*` defines five projects: `chromium`, `firefox`, `webkit`, `mobile-android` (Pixel 7) and `mobile-ios` (iPhone 14). The mobile projects run only the `journeys` specs, and Firefox and WebKit skip the `responsive` specs. The web server is `node tests/e2e/serve.mjs`, which runs `migrate:fresh --seed` on an isolated `e2e.sqlite` and is polled at `/up`. See [End-to-end testing](../10-testing-and-quality/end-to-end-testing.md).
10. `actions/upload-artifact@v4` **only on failure**: artifact `playwright-report`, paths `playwright-report` and `test-results`, `retention-days: 7`.

There is no PostgreSQL in this job; the e2e server uses SQLite.

### `security` (Dependency audit)

1. Checkout, `setup-php@v2` (PHP 8.4).
2. `composer audit --locked --no-interaction`.
3. `setup-node@v4` (Node 22, no cache).
4. `npm audit --omit=dev --audit-level=high`. Fails on high or critical advisories in production dependencies.

This job has no `needs`, so it runs in parallel with the others. It does not gate `docker`.

### `docker` (Docker image builds)

`needs: [lint, tests]`.

1. Checkout.
2. `docker/setup-buildx-action@v3`.
3. `docker/build-push-action@v6` with `context: .`, `push: false`, `cache-from: type=gha`, `cache-to: type=gha,mode=max` (GitHub Actions cache for image layers).

It only proves that the [`Dockerfile`](../../Dockerfile) builds. It does not start the container, run `/up`, or publish an image. The image Render runs is built separately by Render.

## What the pipeline does not do

| Gap | Detail |
| --- | --- |
| No deploy step | Deployment is Render's `autoDeploy`. There is no GitHub deployment, release tag or image registry push. |
| No container smoke test | The image is built but never started in CI. |
| `e2e`, `security` and `docker` are not required by each other | `docker` waits for `lint` and `tests` only. |
| No scheduled run | `security` runs only on push, pull request and manual dispatch, so a new advisory is not caught until the next push. Recommended, not implemented: add a `schedule` trigger. |
| No Dependabot, static analysis or coverage | Listed in the [Technical debt register](../12-maintenance/technical-debt-register.md). |
| Local-only checks | [`scripts/security-scan.ps1`](../../scripts/security-scan.ps1) is not run by CI. |
| Direct commits to `main` | The repository history shows commits made straight to `main`, so CI runs after the push, and with `autoDeploy` on commit the deploy starts at the same time. |

## Re-running and debugging CI

1. Open the repository **Actions** tab on https://github.com/mhiskall282/uew-innovation-hub, choose the **CI** workflow, then **Run workflow** for a manual run (`workflow_dispatch`).
2. Reproduce a failed job locally with the same commands, for example `vendor/bin/pint --test`, `php vendor/bin/phpunit`, `npx playwright test`. See [Testing guide](../05-developer-guide/testing-guide.md).
3. Download `playwright-report` from a failed `e2e` run to see traces and screenshots.
