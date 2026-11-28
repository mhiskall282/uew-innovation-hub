# Secure Configuration

> **Audience:** operators, developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Environment configuration](../09-deployment-and-operations/environment-configuration.md) (canonical variable list), [Docker guide](../09-deployment-and-operations/docker-guide.md), [Production deployment](../09-deployment-and-operations/production-deployment.md), [Secrets management](secrets-management.md)

This page lists the settings that matter for security, the value each should have in production, what the repository ships today, and the HTTP headers the application sends. For every variable's meaning, see [environment configuration](../09-deployment-and-operations/environment-configuration.md).

## Production settings checklist

| Setting | Required value | Shipped in `render.yaml` | Shipped in `.env.example` | Notes |
| --- | --- | --- | --- | --- |
| `APP_ENV` | `production` | `production` | `local` | Enables 12-character passwords, HTTPS forcing, no lazy-loading guard, `Mail::alwaysTo` ignored |
| `APP_DEBUG` | `false` | `"false"` | `true` | Debug pages leak environment and stack traces. The API error handler also hides messages when debug is off (`bootstrap/app.php`) |
| `APP_KEY` | Unique, secret | `generateValue: true` | empty | The entrypoint adds the `base64:` prefix when missing and refuses to start without a key |
| `APP_URL` | `https://...` | `sync: false` | `http://localhost:8000` | `URL::forceHttps()` only runs when this starts with `https://` |
| `SESSION_SECURE_COOKIE` | `true` | `"true"` | `false` | Without it the cookie can travel over HTTP |
| `SESSION_DRIVER` | `database` | `database` | `database` | Allows server-side revocation |
| `TRUSTED_PROXIES` | Proxy IPs, or `*` behind a platform load balancer | `"*"` | empty | See below |
| `LOG_CHANNEL`, `LOG_LEVEL` | `stderr`, `warning` | same | `stack`, `debug` | Container logs go to the platform |
| `SEED_DEMO_DATA` | `false` for real data | `"true"` | `false` | Hosted demo only |
| `SEED_PASSWORD` | Strong, secret | `sync: false` | `password` | `.env.example` ships the weak default for local work |
| `BLOCK_DEFAULT_PASSWORDS` | `true` on any hosted site | **not set** | `false` | Opt-in. Set it in the Render dashboard |
| `MAIL_MAILER` | `smtp` or a provider mailer | `log` | `log` | `log` writes codes and links to the logs |
| `SEARCH_INDEXING` | `true` only at launch | not set (false) | `false` | Adds `X-Robots-Tag: noindex` to every page when false |
| `DOCUMENTS_DISK` | `s3` with a private bucket for real data | `private` | `private` | Free Render disk is ephemeral |
| `GALLERY_DISK` | `gallery` (default) or an S3 disk | not set | **missing** | Read in `config/hub.php`; add to `.env.example` |
| `RUN_MIGRATIONS` | `true` | not set (default true) | n/a | `entrypoint.sh` runs `migrate --force` |

`MAIL_ALWAYS_TO` redirects all mail to one inbox outside production, so demo accounts never receive test mail (`AppServiceProvider`). It is ignored when `APP_ENV=production`.

### Trusted proxies

`bootstrap/app.php` calls `trustProxies(at: ...)` only when `TRUSTED_PROXIES` is set. `*` trusts the `X-Forwarded-*` headers from any sender. On Render all traffic comes through the platform proxy, so this is correct there. If the container were reachable directly, a client could forge `X-Forwarded-For` and evade IP rate limits and falsify audit-log IPs. Do not use `*` on a host that exposes the container without a proxy.

## HTTP security headers

Set by [`SecurityHeaders`](../../app/Http/Middleware/SecurityHeaders.php), appended to the `web` and `api` groups.

