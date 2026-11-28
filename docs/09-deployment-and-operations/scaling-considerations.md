# Scaling Considerations

> **Audience:** Developers, operators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Infrastructure overview](infrastructure-overview.md), [Environment configuration](environment-configuration.md), [Performance maintenance](../12-maintenance/performance-maintenance.md), [Performance problems](../11-troubleshooting/performance-problems.md), [API rate limiting](../06-api-reference/rate-limiting.md)

The Hub runs as one container today. This page lists what already supports growth, what must change to run more than one instance, and the known bottlenecks. Nothing here has been load tested. All numbers are configuration values, not measured capacity. Recommended steps are marked as such.

## Where the design helps

| Property | Evidence |
| --- | --- |
| Stateless web tier once sessions leave local disk | `SESSION_DRIVER=database` and `CACHE_STORE=database` in `render.yaml`, so no per-container session files |
| File storage is behind disks | `DOCUMENTS_DISK` and `GALLERY_DISK` can be `s3` (`config/hub.php`) |
| Queue is external to the request | `QUEUE_CONNECTION=database`, mail and broadcasts are queued |
| Broadcast fan-out is chunked | `SendBroadcast` reads recipients with `lazyById(200)`, so memory stays flat |
| Public API is cache friendly | `Cache-Control: public, max-age=N, s-maxage=N` and a weak ETag with 304 support on v1 and v2 |
| v2 uses cursor pagination | No `COUNT(*)` for totals, stable cost for deep pages |
| N+1 queries are blocked in development | `Model::preventLazyLoading(! isProduction())` in `AppServiceProvider` |
| Programme capacity without per-row counts | `Program::scopeWithActiveApplicationsCount()` and one `withCount` subquery in v1 and v2 lists |

## Scaling path

Do these in order. Each step removes a limit of the one before.

### 1. Bigger single instance

Move from the free plan to a paid plan. This removes sleep, gives more CPU and memory, and keeps the architecture unchanged. Raise `memory_limit` in `docker/php.ini` only if needed (it is `256M`; the worker has `--memory=128`).

### 2. Move shared state off the container

| Concern | Current | Needed for several instances |
| --- | --- | --- |
| Documents | `private` disk (container) | `DOCUMENTS_DISK=s3` |
| Gallery photos | `gallery` disk (container) | `GALLERY_DISK=s3` (not in `render.yaml`) |
| Maintenance mode | `APP_MAINTENANCE_DRIVER=file`, per container | `cache` driver with a shared cache store |
| Compiled caches | built per container at boot | fine, every container builds its own |
| Sessions, cache, queue | database tables | database still works for a few instances. Redis is better (next step). |

Without S3, an upload handled by one container does not exist on another.

### 3. Redis for session, cache and queue

Set `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis` and the `REDIS_*` variables ([Environment configuration](environment-configuration.md)).

> **Warning:** The Docker image does **not** install the `redis` PHP extension, and `.env.example` and `config/database.php` default `REDIS_CLIENT` to `phpredis`. `composer.json` does not require `predis/predis`. Setting `QUEUE_CONNECTION=redis` today would fail. Before enabling Redis, either add `pecl install redis` and `docker-php-ext-enable redis` to the `Dockerfile`, or add `predis/predis` and set `REDIS_CLIENT=predis`. `render.yaml` provisions no Redis instance.

Benefits: sessions and cache stop hitting PostgreSQL on every request, queue polling stops adding load to the database, and `Cache::remember` for metrics (`METRICS_CACHE_TTL`) becomes faster.

### 4. Separate worker

Today `docker/entrypoint.sh` starts `queue:work` inside every web container. With several web containers this gives several workers, which is allowed but wasteful and ties worker restarts to web restarts.

1. Set `RUN_QUEUE_WORKER=false` on the web service(s).
2. Create a second service from the same image with start command `php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --memory=128` (as `www-data`, which owns `storage/`). On Render this is a **Background Worker**. `render.yaml` does not define one.
3. Give the worker the same environment variables as the web service, including `APP_KEY`, `DB_URL`, `MAIL_*` or the workspace SMTP (stored in the database), and the S3 settings.
4. Run one worker per queue consumer you need. Scale workers independently from web.

Note that the worker must not run `migrate` or `hub:create-admin`. Because the shared entrypoint runs them on every boot, a separate worker service should use its own start command or `RUN_MIGRATIONS=false`, and no `ADMIN_EMAIL`. Recommended, not implemented.

### 5. Several web instances

- Put them behind the platform load balancer and keep `TRUSTED_PROXIES` set.
- Only one deploy instance should run migrations at a time. The entrypoint has no lock. Two containers booting together can both run `migrate --force`. Laravel's migrator records each batch, but concurrent runs on PostgreSQL can still collide. Recommended: run migrations as a pre-deploy job or from one instance, and set `RUN_MIGRATIONS=false` on the rest.
- Rate limiting uses the `api` limiter keyed by IP and stored in the cache. With the `database` store the counters are shared. With a per-container store (`file` or `array`) they would not be.
- OPcache has `validate_timestamps=0`, so each instance must be rebuilt for new code. This fits immutable images.

