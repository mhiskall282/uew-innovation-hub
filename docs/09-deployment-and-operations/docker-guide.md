# Docker Guide

> **Audience:** Developers, operators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Local deployment](local-deployment.md), [Build and release process](build-and-release-process.md), [Environment configuration](environment-configuration.md), [Health checks](health-checks.md)

How to build and run the production image defined by [`Dockerfile`](../../Dockerfile), what each file under `docker/` does, and what happens when the container starts. The image is the same one Render builds. There is no `docker-compose.yml` in the repository.

## Files

| File | Role |
| --- | --- |
| [`Dockerfile`](../../Dockerfile) | Three-stage build: Node 22 assets, Composer 2 dependencies, `php:8.4-apache` runtime |
| [`.dockerignore`](../../.dockerignore) | Keeps secrets, tests, docs, `legacy/`, `vendor/`, `node_modules/` and `api/` out of the image |
| [`docker/apache-vhost.conf`](../../docker/apache-vhost.conf) | Document root `public/`, dotfiles denied, one-year immutable cache for `/build/assets/`, logs to stdout and stderr |
| [`docker/php.ini`](../../docker/php.ini) | Installed as `/usr/local/etc/php/conf.d/zz-uew-hub.ini` |
| [`docker/entrypoint.sh`](../../docker/entrypoint.sh) | Boot steps, installed as `/usr/local/bin/uew-entrypoint` |
| [`.user.ini`](../../.user.ini) | **Not used in the image.** It is in `.dockerignore`, and Apache with `mod_php` ignores `.user.ini` anyway. Its values (`upload_max_filesize=100M`, `max_execution_time=300`) differ from `docker/php.ini` and do not apply. |

## Image contents

| Item | Value |
| --- | --- |
| Base | `php:8.4-apache` |
| PHP extensions | `pdo_pgsql`, `pdo_mysql`, `zip`, `intl`, `bcmath`, `opcache`, `gd` (JPEG and FreeType) |
| Not installed | The `redis` PHP extension (see [Scaling considerations](scaling-considerations.md)). `pdo_sqlite` is not installed explicitly (see the note below). |
| Apache | `rewrite` and `headers` enabled, `ServerTokens Prod`, `ServerSignature Off` |
| PHP settings | `display_errors=Off`, errors to `/dev/stderr`, `memory_limit=256M`, `max_execution_time=60`, `upload_max_filesize=25M`, `post_max_size=110M`, `max_file_uploads=12`, OPcache on with `validate_timestamps=0` |
| Port | `ENV PORT=8080`, `EXPOSE 8080` |
| Healthcheck | `curl -fsS http://127.0.0.1:${PORT}/up`, interval 30 s, timeout 5 s, start period 60 s, 3 retries |
| Entrypoint | `uew-entrypoint`, default command `apache2-foreground` |

> **Requires confirmation:** whether `pdo_sqlite` is available in the image. The `Dockerfile` does not install it explicitly. The official `php` image normally compiles it in, but this was not checked by running the image.

## Build

PowerShell or bash, from the repository root:

```bash
docker build -t uew-innovation-hub .
```

The build needs network access for `npm ci` and `composer install`. `public/build` and `vendor/` from your working copy are ignored.

## Run

The container needs a database and an `APP_KEY`. Without `APP_KEY` the entrypoint prints an error and exits with status 1.

### Option 1: with PostgreSQL in a second container

bash:

```bash
docker network create uew
docker run -d --name uew-db --network uew \
  -e POSTGRES_DB=uew_innovation_hub -e POSTGRES_USER=uew_hub -e POSTGRES_PASSWORD=change-me-locally \
  postgres:16
docker run --rm --name uew-hub --network uew -p 8080:8080 \
  -e APP_KEY="$(php artisan key:generate --show)" \
  -e APP_ENV=production -e APP_DEBUG=false -e APP_URL=http://localhost:8080 \
  -e DB_CONNECTION=pgsql -e DB_URL=postgresql://uew_hub:change-me-locally@uew-db:5432/uew_innovation_hub \
  -e ADMIN_EMAIL=you@example.com -e ADMIN_PASSWORD='Use-A-Strong-Passw0rd' \
  -e SESSION_SECURE_COOKIE=false -e LOG_CHANNEL=stderr \
  uew-innovation-hub
```

PowerShell:

