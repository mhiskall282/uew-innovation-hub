# Build and Release Process

> **Audience:** Developers, operators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [CI/CD pipeline](ci-cd-pipeline.md), [Docker guide](docker-guide.md), [Rollback procedures](rollback-procedures.md), [Release management](../12-maintenance/release-management.md), [Versioning strategy](../12-maintenance/versioning-strategy.md)

This page explains how source code becomes a running release: the build stages, what runs at container start, and the manual steps around a release. The project has no release tags, no changelog automation and no artifact registry. A release is "a commit on `main` that Render built".

## Release model

| Aspect | Current practice |
| --- | --- |
| Source of truth | `main` on https://github.com/mhiskall282/uew-innovation-hub |
| Release trigger | A push to `main` (Render `autoDeploy: true`) |
| Artifact | A Docker image built by Render from the `Dockerfile`. It is not published anywhere you can pull from. |
| Version number | None for the application. The API has its own versions (`v1`, `v2`, `config/api.php`). |
| Environments | One hosted site (test/demo). No staging. See [Staging deployment](staging-deployment.md). |
| Release notes | `.ai/changelog.md` and `.ai/state.md` are updated by contributors per `AGENTS.md`. No automated notes. |
| Commits | Made straight to `main` (open item in the debt register). Pull requests trigger CI but nothing forces their use. |

## Build stages

The [`Dockerfile`](../../Dockerfile) has three stages. Render runs the same build as `docker build`.

| Stage | Base | What it does |
| --- | --- | --- |
| 1. `assets` | `node:22-alpine` | `npm ci`, copies `vite.config.js`, `resources/` and `app/Enums/`, runs `npm run build` into `public/build` |
| 2. `vendor` | `composer:2` | `composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs` |
| 3. runtime | `php:8.4-apache` | Installs PHP extensions, copies `vendor`, the application and `public/build`, then `composer dump-autoload --optimize --classmap-authoritative --no-dev` and creates the `storage/` folders owned by `www-data` |

`.dockerignore` keeps `.env`, `.git`, `tests`, `docs`, `legacy`, `*.md`, `node_modules`, `vendor`, `public/build`, SQLite files, `.user.ini`, `vercel.json` and `api/` out of the build context. Local `vendor/` and `public/build` are never copied. The image always uses freshly built ones.

## What runs at container start

Every boot, [`docker/entrypoint.sh`](../../docker/entrypoint.sh) does the following, in this order. Each step is safe to repeat.

1. Rewrite Apache `Listen` and `<VirtualHost>` to `$PORT`.
2. Fail if `APP_KEY` is empty. Add the `base64:` prefix if missing.
3. `php artisan optimize` (caches config, routes, views, events from the runtime environment). Create `storage/app/gallery` and `storage/app/private`, fix ownership.
4. `php artisan migrate --force`, unless `RUN_MIGRATIONS` is not `true`.
5. `php artisan hub:create-admin` when `ADMIN_EMAIL` is set.
6. `php artisan db:seed --class=PageSeeder --force` (About, FAQ, Privacy, Terms pages; never overwrites edits).
7. `php artisan db:seed --force` when `SEED_DEMO_DATA=true`.
8. `php artisan hub:secure-demo-accounts` (warning only on failure).
9. `php artisan hub:import-documents` (warning only on failure).
10. A `queue:work` loop as `www-data` unless `RUN_QUEUE_WORKER` is not `true` or `QUEUE_CONNECTION=sync`.
11. `exec apache2-foreground`.

> **Warning:** Migrations run automatically on every deploy, before the new code takes traffic, with no backup step. A destructive migration is applied to production as soon as `main` is deployed. Review migrations before merging. See [Rollback procedures](rollback-procedures.md).

Because `php artisan optimize` caches routes and config at boot, any environment change needs a restart. OPcache runs with `validate_timestamps=0`, so code changes only take effect in a new container.

## Release checklist (manual)

Recommended practice, derived from the existing workflow. Nothing enforces it.

1. Work on a branch and open a pull request so CI runs before `main` changes ([Git workflow](../05-developer-guide/git-workflow.md)).
2. Locally, run `vendor/bin/pint --test`, `php artisan test` and `npm run build` ([Testing guide](../05-developer-guide/testing-guide.md)).
3. If the change adds a migration, read it and check that `down()` works (CI does this on PostgreSQL: up, rollback one step, up).
4. If the change adds an environment variable, add it to `.env.example`, `render.yaml` when it needs a value on Render, and [Environment configuration](environment-configuration.md).
5. Update `.ai/state.md` and `.ai/changelog.md` as `AGENTS.md` requires.
6. Merge when CI is green. Render builds and deploys.
7. Watch the Render deploy log until the service is live, then open `/up` and the home page.
8. Smoke test: sign in as staff, open the workspace dashboard, load `/api/v2/projects`.

## Changing the API

API versions live in `routes/api/v1.php` and `routes/api/v2.php`. v1 is frozen: its contract does not change, apart from additive items such as ETags. Breaking changes ship as a new version. To retire a version, set `API_V1_DEPRECATED_AT` first, then `API_V1_SUNSET_AT` at least six months later, which adds `Deprecation`, `Link rel="deprecation"` and `Sunset` headers (`app/Http/Middleware/ApiVersion.php`). Remove routes only after the sunset date. See [API overview](../06-api-reference/api-overview.md).

## Building locally

| Task | PowerShell and bash |
| --- | --- |
| Install | `composer install` then `npm ci` |
| Build assets | `npm run build` (output `public/build`) |
| Build image | `docker build -t uew-innovation-hub .` |
| Clear caches after a pull | `php artisan optimize:clear` |

## Gaps

| Gap | Effect |
| --- | --- |
| No automatic backup before migrations | A bad migration cannot be undone from a backup unless one was taken by hand |
| No tagged releases | Rollback targets are commit hashes or Render deploy entries |
| Render deploy is independent of CI unless configured | A failing commit can reach the site ([CI/CD pipeline](ci-cd-pipeline.md)) |
| Build runs on Render's builder | Free-plan builds are slow and share limited resources |