### 6. CDN in front of the cacheable API

Responses from `/api/v1/*` and `/api/v2/*` carry `Cache-Control: public` with `max-age` and `s-maxage` equal to `API_CACHE_SECONDS` (default 60; v1 hard-codes 60 in `V1\ApiController::CACHE_SECONDS`) and a weak ETag. A CDN such as Cloudflare can serve these without reaching the origin. Notes:

- A CDN should respect `s-maxage` and revalidate with `If-None-Match`. The origin returns 304 with no body.
- The rate limiter counts client IP. Behind a CDN the origin sees the CDN's address unless `TRUSTED_PROXIES` includes it and the CDN forwards the client IP. Set `TRUSTED_PROXIES` accordingly or all clients share one limit.
- v2 `updated_since` has no tombstones, so a cached or synced client cannot see deletions.
- Static assets in `/build/assets/` already carry `public, max-age=31536000, immutable` (`docker/apache-vhost.conf`). Gallery images are streamed by `GalleryController` with `public, max-age=31536000, immutable` for published photos, so a CDN can cache them too. Private paths such as `/workspace` and `/portal` must bypass the CDN cache.
- Do not cache HTML pages that vary by sign-in state without a clear rule.

### 7. Database

- Move to a managed paid PostgreSQL with backups, then add a read replica only if reads dominate. The application has no read/write splitting configuration.
- Check indexes before launch: the full list is in a schema dump of a fresh migration run (see [tables-and-columns](../07-database/tables-and-columns.md)) and [Indexes and constraints](../07-database/indexes-and-constraints.md). Add indexes for the columns used by `updated_since`, `sort` and filters on the largest tables if query plans show scans.
- Run `EXPLAIN` on the public list queries once there is real data.
- Pruning: `sessions`, `cache`, `jobs`, `failed_jobs` and `activity_logs` grow without limit because there is no scheduler ([Logging and monitoring](logging-and-monitoring.md)).
- Connection count: each PHP process opens one connection. Several containers with a worker each can exhaust a small plan's connection limit. Consider PgBouncer.

## Known bottlenecks

| Bottleneck | Where | Detail |
| --- | --- | --- |
| Queue worker inside the web container | `docker/entrypoint.sh` step 10 | Shares CPU and memory (256 MB PHP limit, 128 MB worker limit) with Apache. A large broadcast slows page requests. The loop is not supervised separately. |
| Database used for sessions, cache and queue | `render.yaml` | Every request reads the session row. Cache and queue polling add queries. |
| Per-photo storage checks on S3 | `AlbumPhoto::isAvailable()` in `app/Models/AlbumPhoto.php` | Each call runs two `Storage::exists()` checks (thumbnail and large file). Per object these are memoised only for the life of the model instance. `Site\GalleryController` filters album covers and every photo in an album with it, and the v2 `/albums` endpoints only return photos whose files exist. On S3 or R2 each `exists()` is an HTTP request, so an album with 50 photos costs about 100 requests per uncached page view. On local disk it is cheap. Recommended, not implemented: cache availability, store a flag at upload time, or trust the database. |
| Photos and documents streamed through PHP | `GalleryController`, `DocumentStorage::download()` | Each image or PDF download occupies a PHP worker. A CDN or signed bucket URLs would offload this. |
| Free plan sleep and cold start | `render.yaml` | About one minute on the first request. The worker is also asleep. |
| Single Apache process pool | Image default | No tuning of `MaxRequestWorkers` in `docker/apache-vhost.conf`. Defaults apply. |
| Upload size | `docker/php.ini` | `post_max_size=110M` and `max_file_uploads=12` allow 10 photos of 10 MB per request. Several concurrent uploads need memory (`memory_limit=256M`) for image processing with GD. |
| No scheduler | `routes/console.php` | No cache or table maintenance runs on its own. |
| Sitemap and metrics | `SeoController`, `METRICS_CACHE_TTL` | Cached for one hour and 300 seconds, in the cache store. Fine at current size. |

## What to measure before scaling

Recommended, not implemented: there is no metrics collection today. Start with Render's CPU, memory and response time graphs, count queue depth with `DB::table('jobs')->count()`, and sample slow queries in PostgreSQL (`pg_stat_statements`, if the plan allows). Use [`scripts/`](../../scripts/) only for the checks they were written for (`signin-check.ps1`, `email-flow-test.php`, `security-scan.ps1`). None of them is a load test.

## Quick reference

| If you see | Try |
| --- | --- |
| Slow pages after a broadcast | `RUN_QUEUE_WORKER=false` plus a separate worker |
| Database CPU high with little traffic | Redis for sessions and cache |
| Gallery pages slow on S3 | Reduce `isAvailable()` checks (see above) |
| API load from many clients | CDN honouring `s-maxage`, ETag revalidation |
| Rate-limit complaints behind a CDN or proxy | Check `TRUSTED_PROXIES` and the forwarded client IP |
| Two containers fighting at deploy | `RUN_MIGRATIONS=false` on all but one |
