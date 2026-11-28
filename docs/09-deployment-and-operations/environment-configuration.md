# Environment Configuration

> **Audience:** Developers, operators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Deployment overview](deployment-overview.md), [Production deployment](production-deployment.md), [Secrets management](../08-security/secrets-management.md), [Secure configuration](../08-security/secure-configuration.md), [Rate limiting](../06-api-reference/rate-limiting.md)

This is the canonical list of environment variables for the Hub. It covers every variable in [`.env.example`](../../.env.example), every Hub-specific `env()` call in `config/*.php` and `bootstrap/app.php`, and the variables read only by [`docker/entrypoint.sh`](../../docker/entrypoint.sh). Secret values are never written here.

**How values are loaded.** Locally, Laravel reads `.env`. On Render, values come from the service environment (set in `render.yaml` or the dashboard). `docker/entrypoint.sh` runs `php artisan optimize` at boot, which caches config using the runtime environment. Change a variable, then restart or redeploy the service.

> **Warning:** `APP_KEY` also encrypts the SMTP password stored in the `settings` table (see `app/Services/MailSettings.php`). Changing it after launch makes that password unreadable and invalidates sessions and signed links.

Legend. **Secret:** yes means never commit or log it. **Env:** L = local, R = Render/production, V = Vercel.

## 1. Core application

