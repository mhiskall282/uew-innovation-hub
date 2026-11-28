# Performance Maintenance

> **Audience:** Developers, maintainers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [../11-troubleshooting/performance-problems.md](../11-troubleshooting/performance-problems.md), [../09-deployment-and-operations/scaling-considerations.md](../09-deployment-and-operations/scaling-considerations.md), [database-maintenance.md](database-maintenance.md), [technical-debt-register.md](technical-debt-register.md)

This page lists what already protects performance and what to watch. No performance testing or profiling tool is configured in the repository.

## Existing measures

| Measure | Where |
| --- | --- |
| Dashboard metrics cached for `METRICS_CACHE_TTL` seconds (default 300) | [`config/hub.php`](../../config/hub.php) |
| Open data API responses cached for `API_CACHE_SECONDS` (default 60), with weak ETag and 304 | [`config/api.php`](../../config/api.php) |
| API rate limit `API_RATE_LIMIT_PER_MINUTE` (default 60) per IP | `config/api.php`, `AppServiceProvider` |
| API v2 cursor pagination, max `limit` 100 | `config/api.php` |
| Programme capacity from one `withCount` subquery, no per-row `COUNT` (TD-R08) | `Program::scopeWithActiveApplicationsCount()` |
| Vite production build with hashed assets | `npm run build` in the `Dockerfile` |
| Upload limit `MAX_UPLOAD_KB` (default 20480) | `config/hub.php` |

## What to check regularly

1. **N+1 queries.** When you add a list page, eager-load relations (`with()`) and use `withCount()`. A feature test that counts queries is a good guard.
2. **Table size.** Audit log, notifications and sessions grow (see [database-maintenance.md](database-maintenance.md)).
3. **Queue lag.** The worker runs in the web container (TD-006). If broadcasts are slow, look at `jobs` row count and `failed_jobs`.
4. **Cold starts.** The Render free plan sleeps idle services, so the first request is slow. That is a plan limit, not a code fault.
5. **File storage.** Large uploads (up to 25 MB per file in `docker/php.ini`) and galleries should live on S3-compatible storage (`DOCUMENTS_DISK=s3`, `GALLERY_DISK=s3`).
6. **Config caching.** Production should run `php artisan config:cache`, `route:cache`, `view:cache`. Check `docker/entrypoint.sh` for what it does. Remember TD-004 if you cache config.

## When to scale

| Symptom | First action |
| --- | --- |
| Slow pages under load | Add indexes after `EXPLAIN`, then move cache and sessions to Redis |
| Slow e-mail or broadcasts | Separate worker service (`RUN_QUEUE_WORKER=false` on web) |
| High memory | Check worker `--memory=128` flag and `memory_limit` in `docker/php.ini` |
| Many API clients | Raise `API_CACHE_SECONDS`, put a CDN in front |

## Recommended, not implemented

- Query-count assertions in tests for list pages.
- A load test (see [../10-testing-and-quality/performance-testing.md](../10-testing-and-quality/performance-testing.md)).
- Application performance monitoring.

> **Requires confirmation:** which `artisan` cache commands `docker/entrypoint.sh` runs on start. Read the file before relying on item 6.