```powershell
docker network create uew
docker run -d --name uew-db --network uew `
  -e POSTGRES_DB=uew_innovation_hub -e POSTGRES_USER=uew_hub -e POSTGRES_PASSWORD=change-me-locally `
  postgres:16
$key = php artisan key:generate --show
docker run --rm --name uew-hub --network uew -p 8080:8080 `
  -e APP_KEY="$key" `
  -e APP_ENV=production -e APP_DEBUG=false -e APP_URL=http://localhost:8080 `
  -e DB_CONNECTION=pgsql -e DB_URL=postgresql://uew_hub:change-me-locally@uew-db:5432/uew_innovation_hub `
  -e ADMIN_EMAIL=you@example.com -e ADMIN_PASSWORD='Use-A-Strong-Passw0rd' `
  -e SESSION_SECURE_COOKIE=false -e LOG_CHANNEL=stderr `
  uew-innovation-hub
```

Open http://localhost:8080 and sign in at `/sign-in`. The passwords above are placeholders for a throw-away local database. Do not reuse them anywhere else.

> **Requires confirmation:** this exact command pair was assembled from the `Dockerfile`, entrypoint and `config/database.php`, and was not executed while writing this page. `php artisan key:generate --show` needs a local PHP install; without it, generate 32 random bytes in base64 by any other means. The entrypoint adds the `base64:` prefix if it is missing.

### Useful variations

| Goal | Add |
| --- | --- |
| Listen on a different port | `-e PORT=9000 -p 9000:9000` (the entrypoint rewrites Apache's `Listen`) |
| Skip migrations | `-e RUN_MIGRATIONS=false` |
| No queue worker in this container | `-e RUN_QUEUE_WORKER=false` |
| No worker and inline mail | `-e QUEUE_CONNECTION=sync` |
| Demo data on a throw-away database | `-e SEED_DEMO_DATA=true -e SEED_PASSWORD=<strong value>` |
| Documents in a bucket | `-e DOCUMENTS_DISK=s3 -e GALLERY_DISK=s3 -e AWS_BUCKET=... -e AWS_ENDPOINT=... -e AWS_ACCESS_KEY_ID=... -e AWS_SECRET_ACCESS_KEY=...` |
| Keep uploads between runs | `-v uew-storage:/var/www/html/storage/app` (named volume) |

> **Warning:** Do not pass real secrets on a shared machine's command line, because they appear in shell history and `docker inspect`. Use `--env-file` with a file outside the repository. `.env` is excluded from the image by `.dockerignore`, so the container never reads it.

## Boot sequence (order matters)

`docker/entrypoint.sh` uses `set -euo pipefail`, so a failing step without `|| echo` stops the container.

| # | Step | Failure behaviour |
| --- | --- | --- |
| 1 | Set Apache `Listen` and `VirtualHost` to `$PORT` | Stops |
| 2 | Require `APP_KEY`, add `base64:` prefix | Exits 1 with an error message |
| 3 | `php artisan optimize`, create `storage/app/gallery` and `storage/app/private`, `chown www-data` | Stops |
| 4 | `php artisan migrate --force` when `RUN_MIGRATIONS` is `true` (default) | Stops (container restarts on Render) |
| 5 | `php artisan hub:create-admin` when `ADMIN_EMAIL` is set | Stops if it returns a failure code, for example a weak `ADMIN_PASSWORD` |
| 6 | `php artisan db:seed --class=PageSeeder --force` | Stops |
| 7 | `php artisan db:seed --force` when `SEED_DEMO_DATA=true` | Stops |
| 8 | `php artisan hub:secure-demo-accounts` | Warning only |
| 9 | `php artisan hub:import-documents` | Warning only |
| 10 | Background `queue:work --sleep=3 --tries=3 --max-time=3600 --memory=128` as `www-data`, in a loop that restarts it after 2 seconds | Not applicable |
| 11 | `exec apache2-foreground` | Container ends if Apache exits |

Step 5 note: a weak `ADMIN_PASSWORD` makes `hub:create-admin` return failure, and `set -e` then stops the boot. The service shows "deploy failed" until the password is fixed.

## Operating a running container

```bash
docker logs -f uew-hub                          # Apache access log (stdout) and errors (stderr)
docker exec -it uew-hub php artisan about       # runs as root, use -u www-data for files in storage/
docker exec -u www-data uew-hub php artisan hub:create-admin --email=you@example.com
docker exec -u www-data uew-hub php artisan queue:failed
docker inspect --format '{{json .State.Health}}' uew-hub
```

On Render use the dashboard **Shell** tab instead of `docker exec`.

## Hosting notes

- One container runs Apache, PHP and the queue worker. This is simple but couples web and worker. See [Scaling considerations](scaling-considerations.md).
- The image has no TLS. Put a reverse proxy or the platform load balancer in front, and set `TRUSTED_PROXIES`.
- CI builds the image (`docker` job) with a GitHub Actions layer cache but never runs it. See [CI/CD pipeline](ci-cd-pipeline.md).
- The `legacy/` folder is excluded from the image. The legacy PDFs imported by `hub:import-documents` come from `reports/` and `publications/` at the repository root, which are not excluded.