| Variable | Default | Read in | Env | Secret | Notes |
| --- | --- | --- | --- | --- | --- |
| `APP_NAME` | `Laravel` (`.env.example`: `UEW Innovation Hub`) | `config/app.php` | L R V | no | Also seeds cache prefix and session cookie name. |
| `APP_ENV` | `production` (`.env.example`: `local`) | `config/app.php` | L R V | no | `production` turns on HTTPS forcing, strong password rules, and disables `Mail::alwaysTo`. |
| `APP_KEY` | none | `config/app.php`, `docker/entrypoint.sh` | L R V | **yes** | Container exits if empty. Entrypoint prepends `base64:` if missing (Render `generateValue` omits it). Generate with `php artisan key:generate --show`. |
| `APP_DEBUG` | `false` (`.env.example`: `true`) | `config/app.php` | L R V | no | Must be `false` in production. |
| `APP_URL` | `http://localhost` (`.env.example`: `http://localhost:8000`) | `config/app.php`, `config/filesystems.php`, `config/mail.php` | L R V | no | Used in e-mail links. If it starts with `https://` in production, `URL::forceHttps()` is applied (`AppServiceProvider`). `sync: false` in `render.yaml`, so Render prompts for it. |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE` | `en`, `en`, `en_US` | `config/app.php` | all | no | |
| `APP_PREVIOUS_KEYS` | empty | `config/app.php` | R | **yes** | Comma-separated old keys for rotation. Not in `.env.example`. |
| `APP_MAINTENANCE_DRIVER` | `file` | `config/app.php` | all | no | `file` mode is per container. Use `cache` with a shared cache for several instances. |
| `APP_MAINTENANCE_STORE` | `database` | `config/app.php` | all | no | Only used when the driver is `cache`. |
| `BCRYPT_ROUNDS` | framework default | `.env.example`, hashing config | all | no | `.env.example` sets `12`. |
| `TRUSTED_PROXIES` | empty (no proxy trusted) | `bootstrap/app.php` | R V | no | `*` or a comma-separated IP list. Needed for HTTPS detection, client IPs (rate limiting, audit log). `render.yaml` and `vercel.json` set `*`. Read with `env()` in `bootstrap/app.php`, so it is read from the real process environment; a value that exists only in `.env` is ignored once config is cached. |

## 2. Logging

| Variable | Default | Read in | Env | Secret | Notes |
| --- | --- | --- | --- | --- | --- |
| `LOG_CHANNEL` | `stack` | `config/logging.php` | all | no | `render.yaml` and `vercel.json`: `stderr`. |
| `LOG_STACK` | `single` | `config/logging.php` | L | no | Channels in the `stack` channel. |
| `LOG_LEVEL` | `debug` | `config/logging.php` | all | no | `render.yaml`: `warning`. |
| `LOG_DEPRECATIONS_CHANNEL`, `LOG_DEPRECATIONS_TRACE` | `null`, `false` | `config/logging.php` | L | no | |
| `LOG_DAILY_DAYS` | `14` | `config/logging.php` | optional | no | Only for the `daily` channel. |
| `LOG_STDERR_FORMATTER` | none | `config/logging.php` | optional | no | |
| `LOG_SLACK_WEBHOOK_URL`, `LOG_SLACK_USERNAME`, `LOG_SLACK_EMOJI` | none | `config/logging.php` | optional | webhook: **yes** | Laravel `slack` channel. Not enabled by default. |
| `LOG_PAPERTRAIL_HANDLER`, `PAPERTRAIL_URL`, `PAPERTRAIL_PORT`, `LOG_SYSLOG_FACILITY` | framework defaults | `config/logging.php` | optional | no | Unused today. |

## 3. Database

| Variable | Default | Read in | Env | Secret | Notes |
| --- | --- | --- | --- | --- | --- |
| `DB_CONNECTION` | `sqlite` | `config/database.php` | all | no | `render.yaml`: `pgsql`. |
| `DB_URL` | none | `config/database.php` | R V | **yes** | Full connection string. On Render it comes from `fromDatabase` (`uew-innovation-hub-db`). |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `127.0.0.1`, `3306`/`5432`, `laravel`, `root`, empty | `config/database.php` | optional | password: **yes** | `DB_DATABASE` defaults to `database/database.sqlite` for SQLite. |
| `DB_SSLMODE` | `prefer` | `config/database.php` | pgsql | no | Use `require` for external hosts. |
| `DB_CHARSET`, `DB_COLLATION`, `DB_SOCKET`, `MYSQL_ATTR_SSL_CA` | framework defaults | `config/database.php` | MySQL | no | |
| `DB_FOREIGN_KEYS` | `true` | `config/database.php` | SQLite | no | |
| `DB_CACHE_CONNECTION`, `DB_CACHE_TABLE`, `DB_CACHE_LOCK_CONNECTION`, `DB_CACHE_LOCK_TABLE`, `DB_QUEUE_CONNECTION`, `DB_QUEUE_TABLE`, `DB_QUEUE`, `DB_QUEUE_RETRY_AFTER`, `SESSION_CONNECTION`, `SESSION_TABLE`, `QUEUE_FAILED_DRIVER` | framework defaults (`cache`, `jobs`, `default`, `90`, `sessions`, `database-uuids`) | `config/cache.php`, `config/queue.php`, `config/session.php` | optional | no | Not set anywhere in the repo. |

## 4. Sessions, cache, queue, Redis

| Variable | Default | Read in | Env | Secret | Notes |
| --- | --- | --- | --- | --- | --- |
| `SESSION_DRIVER` | `database` | `config/session.php` | all | no | |
| `SESSION_LIFETIME` | `120` | `config/session.php` | all | no | Minutes. |
| `SESSION_ENCRYPT`, `SESSION_PATH`, `SESSION_DOMAIN` | `false`, `/`, none | `config/session.php` | all | no | |
| `SESSION_SECURE_COOKIE` | none | `config/session.php` | R V | no | Set `true` on HTTPS. `render.yaml` sets it. |
| `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE`, `SESSION_EXPIRE_ON_CLOSE`, `SESSION_COOKIE`, `SESSION_STORE`, `SESSION_PARTITIONED_COOKIE` | `true`, `lax`, `false`, slug of `APP_NAME` + `-session`, none, `false` | `config/session.php` | optional | no | |
| `CACHE_STORE` | `database` | `config/cache.php` | all | no | |
| `CACHE_PREFIX` | slug of `APP_NAME` + `-cache-` | `config/cache.php` | optional | no | |
| `QUEUE_CONNECTION` | `database` | `config/queue.php`, `docker/entrypoint.sh` | all | no | `sync` on Vercel, and the entrypoint skips the worker when it is `sync`. |
| `BROADCAST_CONNECTION` | `log` | `.env.example` | all | no | |
| `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, `REDIS_URL`, `REDIS_USERNAME`, `REDIS_DB`, `REDIS_CACHE_DB`, `REDIS_PREFIX`, `REDIS_PERSISTENT`, `REDIS_CLUSTER`, `REDIS_QUEUE_CONNECTION`, `REDIS_QUEUE`, `REDIS_QUEUE_RETRY_AFTER`, `REDIS_CACHE_CONNECTION`, `REDIS_CACHE_LOCK_CONNECTION`, `REDIS_MAX_RETRIES`, `REDIS_BACKOFF_*` | `phpredis`, `127.0.0.1`, `6379`, `null`, ... | `config/database.php`, `config/cache.php`, `config/queue.php` | optional | password and URL: **yes** | Redis is not provisioned by `render.yaml`. The Docker image installs no `redis` PHP extension (see [Scaling considerations](scaling-considerations.md)). |

