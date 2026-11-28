# Deployment Problems

> **Audience:** Operators, developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Deployment guide](../deployment.md), [Production deployment](../09-deployment-and-operations/production-deployment.md), [Docker guide](../09-deployment-and-operations/docker-guide.md), [CI/CD pipeline](../09-deployment-and-operations/ci-cd-pipeline.md), [Environment configuration](../09-deployment-and-operations/environment-configuration.md)

The live site is `https://uew-innovation-hub.onrender.com`: a Render free-plan Docker web service with a free PostgreSQL database, auto-deployed from the `main` branch of the GitHub repository (`render.yaml`, `Dockerfile`, `docker/entrypoint.sh`). The health check path is `/up`.

## What happens on every container start

`docker/entrypoint.sh` runs these steps in order. A failure in an early step stops the container.

| Step | Command or action | If it fails |
| --- | --- | --- |
| 1 | Set Apache's listen port from `PORT` (default 8080) | Port mismatch, health check fails |
| 2 | Require `APP_KEY`; add the `base64:` prefix when missing | Exits with `ERROR: APP_KEY is not set` |
| 3 | `php artisan optimize`, create `storage/app/gallery` and `storage/app/private`, `chown` to `www-data` | Container exits (`set -e`) |
| 4 | `php artisan migrate --force` (unless `RUN_MIGRATIONS=false`) | Container exits |
| 4 | `php artisan hub:create-admin` when `ADMIN_EMAIL` is set | Container exits on failure (for example a weak `ADMIN_PASSWORD`) |
| 4 | `php artisan db:seed --class=PageSeeder --force` | Container exits |
| 4 | `php artisan db:seed --force` when `SEED_DEMO_DATA=true` | Container exits |
| 4 | `php artisan hub:secure-demo-accounts` | Prints a warning, continues |
| 5 | `php artisan hub:import-documents` | Prints a warning, continues |
| 6 | Starts a background queue worker loop unless `RUN_QUEUE_WORKER=false` or `QUEUE_CONNECTION=sync` | Worker restarts itself every 2 seconds |
| 7 | `exec` Apache | |

Read the **deploy log** in Render top to bottom. The last message before the stop shows the failing step.

## Deploy fails: build stage

**Symptoms:** The Render build fails before the container starts.

**Possible causes:** `npm ci` fails (lock file out of step with `package.json`), `composer install` fails, or an extension cannot be compiled in the PHP stage.

**Diagnostic steps:** Run the same stages locally:

```powershell
npm ci
npm run build
composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
```

CI also builds the Docker image (job *Docker image builds*), so a failure there predicts a failed deploy.

**Resolution:** Fix the lock file or code, push, and let Render rebuild.

**Prevention:** Wait for CI to pass before merging to `main`, because `main` auto-deploys.

## Container exits: APP_KEY