| Header | Value |
| --- | --- |
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `SAMEORIGIN` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=(), payment=()` |
| `Cross-Origin-Opener-Policy` | `same-origin` |
| `Content-Security-Policy` | `default-src 'self'; script-src 'self' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` (only when `$request->isSecure()`) |
| `X-Robots-Tag` | `noindex, nofollow` for private paths (`workspace`, `portal`, `notifications`, `onboarding`, `verify-email*`, `sign-in`, `sign-up`, `forgot-password`, `reset-password`, `unsubscribe/*`, `documents/*`, `programmes/*/apply`) and for every path while `SEARCH_INDEXING=false` |

The CSP is skipped while the Vite dev server is running, and a response that already has a CSP header keeps it. `'unsafe-eval'` is needed by Alpine.js expressions; `'unsafe-inline'` for styles covers Alpine `x-show` and progress bar widths. Moving to Alpine's CSP build would remove both but needs every inline expression rewritten. No `preload` directive is set on HSTS. No `Cross-Origin-Resource-Policy` or `Cross-Origin-Embedder-Policy` header is sent.

There is no `config/cors.php`, so the framework default (`vendor/laravel/framework/config/cors.php`) applies through the global `HandleCors` middleware: for `api/*` every origin, method and header is allowed (`allowed_origins` is `*`) and credentials are not supported. Browser scripts on any origin can therefore read the public API, v1 and v2 alike. This matches [Integration architecture](../04-architecture/integration-architecture.md). Read from code; not confirmed with a browser test. To restrict it, publish `config/cors.php` with `php artisan config:publish cors`.

## PHP and Apache hardening

[`docker/php.ini`](../../docker/php.ini): `expose_php=Off`, `display_errors=Off`, `display_startup_errors=Off`, `log_errors=On`, `error_log=/dev/stderr`, `upload_max_filesize=25M`, `post_max_size=30M`, `max_file_uploads=5`, `session.upload_progress.enabled=0`, `session.cookie_httponly=1`, `session.use_strict_mode=1`, `opcache.validate_timestamps=0`.

[`Dockerfile`](../../Dockerfile): `ServerTokens Prod`, `ServerSignature Off`, `a2enmod rewrite headers`, multi-stage build so Node and Composer are not in the final image, `composer install --no-dev`, `HEALTHCHECK` against `/up`. Apache serves only `public/` ([`apache-vhost.conf`](../../docker/apache-vhost.conf)), `Options -Indexes`, dotfiles denied, long cache for `/build/assets/`. `disable_functions` is not set; `open_basedir` is not set. Recommended, not implemented.

[`docker/entrypoint.sh`](../../docker/entrypoint.sh): requires `APP_KEY`, runs `php artisan optimize`, migrates, creates the admin, seeds default pages, runs `hub:secure-demo-accounts`, imports legacy documents, then starts a queue worker with `runuser -u www-data`. Apache workers also run as `www-data`. The container image does not drop root for the entrypoint itself (Apache needs it to bind and then drops privileges).

> **Warning:** `.user.ini` at the repository root sets `upload_max_filesize = 100M`, `post_max_size = 110M` and `max_execution_time = 300`. It has no effect in the Docker image but is far above the 25M container limit and the 20 MB application limit. Remove it or align it if the site is ever served by PHP-FPM or CGI.

## Application behaviours tied to configuration

- `Model::preventLazyLoading` is on outside production, which keeps N+1 queries out of tests but is off in production.
- `MailSettings::apply()` lets the Hub Director override mail transport at runtime. It swallows exceptions so a bad mail setting cannot take the site down.
- Health check `/up` (Laravel built-in) reveals nothing beyond status.
- Debug tooling: Telescope, Pulse and Nightwatch are disabled in `phpunit.xml`; none is installed in production dependencies (**Requires confirmation:** check `composer.json` `require-dev` for Pail/Tinker; they are not deployed with `--no-dev`).

## Gaps

| Gap | Fix |
| --- | --- |
| `BLOCK_DEFAULT_PASSWORDS` not set for the hosted demo | Set `true` and a strong `SEED_PASSWORD` in Render |
| `GALLERY_DISK` missing from `.env.example` | Add it |
| `.user.ini` conflicts with `docker/php.ini` | Remove or align |
| CSP has `unsafe-eval` and `unsafe-inline` styles | Alpine CSP build |
| No `disable_functions`, `open_basedir` | Recommended, not implemented |
| No HSTS preload, CORP or COEP | Optional |