## 5. Files and object storage

| Variable | Default | Read in | Env | Secret | Notes |
| --- | --- | --- | --- | --- | --- |
| `DOCUMENTS_DISK` | `private` | `config/hub.php` | R V | no | `private` (`storage/app/private`) or `s3`. `vercel.json` sets `s3`. Lost on redeploy on Render free plan unless `s3`. |
| `GALLERY_DISK` | `gallery` | `config/hub.php` | R V | no | `gallery` (`storage/app/gallery`) or `s3`. Not in `render.yaml`. |
| `MAX_UPLOAD_KB` | `20480` | `config/hub.php` | all | no | Application limit. `docker/php.ini` allows 25 MB per file (`upload_max_filesize`), 110 MB per request (`post_max_size`) and 12 files per request (`max_file_uploads`), sized for 10 gallery photos of 10 MB. |
| `FILESYSTEM_DISK` | `local` | `config/filesystems.php` | all | no | |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` | none | `config/filesystems.php` | R V | **yes** | S3 or R2 credentials. |
| `AWS_DEFAULT_REGION` | none (`.env.example`: `auto`) | `config/filesystems.php` | R V | no | `auto` for Cloudflare R2. |
| `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_URL` | none | `config/filesystems.php` | R V | no | `AWS_ENDPOINT` is the R2 or S3-compatible URL. |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `false` (`.env.example`: `true`) | `config/filesystems.php` | R V | no | |

## 6. Mail

| Variable | Default | Read in | Env | Secret | Notes |
| --- | --- | --- | --- | --- | --- |
| `MAIL_MAILER` | `log` | `config/mail.php` | all | no | `log` writes e-mails to the log. Use `smtp` in production. |
| `MAIL_SCHEME`, `MAIL_URL`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_EHLO_DOMAIN` | none, none, `127.0.0.1`, `2525`, none, none, host of `APP_URL` | `config/mail.php` | R | password: **yes** | |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | `hello@example.com`, `APP_NAME` | `config/mail.php` | all | no | `render.yaml`: `innovationhub@uew.edu.gh`. |
| `MAIL_SENDMAIL_PATH`, `MAIL_LOG_CHANNEL` | framework defaults | `config/mail.php` | optional | no | |
| `MAIL_ALWAYS_TO` | empty | `config/mail.php` (`always_to`), `AppServiceProvider::boot()` | L, staging | no | See below. |
| `MAIL_ASSET_URL` | empty (falls back to `APP_URL`) | `config/mail.php` (`asset_url`), `resources/views/components/emails/layout.blade.php` | L, staging | no | See below. |
| `POSTMARK_API_KEY`, `RESEND_API_KEY` | none | `config/services.php` | optional | **yes** | Only if you switch `MAIL_MAILER` to those drivers. |

