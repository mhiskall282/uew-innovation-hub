# Performance Problems

> **Audience:** Developers, operators · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Scaling considerations](../09-deployment-and-operations/scaling-considerations.md), [Performance testing](../10-testing-and-quality/performance-testing.md), [Performance maintenance](../12-maintenance/performance-maintenance.md), [Database problems](database-problems.md)

Slow or stale pages, with the causes that exist in this code base and host. No profiling tool such as Telescope or Debugbar is installed, so measure with the log, the browser Network tab and the commands below.

## Render cold start

**Symptoms:** The first visit after a quiet period takes about a minute, shows a Render waiting page, or times out. Later requests are fast.

**Cause:** Free Render web services sleep after 15 minutes without traffic (comment in `render.yaml`). Waking means starting the container, which runs `php artisan optimize`, `migrate --force`, two seeders, `hub:secure-demo-accounts`, `hub:import-documents` and starts the queue worker before Apache accepts requests (`docker/entrypoint.sh`).

**Diagnostic steps:** Time the request twice:

```powershell
Measure-Command { Invoke-WebRequest https://uew-innovation-hub.onrender.com/up -UseBasicParsing }
```

A first call of 30 to 70 seconds and a second call under a second confirms a cold start.

**Resolution:** Wait and reload. For demos, open the site a minute before the audience arrives.

**Prevention:** Use a paid plan that does not sleep. A pinger that calls `/up` every 10 minutes keeps a free service awake but may breach the host's terms. Check them first. Fewer boot steps would shorten the wake time, for example skipping the seeders once data exists (`SEED_DEMO_DATA=false`).

## Slow workspace lists or dashboard

**Symptoms:** A workspace page takes seconds to load.

**Possible causes:**

1. Many queries per page (N+1). In development and tests, `Model::preventLazyLoading` throws on them. In production it does not, so a missed eager load is silently slow.
2. Unindexed searches. Search uses `LIKE '%term%'` on several columns (`Searchable` trait), which cannot use an index. It is fine for thousands of rows and slow for millions.
3. An "All time" report. `InsightsService` reads interests and dates of birth in PHP in chunks of 1,000 (`lazyById`), so cost grows with the number of members.
4. A cold database connection after sleep.

**Diagnostic steps:** Count queries for one request locally:

```powershell
php artisan tinker --execute="DB::enableQueryLog(); app(App\Services\MetricsService::class)->workspaceStats(); dump(count(DB::getQueryLog()));"
```

Run the test suite: `php artisan test` fails on lazy-loading violations.

**Resolution:** Add `with()` eager loading in the controller. Use the filters before opening large lists. Choose a shorter report range.

**Prevention:** Keep controllers eager loading as in `ProjectController::index` and `ApplicationController::index`. Review new views for relation access.

## Dashboard or API numbers do not update

**Symptoms:** You changed a project or approved an application, and the dashboard or `/api/v1/stats` shows the old figure.

**Cause:** Intentional caching. `MetricsService` caches public and workspace figures for `METRICS_CACHE_TTL` seconds (default 300) and the open-applications counter for 60 seconds. API responses also send a short public `Cache-Control`.

**Resolution:** Wait, or run `php artisan cache:clear` (local or Render shell).

**Prevention:** Lower `METRICS_CACHE_TTL` if staff need faster refresh. A lower value means more database work.

## Settings or template change seems ignored

**Cause:** Settings are cached forever until saved (`SettingsService::CACHE_KEY`). Saving through the workspace clears the cache. A direct database edit does not.

**Resolution:** `php artisan cache:clear`.

## Slow report PDF

**Symptoms:** The PDF takes 10 seconds or more, or fails.

**Cause:** DomPDF renders in PHP. All five sections over "All time" is the heaviest case. `memory_limit` is 256M and `max_execution_time` 60 seconds (`docker/php.ini`). The route is throttled to 10 per minute.

**Resolution:** Choose a shorter period or fewer sections. See [backend problems](backend-problems.md#pdf-report-fails-or-times-out).

## Slow photo upload

**Cause:** Each upload is decoded with GD, rotated, resized twice and re-encoded as JPEG (`GalleryService`). A 30-megapixel photo needs a large bitmap in memory (the service raises `memory_limit` to 512M for the decode). Ten photos in a batch multiply the time. Slow mobile networks add to it.

**Resolution:** Upload 3 to 5 photos at a time. Resize huge originals first. See [backend problems](backend-problems.md#photo-upload-drops-files).

## Slow page load in the browser

**Possible causes:** Large images on the public site, a cold service, many requests, or a disabled cache.

**Facts:** Gallery photos are served as 640 px thumbnails and 1600 px large versions with long browser caching (the deployment guide says published photos are cached for a year). Built assets live in `public/build` with hashed names and are cached for a year on Vercel routes. Fonts and scripts load from the same origin only (CSP).

**Diagnostic steps:** Use the browser Network tab. Locally, the repository has a script for timing:

```powershell
node tests\e2e\measure-performance.mjs
```

> **Requires confirmation:** the script's expected arguments and server prerequisites. Read the top of `tests/e2e/measure-performance.mjs` before running it.

## Code changes not visible on the container

**Symptoms:** You edit PHP on a running container and nothing changes.

**Cause:** `opcache.validate_timestamps = 0` in `docker/php.ini`. OPcache never checks files again until the process restarts. Config, routes and views are also cached by `php artisan optimize`.

**Resolution:** Redeploy or restart the service. Do not edit files inside a running production container.

## Database growth and slow queries

**Tables that grow without cleanup:** `activity_logs` (never pruned), `sessions`, `cache`, `jobs`, `failed_jobs`, `notifications`, `verification_codes`. No scheduled prune job exists (`routes/console.php` is empty apart from `inspire`).

**Diagnostic steps:**

```powershell
php artisan tinker --execute="dump(DB::table('activity_logs')->count(), DB::table('sessions')->count(), DB::table('failed_jobs')->count());"
```

**Resolution:** For old sessions, rely on `SESSION_LIFETIME` and Laravel's session garbage collection. For others plan manual cleanup (see [database maintenance](../12-maintenance/database-maintenance.md)).

> **Recommended, not implemented:** a scheduled cleanup of expired verification codes and old sessions, and retention rules for the audit log.

## Scaling signals and next steps

Move sessions, cache and queue to Redis (`REDIS_*` variables exist in `.env.example`), run a separate worker service, add web instances and use S3 storage. These steps are listed in the deployment guide. See [scaling considerations](../09-deployment-and-operations/scaling-considerations.md).

## Quick reference

| Symptom | First check |
| --- | --- |
| First hit takes about a minute | Cold start |
| Every page slow after deploy | Database connection, free database expired |
| Report PDF slow | Range and sections |
| Dashboard figure old | `METRICS_CACHE_TTL` |
| Upload slow | Photo size and batch size |
| Page slow only for one list | Eager loading, search term |
