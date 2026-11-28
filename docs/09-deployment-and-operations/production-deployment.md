# Production Deployment

> **Audience:** Operators, Hub Director · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Environment configuration](environment-configuration.md), [Staging deployment](staging-deployment.md), [Rollback procedures](rollback-procedures.md), [Backup and recovery](backup-and-recovery.md), [Secrets management](../08-security/secrets-management.md)

This page is the launch checklist for a real production site, plus the limits of the Render free plan that the current test/demo site runs on. The current site at https://uew-innovation-hub.onrender.com is **not** production-ready: `render.yaml` sets `SEED_DEMO_DATA=true`, `MAIL_MAILER=log`, `DOCUMENTS_DISK=private` and uses free plans for the web service and the database.

## Render free-plan limits

These limits are stated in the header comment of [`render.yaml`](../../render.yaml) and in [`../deployment.md`](../deployment.md).

| Limit | Effect on the Hub |
| --- | --- |
| Web service sleeps after 15 minutes without traffic | First request after sleep takes about a minute. The in-container queue worker also stops, so queued e-mail waits until the service wakes. |
| Free PostgreSQL expires after 30 days | The database is removed unless upgraded. All data is lost without a backup. |
| No persistent disk | Anything written under `storage/` is lost on every redeploy and restart. That includes documents (`DOCUMENTS_DISK=private`), gallery photos (`GALLERY_DISK=gallery`) and `storage/logs`. |
| One instance | No horizontal scaling and no zero-downtime deploy. |

> **Warning:** Do not hold real member data on the free plan. Move to a paid web service and a paid database before launch.

## Launch checklist

Work through this list in order. Each item names the variable or setting and where it is read. Variable details are in [Environment configuration](environment-configuration.md).

### 1. Infrastructure

- [ ] Paid Render web service (or any Docker host) and a **paid PostgreSQL** database. Change `plan: free` in `render.yaml` for both services, or set the plan in the dashboard.
- [ ] A **new, empty database**. Never turn demo seeding on against a database that holds real data.
- [ ] An S3-compatible bucket (AWS S3, Cloudflare R2 or Backblaze B2) with an access key limited to that bucket.
- [ ] An SMTP account for the sending domain (or a provider such as Resend or Postmark, see `config/services.php`).
- [ ] The real domain pointed at the service, with HTTPS (Render issues certificates for custom domains).

### 2. Environment variables

| Variable | Launch value | Why |
| --- | --- | --- |
| `APP_ENV` | `production` | Enables HTTPS forcing, 12-character passwords, and disables `MAIL_ALWAYS_TO`. |
| `APP_DEBUG` | `false` | No stack traces for visitors. |
| `APP_KEY` | generated once, never changed | Also encrypts the SMTP password in the `settings` table. Keep a copy in a password manager. |
| `APP_URL` | `https://<real domain>` | Used in e-mail links. An `https://` value turns on `URL::forceHttps()`. |
| `TRUSTED_PROXIES` | `*` on Render, or the proxy IPs | Correct client IPs for rate limiting and the audit log, and correct HTTPS detection. |
| `SEED_DEMO_DATA` | `false` | No demo accounts or sample content. |
| `ADMIN_EMAIL`, `ADMIN_NAME` | the real Hub Director | `hub:create-admin` runs at boot while no active Hub Director exists. |
| `ADMIN_PASSWORD` | strong, 12+ characters, upper and lower case, a number | If empty, the command generates a password and prints it once to the log. |
| `SEED_PASSWORD` | strong value | Still read by `hub:secure-demo-accounts` at every boot. If it is missing or weak the command changes nothing and logs a warning. |
| `BLOCK_DEFAULT_PASSWORDS` | `true` | Rejects sign-in with well-known passwords such as `password`. |
| `MAIL_MAILER` | `smtp` (plus `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`) | Real delivery. Alternatively configure SMTP in **Site settings > E-mail delivery**. |
| `MAIL_ALWAYS_TO` | empty | Ignored in production anyway. |
| `DOCUMENTS_DISK` | `s3` | Documents survive redeploys. |
| `GALLERY_DISK` | `s3` | Photos survive redeploys. Not set in `render.yaml`, add it. |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_DEFAULT_REGION`, `AWS_USE_PATH_STYLE_ENDPOINT` | bucket values | `AWS_DEFAULT_REGION=auto` for R2. |
| `DB_CONNECTION`, `DB_URL` | `pgsql`, connection string | On Render `DB_URL` comes from `fromDatabase`. For an external database add `?sslmode=require`. |
| `SESSION_SECURE_COOKIE` | `true` | HTTPS-only cookies. |
| `SEARCH_INDEXING` | `true` | Default is `false`, which serves `Disallow: /` in `robots.txt` and an `X-Robots-Tag: noindex` header. |
| `LOG_CHANNEL`, `LOG_LEVEL` | `stderr`, `warning` | Logs go to the platform log viewer. |

### 3. First boot

- [ ] Deploy. Watch the logs for `php artisan optimize`, the migrations and `hub:create-admin` output (see [Docker guide](docker-guide.md) for the step order).
- [ ] Check `GET /up` returns 200 ([Health checks](health-checks.md)).
- [ ] Sign in at `/sign-in` as the Hub Director and **change the password if it was generated**.
- [ ] In **Site settings > E-mail delivery**, send a test e-mail.
- [ ] Open `/robots.txt`. It should list a `Sitemap:` line and not `Disallow: /`.
- [ ] Upload a test PDF and a test photo, redeploy, and confirm both still load (proves the S3 disks).
- [ ] Run `php artisan hub:import-documents` once from the Render shell if legacy PDFs are needed on the S3 bucket. The entrypoint already runs it at every boot and skips files that exist.
- [ ] Confirm `GET /api/v2/projects` and `GET /api/v1/projects` answer and carry an `API-Version` header.

### 4. Before announcing

- [ ] Enable database backups ([Backup and recovery](backup-and-recovery.md)). Nothing in the repository automates backups.
- [ ] Turn on "wait for CI" or branch protection for `main` (see [CI/CD pipeline](ci-cd-pipeline.md)).
- [ ] Decide on uptime monitoring. None exists ([Logging and monitoring](logging-and-monitoring.md)).
- [ ] Review the open items in the [Technical debt register](../12-maintenance/technical-debt-register.md): no scheduler or pruning, no forced password change, a tester password committed in git history (commit `87df882`), no restore UI for soft deletes.
- [ ] Make sure no demo account exists: sign in as staff and check **Users**. If the database was ever seeded with demo data, create a fresh one instead of deleting accounts by hand.

## Things that differ from the test site

| Setting | Test site today | Production |
| --- | --- | --- |
| `SEED_DEMO_DATA` | `true` | `false`, fresh database |
| `MAIL_MAILER` | `log` | `smtp` |
| `DOCUMENTS_DISK`, `GALLERY_DISK` | `private`, `gallery` | `s3`, `s3` |
| `SEARCH_INDEXING` | unset (`false`) | `true` |
| `BLOCK_DEFAULT_PASSWORDS` | unset (`false`) | `true` |
| Database plan | free (expires after 30 days) | paid |
| Web plan | free (sleeps) | paid |

## Other hosts

The image is generic. On Railway, Fly.io or a VM, provide the same variables, a PostgreSQL or MySQL database, and a port in `PORT`. Terminate HTTPS at the platform and set `TRUSTED_PROXIES` to its address range. The Vercel entry (`vercel.json`, `api/index.php`) is an unverified alternative, see [Deployment overview](deployment-overview.md).