Administrators can override the mailer, host, port, encryption, username, password and from address in **Site settings > E-mail delivery**. Those values live in the `settings` table and are applied at boot by `MailSettings::apply()`. If the workspace mailer is `env` (the default), the variables above apply.

**`MAIL_ALWAYS_TO`.** `AppServiceProvider::boot()` runs `Mail::alwaysTo($address)` only when `! $this->app->isProduction()` and the value is non-empty. Every message, including those sent by the queue worker, is then delivered to that one inbox instead of the real recipients. When `APP_ENV=production` the variable is ignored. This is verified in code. Consequence: a staging service must run with `APP_ENV` other than `production` to use it. Render's `render.yaml` sets `APP_ENV=production`, so you must change that value on a staging service Outside production the app also turns on `Model::preventLazyLoading` (N+1 queries throw errors) and lowers the minimum password length from 12 to 8 characters, so a staging service behaves slightly differently from production.

**`MAIL_ASSET_URL`.** E-mails link to the UEW crest by URL; images are never attached. The layout uses `config('mail.asset_url') ?: config('app.url')`. Set it to the public HTTPS site when e-mails are sent from a machine where `APP_URL` is `http://localhost:8000`, or the images will be broken in recipients' inboxes.

## 7. Hub settings (`config/hub.php`)

| Variable | Default | Env | Secret | Notes |
| --- | --- | --- | --- | --- |
| `HUB_CONTACT_EMAIL` | `innovationhub@uew.edu.gh` | all | no | |
| `METRICS_CACHE_TTL` | `300` | all | no | Seconds. |
| `ADMIN_EMAIL` | none | R | no | Setting it makes the entrypoint run `hub:create-admin` on every boot. The command does nothing once an active Hub Director exists. |
| `ADMIN_NAME` | `Hub Director` | R | no | |
| `ADMIN_PASSWORD` | none | R | **yes** | 12+ characters with upper case, lower case and a number. If empty, the command generates a password and prints it once to the output (on Render, the log). |
| `SEED_DEMO_DATA` | `false` | R (demo only) | no | When `true`, entrypoint runs `php artisan db:seed --force`. In production the seeder also needs a strong `SEED_PASSWORD`. |
| `SEED_PASSWORD` | `password` | R (demo only) | **yes** | Password for demo accounts. `hub:secure-demo-accounts` moves accounts using a well-known password to this value on every boot. The default `password` is itself on the weak list. |
| `BLOCK_DEFAULT_PASSWORDS` | `false` | R | no | `true` rejects sign-in with passwords such as `password`, `secret`, `12345678`, `admin`. Set it once `SEED_PASSWORD` is strong. |
| `SEARCH_INDEXING` | `false` | R (live only) | no | `false` serves `Disallow: /` in `robots.txt` and adds noindex headers. Set `true` at launch. |
| `API_DOCS_URL` | GitHub docs URL | all | no | Read in both `config/hub.php` and `config/api.php`, with different default URLs. Setting it fixes both. |

## 8. Open data API (`config/api.php`)

| Variable | Default | Notes |
| --- | --- | --- |
| `API_RATE_LIMIT_PER_MINUTE` | `60` | Per client IP, shared by v1 and v2 (`api` limiter in `AppServiceProvider`). |
| `API_CACHE_SECONDS` | `60` | `Cache-Control: public, max-age, s-maxage` for v2. **v1 ignores it:** `V1\ApiController` uses a hard-coded 60-second constant. |
| `API_V1_DEPRECATED_AT`, `API_V1_SUNSET_AT` | empty | Set to emit `Deprecation`, `Link rel="deprecation"` and `Sunset` headers on v1 (`ApiVersion` middleware). Sunset at least six months after deprecation. |
| `API_V2_DEPRECATED_AT`, `API_V2_SUNSET_AT` | empty | Same for v2. Read in `config/api.php` but not listed in `.env.example`. |

