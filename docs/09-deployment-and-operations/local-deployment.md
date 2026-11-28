# Local Deployment

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Environment configuration](environment-configuration.md), [Docker guide](docker-guide.md), [Development environment setup](../05-developer-guide/development-environment-setup.md), [Testing guide](../05-developer-guide/testing-guide.md)

How to run the Hub on your own machine, either directly with PHP or inside Docker. Developer onboarding is covered in more depth in the developer guide; this page focuses on the deployment-relevant parts.

## Option A: PHP directly (default)

Requirements: PHP 8.4 with `pdo_sqlite`, `intl`, `gd`, `zip`, `bcmath`; Composer 2; Node 22. These match `.github/workflows/ci.yml` (`PHP_VERSION: '8.4'`, `NODE_VERSION: '22'`).

PowerShell:

```powershell
composer install
npm ci
Copy-Item .env.example .env
php artisan key:generate
New-Item database/database.sqlite -ItemType File -Force
php artisan migrate --seed
php artisan hub:import-documents
npm run build
php artisan serve
```

bash:

```bash
composer install && npm ci
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan hub:import-documents
npm run build
php artisan serve
```

The site is at http://localhost:8000 (`APP_URL` in `.env.example`). `composer setup` and `composer dev` exist in `composer.json` as shortcuts. `composer dev` starts four processes with `npx concurrently`: `php artisan serve`, `php artisan queue:listen --tries=1 --timeout=0`, `php artisan pail --timeout=0` and `npm run dev`.

Notes:

- `php artisan migrate --seed` runs `DatabaseSeeder`. With `APP_ENV=local` it creates the demo accounts and sample content. The account list is in `database/seeders/DemoContentSeeder.php`. The password is taken from `SEED_PASSWORD` (the `.env.example` value is a well-known default and must not be reused on a hosted site).
- With `MAIL_MAILER=log` e-mails (including sign-in and reset codes) are written to `storage/logs/laravel.log`. The app never shows codes on screen.
- Queued mail and broadcasts need a worker: `php artisan queue:work`, or `composer dev`, or set `QUEUE_CONNECTION=sync`.
- After pulling new code run `composer install`, `npm ci`, `npm run build`, `php artisan migrate`, `php artisan optimize:clear`. A stale `public/build` is the usual reason new styling does not appear.
- `hub:import-documents` copies the legacy PDFs in `reports/` and `publications/` to the documents disk. It skips files that already exist.

### Test the e-mail redirect and assets locally

Set `MAIL_ALWAYS_TO=you@example.com` so demo accounts (real `@uew.edu.gh` addresses) never receive test mail. It only works when `APP_ENV` is not `production`. Set `MAIL_ASSET_URL` to the live HTTPS site if you want the crest image to load in a real inbox. See [Environment configuration](environment-configuration.md).

## Option B: Docker locally

Use this to check the production image before pushing. Full detail is in the [Docker guide](docker-guide.md).

```powershell
docker build -t uew-innovation-hub .
$key = php artisan key:generate --show
docker run --rm -p 8080:8080 `
  -e APP_KEY="$key" -e APP_ENV=production -e APP_DEBUG=false `
  -e APP_URL=http://localhost:8080 `
  -e DB_CONNECTION=sqlite -e DB_DATABASE=/var/www/html/storage/app/private/hub.sqlite `
  -e SESSION_DRIVER=file -e CACHE_STORE=file -e QUEUE_CONNECTION=sync `
  uew-innovation-hub
```

> **Requires confirmation:** the SQLite-in-container command above is assembled from the Dockerfile and entrypoint and has not been run for this page. `database.sqlite` must exist for `migrate` to work, and the image does not create it. The supported path is PostgreSQL through `DB_URL`; see the Docker guide for a two-container example.

## Browser tests locally

`npm run test:e2e` (see `package.json`) starts `node tests/e2e/serve.mjs` on port 8010 with an isolated `database/e2e.sqlite`. It does not touch your development database. Install browsers first with `npx playwright install`.

## Common local problems

| Symptom | Cause | Fix |
| --- | --- | --- |
| `no such table` | Migrations not run, or `DB_DATABASE` points elsewhere | `php artisan migrate`; check for a stray `DB_DATABASE` in your shell |
| Old CSS | `public/build` is stale | `npm run build` |
| No e-mail arrives | `MAIL_MAILER=log` | Read `storage/logs/laravel.log` |
| E-mail jobs never run | No worker | `php artisan queue:work` |
| Config changes ignored | Cached config | `php artisan optimize:clear` |

More: [Common development issues](../05-developer-guide/common-development-issues.md), [Installation problems](../11-troubleshooting/installation-problems.md).
