# Performance Testing

> **Audience:** Developers, operators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [testing-strategy](testing-strategy.md), [end-to-end-testing](end-to-end-testing.md), [api-testing](api-testing.md), [scaling considerations](../09-deployment-and-operations/scaling-considerations.md), [performance problems](../11-troubleshooting/performance-problems.md)

Performance checks in this project are small. There is one browser measurement script, one framework guard against N+1 queries, and two tests that cap the number of queries on a list. There is no load test and no performance budget in CI. This page describes each existing check, then lists recommended work (not implemented).

## Existing checks

### `tests/e2e/measure-performance.mjs` (lab measurement, manual)

File: `../../tests/e2e/measure-performance.mjs`. It is a Node script, not a Playwright spec. CI does not run it, and it has no pass or fail threshold. It prints a table.

| Item | Detail |
| --- | --- |
| Pages | `/`, `/programmes`, `/projects`, `/news`, `/community`, `/sign-in` |
| Browser | Chromium (or Edge with `E2E_CHANNEL=msedge`), a fresh context per page (cold cache) |
| Viewport | 390 x 844, device scale factor 2 (phone) |
| Throttling | Off by default. `THROTTLE=1` turns on CDP network conditions (150 ms latency, 1.6 Mbit/s down, 750 Kbit/s up) and 4x CPU slowdown |
| Wait | `load` event, then 1.5 s so that late layout shifts are recorded |
| Metrics per page | `ttfb` (navigation `responseStart`, ms), `lcp` (largest contentful paint, ms), `load` (load event end, ms), `cls` (cumulative layout shift, ignoring shifts after input), `requests` (response count), `kb` (transferred size from `content-length`, or body size when the header is missing) |
| Output | `console.table` with one row per page |

It needs an app that is already running with demo data. It does not start a server.

```powershell
php artisan serve                                   # terminal 1, default http://127.0.0.1:8000
node tests/e2e/measure-performance.mjs http://127.0.0.1:8000        # terminal 2
$env:THROTTLE = '1'; node tests/e2e/measure-performance.mjs http://127.0.0.1:8000; Remove-Item Env:THROTTLE
$env:E2E_CHANNEL = 'msedge'; node tests/e2e/measure-performance.mjs; Remove-Item Env:E2E_CHANNEL
```

```bash
php artisan serve
node tests/e2e/measure-performance.mjs http://127.0.0.1:8000
THROTTLE=1 node tests/e2e/measure-performance.mjs http://127.0.0.1:8000
```

Limits of the measurement:

- Lab data from one machine, one run per page. Variation between runs is large. Run it three times and compare medians.
- The default base URL is `http://127.0.0.1:8000`. Pointing it at the live Render site measures a free-plan instance, which may be asleep. The first request then shows a long TTFB that is not a code problem.
- `php artisan serve` is a development server. Numbers are not comparable with production (Apache in Docker).
- It does not sign in, so portal and workspace pages are not measured.
- `public/build` must exist (`npm run build`), or pages load without styles and the numbers mean nothing.

### `Model::preventLazyLoading` (N+1 guard)

`app/Providers/AppServiceProvider.php` calls `Model::preventLazyLoading(! $this->app->isProduction())`. In every environment except production, loading a relation that was not eager-loaded throws `LazyLoadingViolationException`. Because the PHPUnit and Playwright runs use `APP_ENV=testing` and `APP_ENV=local`, any page that triggers an N+1 query fails its test with an exception (HTTP 500). This is the main performance regression guard, and it acts on every feature test, the route smoke test and the browser journeys. In production the guard is off, so users never see the exception.

Limit: it catches a missing `with()` on a relation, not a slow query, a missing index or a large result set. A list with one row will not reveal an N+1 that needs many rows, so tests that matter use several rows (see below).

### Query-count assertions

Two tests count queries with `DB::enableQueryLog()` and assert an upper bound. Both guard the programme capacity calculation (`Program::scopeWithActiveApplicationsCount()`, added in commit `d1296b1`), which uses one `withCount` subquery instead of one COUNT per row.

| Test | Setup | Assertion |
| --- | --- | --- |
| `Api/ApiV2Test::test_programmes_report_capacity_without_a_query_per_row` | 2 published programmes (capacity 1 and 5) plus 1 draft, 1 submitted application. `GET /api/v2/programmes?availability=open` | At most **4** queries. Also checks `is_accepting_applications` and `availability` values |
| `Workspace/OperationalFixesTest::test_programme_lists_do_not_count_applications_once_per_programme` | 6 published programmes, 1 application. `GET /api/v1/programmes?per_page=50` | At most **4** queries. Also checks that `Program::isAcceptingApplications()` still works without the preloaded count |