None are secrets. The v2 page sizes (`default_limit` 20, `max_limit` 100) and `current = v2` are fixed in the config file, not in the environment.

## 9. Container-only variables (`docker/entrypoint.sh`)

| Variable | Default | Effect |
| --- | --- | --- |
| `PORT` | `8080` (`ENV PORT=8080` in the `Dockerfile`) | Apache `Listen` and `<VirtualHost>` are rewritten to this port. Render injects it. The Docker `HEALTHCHECK` also uses it. |
| `RUN_MIGRATIONS` | `true` | When not exactly `true`, `php artisan migrate --force` is skipped. |
| `RUN_QUEUE_WORKER` | `true` | When `true` (and `QUEUE_CONNECTION` is not `sync`), a `queue:work` loop runs as `www-data` inside the container. Set `false` when a separate worker service exists. |
| `ADMIN_EMAIL`, `ADMIN_NAME`, `ADMIN_PASSWORD` | see section 7 | `ADMIN_EMAIL` triggers `hub:create-admin`. |
| `SEED_DEMO_DATA` | `false` | See section 7. |

## 10. Other framework variables (not set by the Hub)

`AUTH_GUARD`, `AUTH_PASSWORD_BROKER`, `AUTH_MODEL`, `AUTH_PASSWORD_RESET_TOKEN_TABLE`, `AUTH_PASSWORD_TIMEOUT` (`config/auth.php`); `MEMCACHED_*`, `DYNAMODB_*` (`config/cache.php`); `BEANSTALKD_*`, `SQS_*` (`config/queue.php`); `SLACK_BOT_USER_OAUTH_TOKEN`, `SLACK_BOT_USER_DEFAULT_CHANNEL` (`config/services.php`); `VITE_APP_NAME` (build time). All have Laravel defaults and nothing in the repository sets them.

Vercel-only variables in `vercel.json`: `APP_CONFIG_CACHE`, `APP_EVENTS_CACHE`, `APP_PACKAGES_CACHE`, `APP_ROUTES_CACHE`, `APP_SERVICES_CACHE`, `VIEW_COMPILED_PATH` (all under `/tmp/storage`). `api/index.php` sets `LARAVEL_STORAGE_PATH=/tmp/storage`.

## Which environment needs what

| Concern | Local | Test/demo (Render today) | Production launch |
| --- | --- | --- | --- |
| `APP_ENV` / `APP_DEBUG` | `local` / `true` | `production` / `false` | `production` / `false` |
| Database | SQLite | Render free Postgres | Paid Postgres via `DB_URL` |
| `SEED_DEMO_DATA` | `false` (use `php artisan migrate --seed`) | `true` | `false` |
| `SEARCH_INDEXING` | `false` | `false` | `true` |
| `BLOCK_DEFAULT_PASSWORDS` | `false` | `true` recommended | `true` |
| `DOCUMENTS_DISK`, `GALLERY_DISK` | `private`, `gallery` | `private` (render.yaml; files lost on redeploy) | `s3`, `s3` |
| `MAIL_MAILER` | `log` | `log` (render.yaml) | `smtp` or workspace SMTP |

## Inconsistencies found

- `.env.example` comment says production values are in `docs/deployment.md`, which is older than this page. `render.yaml` also points to it.
- `.env.example` lacks `API_V2_DEPRECATED_AT`, `API_V2_SUNSET_AT`, `RUN_MIGRATIONS`, `RUN_QUEUE_WORKER`, `PORT`, `APP_PREVIOUS_KEYS` and `API_DOCS_URL`.
- `render.yaml` sets none of `GALLERY_DISK`, `SEARCH_INDEXING`, `BLOCK_DEFAULT_PASSWORDS`, `RUN_QUEUE_WORKER`, so the code defaults apply.
- `SEED_PASSWORD` defaults to `password` in `config/hub.php`.