See [installation problems](installation-problems.md#container-stops-with-app_key-is-not-set).

## Container exits: migration or seed error

**Symptoms:** The service restarts repeatedly. The log shows an SQL error after `migrate`.

**Possible causes:** The database is unreachable, credentials wrong, a migration conflicts with existing data, or the free database expired.

**Diagnostic steps:** Read the log for the SQLSTATE code. See [database problems](database-problems.md).

**Resolution:** Fix the cause. As a stop-gap to get the site up while you fix the database, set `RUN_MIGRATIONS=false`, but the code may then expect columns that do not exist.

**Prevention:** Run `php artisan migrate --pretend` locally against a copy of production data when a migration touches existing rows.

## Container exits: `hub:create-admin` failure

**Symptoms:** The log shows a validation message such as "The password field must be at least 12 characters" or "The email has already been taken", and the container stops.

**Cause:** `ADMIN_EMAIL` is set and the command returns failure. It fails when `ADMIN_PASSWORD` is set but weak (12+ characters, upper and lower case, a number are required), or the e-mail is already used by another account while no active Hub Director exists.

**Resolution:** Fix `ADMIN_PASSWORD` (or leave it empty so a password is generated and printed once in the log, which you should then rotate). If an active Hub Director already exists the command exits without doing anything.

**Prevention:** Remove `ADMIN_PASSWORD` from the environment after the first sign-in.

## Render cold start

See [performance problems](performance-problems.md#render-cold-start). The service sleeps after 15 minutes of no traffic and the next request takes about a minute.

## Uploaded files disappear after a deploy

**Symptoms:** Project report PDFs, publication PDFs, grant proposals, application uploads and gallery photos return 404 or vanish after a deploy or restart. Database rows are intact. Public gallery pages show fewer photos (`AlbumPhoto::isAvailable()` hides missing ones).

**Possible causes:** The free plan has no persistent disk. Files go to `storage/app/private` (documents) and `storage/app/gallery` (photos) inside the container, which is replaced on each deploy. `render.yaml` sets `DOCUMENTS_DISK=private`.

**Diagnostic steps:**

```powershell
php artisan config:show hub.documents_disk
php artisan config:show hub.gallery_disk
```

On Render, open the Shell and `ls storage/app/private storage/app/gallery`. Only files that were shipped in the image or copied by `hub:import-documents` at boot will be there.

**Resolution:**

1. Create an S3-compatible bucket (the project documents Cloudflare R2, which has a free tier).
2. Set `DOCUMENTS_DISK=s3`, `GALLERY_DISK=s3`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION=auto`, `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT=true`.
3. Redeploy and re-upload the lost files. Legacy PDFs can be re-copied: `php artisan hub:import-documents --source=<folder>` against the S3 disk.

**Prevention:** Decide on durable storage before real data goes in. `GALLERY_DISK` is missing from `.env.example`, so teams forget it.

## E-mails not sent from the hosted site

`render.yaml` sets `MAIL_MAILER=log`. See [backend problems](backend-problems.md#e-mails-only-appear-in-the-log). Switch to SMTP in **Site settings > E-mail delivery** after the first Hub Director signs in.

## Queue worker not running on the host

The entrypoint starts the worker inside the web container. If the container is asleep, jobs wait until it wakes. With multiple web instances each runs its own worker. For heavier use run a separate worker service and set `RUN_QUEUE_WORKER=false` on the web service. See [backend problems](backend-problems.md#queue-worker-not-running).

## Wrong links, mixed content, or sign-in loops behind the proxy

**Symptoms:** E-mail links use `http://`, assets are blocked as mixed content, sign-in redirects back to the form, signed links show "expired".

**Possible causes:** `TRUSTED_PROXIES` not set (client IP and scheme wrong), `APP_URL` wrong, `SESSION_SECURE_COOKIE=true` while the site is served over plain HTTP.

**Resolution:** On Render set `TRUSTED_PROXIES=*`, `APP_URL=https://<your-service>.onrender.com`, `SESSION_SECURE_COOKIE=true`. In production with an `https://` `APP_URL` the app calls `URL::forceHttps()`.

**Prevention:** Copy the environment block from `render.yaml`.

## Free database expired

**Symptoms:** After about 30 days the site returns 500 on every page. The log shows connection or authentication failures.

**Cause:** Free Render PostgreSQL databases expire after 30 days (comment in `render.yaml`).

**Resolution:** Upgrade the database plan, or create a free Neon database and set `DB_URL` to it. Restore data from a backup, then `php artisan migrate --force` runs on boot.

**Prevention:** Take regular dumps. See [backup and recovery](../09-deployment-and-operations/backup-and-recovery.md).

## Demo data on a site that should be real

**Symptoms:** Demo accounts and sample content appear on the live site.

**Cause:** `render.yaml` sets `SEED_DEMO_DATA=true`, described as "TEST SITE". The seeder runs on a fresh database.

**Resolution before launch:** Set `SEED_DEMO_DATA=false` and use a fresh database. Set a strong `SEED_PASSWORD` and `BLOCK_DEFAULT_PASSWORDS=true` while demo accounts still exist. Set `SEARCH_INDEXING=true` only at launch. A tester password was committed in git history (commit `87df882`). Treat it as public and make sure no live account uses it.

## Health check fails

**Symptoms:** Render marks the deploy failed although the log looks fine.

**Possible causes:** The app is not listening on `PORT`, the container needs longer than the check allows to boot and migrate, or `/up` returns an error because the environment is broken.

**Diagnostic steps:** Open `https://<service>/up` after the deploy. The Dockerfile's own `HEALTHCHECK` waits 60 seconds before counting failures.

## Vercel deployment

The repository also contains `vercel.json` and `api/index.php`. That setup uses `vercel-php`, `QUEUE_CONNECTION=sync`, `DOCUMENTS_DISK=s3` and config caches under `/tmp`. **Requires confirmation:** how complete and tested it is. Render is the documented live target. There is no persistent disk on Vercel either, so S3 storage is required for documents and gallery photos.

## Rollback

Render keeps earlier deploys. In the dashboard, open **Events** or **Deploys** and redeploy a previous successful one. Database migrations are not reversed by this. If a migration changed data, restore the database from a backup. See [rollback procedures](../09-deployment-and-operations/rollback-procedures.md).
