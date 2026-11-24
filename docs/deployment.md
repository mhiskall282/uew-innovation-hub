# Deployment

Three ways to run the UEW Innovation Hub, from cheapest to most capable. All use the same code.

| Option | Cost | Good for | Limits |
| --- | --- | --- | --- |
| Local (`php artisan serve`) | Free | Development, demos | Your machine only |
| Render, free plan (Docker) | Free | Pilot, staging | Sleeps after 15 min idle; free Postgres expires after 30 days; no persistent disk |
| Vercel (serverless PHP) + Neon Postgres + Cloudflare R2 | Free tiers | Low-traffic public site | Community PHP runtime; no background worker, so mail is sent inline |
| Render Starter ($7/month) + paid Postgres | Low | Production | None of the above |

## 1. Local

See the README. In short:

```bash
composer install && npm ci
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan hub:import-documents
npm run build
php artisan serve
```

Sign in at http://localhost:8000/sign-in (demo accounts are listed in the README; password `password`).

Verification and reset codes are only ever delivered by e-mail; the app never shows them on screen. With `MAIL_MAILER=log` (local development only) e-mails are written to `storage/logs/laravel.log`, so a developer can read the code there. On any shared or hosted site configure real SMTP under **Site settings > E-mail delivery** so codes reach people's inboxes. Run `php artisan queue:work` to process queued e-mails and broadcasts.

**After pulling new code**, always run:

```bash
composer install && npm ci && npm run build
php artisan migrate
php artisan optimize:clear
```

Skipping `npm run build` leaves old CSS in place, so new layout classes (for example the desktop navigation) do not appear.

## 2. Render (Docker)

1. Push the repository to GitHub.
2. In Render: **New > Blueprint**, pick the repository. Render reads `render.yaml` and creates the web service and a Postgres database.
3. Fill in the prompted values:
   - `APP_URL`: the service URL, e.g. `https://uew-innovation-hub.onrender.com`
   - `ADMIN_EMAIL` and `ADMIN_PASSWORD`: the first Hub Director (12+ characters, upper and lower case, a number)
4. Deploy. On every boot `docker/entrypoint.sh` caches config, runs migrations, creates the first Hub Director if none exists, creates the default CMS pages, imports the legacy PDFs and starts a queue worker.
5. Sign in at `/sign-in`, open **Site settings > E-mail delivery**, enter SMTP details and send a test e-mail.

Uploaded documents: the free plan has no persistent disk. Set `DOCUMENTS_DISK=s3` with a Cloudflare R2 (free tier) or S3 bucket, then run `php artisan hub:import-documents` once from the Render shell.

Free Postgres expires after 30 days. For longer pilots use a free [Neon](https://neon.tech) database: set `DB_URL` to its connection string (with `?sslmode=require`) and remove the `databases` block from `render.yaml`.

## 3. Vercel

Vercel runs PHP through the community `vercel-php` runtime (`vercel.json`, `api/index.php`). The filesystem is read-only except `/tmp`, so:

- Use an external database: set `DB_CONNECTION=pgsql` and `DB_URL` (Neon free tier works).
- Use S3-compatible storage for documents: `DOCUMENTS_DISK=s3` plus the `AWS_*` variables (Cloudflare R2: `AWS_ENDPOINT=https://<account>.r2.cloudflarestorage.com`, `AWS_DEFAULT_REGION=auto`).
- There is no background worker; `QUEUE_CONNECTION=sync` sends e-mail during the request. Large broadcasts are better run from Render.

Steps:

1. `npm i -g vercel` and `vercel link` in the project folder.
2. Add environment variables in the Vercel dashboard: `APP_KEY` (from `php artisan key:generate --show`), `APP_URL`, `DB_URL`, `DB_CONNECTION=pgsql`, the `AWS_*` values and mail settings.
3. Run migrations once against the database from your machine:
   ```bash
   DB_CONNECTION=pgsql DB_URL="postgresql://..." php artisan migrate --force
   DB_CONNECTION=pgsql DB_URL="postgresql://..." php artisan db:seed --class=PageSeeder --force
   ADMIN_EMAIL=you@uew.edu.gh DB_CONNECTION=pgsql DB_URL="postgresql://..." php artisan hub:create-admin
   ```
4. `vercel --prod`.

## Environment reference

| Variable | Purpose |
| --- | --- |
| `APP_KEY` | Encryption key. Required. Never change it after launch (it also encrypts the SMTP password). |
| `APP_URL` | Public URL, used in e-mails and links. |
| `APP_DEBUG` | Always `false` in production. |
| `TRUSTED_PROXIES` | `*` behind Render/Vercel so HTTPS and client IPs are detected. |
| `DB_CONNECTION`, `DB_URL` | Database. `sqlite` locally, `pgsql` or `mysql` in production. |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | `database` by default; `redis` when traffic grows. |
| `SESSION_SECURE_COOKIE` | `true` on HTTPS. |
| `DOCUMENTS_DISK` | `private` (local disk) or `s3`. |
| `MAIL_*` | Fallback mail settings; the workspace **E-mail delivery** tab overrides them. |
| `ADMIN_EMAIL`, `ADMIN_NAME`, `ADMIN_PASSWORD` | First Hub Director, created by `hub:create-admin`. |
| `SEED_DEMO_DATA` | `true` only for demo sites. |

## Operations

- **Backups:** schedule daily database backups (Render and Neon both provide them). Documents in R2/S3 are durable; enable bucket versioning for extra safety.
- **Logs:** `LOG_CHANNEL=stderr` sends logs to the platform's log viewer.
- **Health check:** `GET /up` returns 200 when the app boots.
- **Scaling:** move sessions, cache and queue to Redis, run the queue worker as a separate service (`RUN_QUEUE_WORKER=false` on web containers), and add web instances behind the load balancer.
- **Security updates:** run `composer audit` and `npm audit` before each release.

## Troubleshooting

| Symptom | Cause and fix |
| --- | --- |
| `no such table: settings` (or any table) | Migrations have not run on the database the app is using. Run `php artisan migrate`. Check that no stray `DB_DATABASE` environment variable points at a different file. |
| Desktop navigation or other new styling missing | CSS not rebuilt. Run `npm run build` and hard-refresh the browser. |
| `/login` 404 | Fixed: `/login` now redirects to `/sign-in`. Clear route cache with `php artisan optimize:clear`. |
| Verification code e-mail never arrives | Check **Site settings > E-mail delivery**, send a test e-mail, check spam. Locally, read `storage/logs/laravel.log`. |
| Application/broadcast e-mails not sent | A queue worker must be running (`php artisan queue:work`), or set `QUEUE_CONNECTION=sync`. |
| 419 "page expired" | The form sat open past the session lifetime. Refresh and resubmit. |
| Uploaded PDFs vanish after redeploy | Ephemeral disk. Use `DOCUMENTS_DISK=s3`. |
| Locked out after failed sign-ins | Wait 15 minutes, or use **Forgot password**. |