The bound 4 is a fixed constant. It allows a few framework queries (for example pagination count, settings) and fails if the work grows with the number of rows. If you add a legitimate query to the endpoint, raise the bound in the same commit and say why.

### Other behaviour that affects speed (tested for correctness, not speed)

| Test | What it asserts |
| --- | --- |
| `PublicApiTest::test_responses_are_cacheable_and_rate_limited`, `ApiV2Test::test_responses_support_etags_caching_and_shared_rate_limits` | `Cache-Control: public, max-age=60`, ETag and 304 on API lists (`API_CACHE_SECONDS`, default 60) |
| `GalleryTest::test_public_gallery_pages_homepage_and_cached_photos` | Public gallery photos carry long-lived `immutable` caching; the private route uses `no-store, private` |
| `GalleryTest::test_officer_creates_an_album_and_uploads_photos_which_are_resized_and_stripped` | Uploads are resized to 1600 px wide and a 640 px thumbnail is made |
| `e2e/responsive.spec.ts`, `expectImagesLoaded` | Layout does not overflow, images load. Not a speed check |

## What is not measured

| Area | State |
| --- | --- |
| Server response time under load | Not measured |
| Database query time and slow queries | Not measured. No `EXPLAIN` checks, no slow-query log review in the repository |
| Queue and mail throughput (broadcasts to many members) | Not measured. `SendBroadcast` has `tries = 1` and the queue worker runs inside the container |
| PDF export time (`ReportExportService`) | Only correctness is tested (`ReportExportTest`) |
| Large uploads (10 photos of 10 MB each) | Limits are set in `docker/php.ini` (post_max_size 110M, max_file_uploads 12), not tested |
| Web Vitals in CI | None. The script above is manual |
| Behaviour on PostgreSQL vs SQLite | The `pgsql` CI job runs functional tests only |
| Render free plan cold start | Not measured. See [scaling considerations](../09-deployment-and-operations/scaling-considerations.md) |

## Recommended, not implemented

### Load testing

Nothing in the repository runs a load test. The live site is a Render free plan (single instance, ephemeral disk), so a load test against it can degrade or crash it for real users. **Only load-test a copy you own** (a local Docker container or a separate Render service), never the live site without the owner's permission.

Suggested plan:

1. **Tool.** k6 (single binary, JavaScript scripts) or Apache Bench for quick checks. Neither is installed or configured.
2. **Scenarios.** (a) Anonymous browsing: `/`, `/programmes`, `/projects`, `/news` with a realistic mix. (b) API reads: `/api/v2/projects?limit=20`, with and without `If-None-Match`, to confirm 304s are cheap. (c) Sign-in and portal dashboard for 20 to 50 virtual users. (d) A workspace report export (PDF) by 2 users at once.
3. **Targets to agree first.** For example p95 under 500 ms for cached public pages and error rate under 1 percent. The project has not set any target, so pick them with the Hub team.
4. **Rate limit awareness.** The `api` limiter allows 60 requests per minute per IP by default (`API_RATE_LIMIT_PER_MINUTE`), contact form 3, registrations 5. A single-IP load test will hit 429 quickly. Raise the limit on the test copy only.
5. **Data.** Seed the test copy with demo data plus a generated set of a few thousand projects, members and applications (a throwaway seeder, not committed to production).
6. **Record.** Save results next to a commit hash, and compare after each change to queries, caching or the Docker image.

### Other recommended work

- Add a query-count test for each list page and each list API endpoint that has a relation (projects with partners, news, publications, workspace applications and members lists). Use more than one row in the fixture.
- Add a Playwright test that fails when LCP or CLS on the home page exceeds an agreed number, in Chromium only. Start by recording numbers, not failing builds.
- Run `measure-performance.mjs` in CI against the e2e server and upload the table as an artefact.
- Add `EXPLAIN` checks (PostgreSQL) for search and filter queries once data grows. Review the indexes listed in `../07-database/indexes-and-constraints.md`.
- Time `SendBroadcast` with a few hundred fake recipients (`Mail::fake()`) to find the point where the single in-container worker is too slow.
