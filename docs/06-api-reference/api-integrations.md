# API Integrations and Machine-Facing Endpoints

> **Audience:** Developers, integrators, operators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [API overview](api-overview.md), [Webhooks](webhooks.md), [Endpoints](endpoints.md), [Integration architecture](../04-architecture/integration-architecture.md), [Health checks](../09-deployment-and-operations/health-checks.md)

This page covers how other systems use the Hub. Part 1 describes consumers of the Open data API. Part 2 lists the non-API web endpoints that machines (crawlers, mail providers, health checkers, browsers acting as downloaders) call. Part 3 lists the outbound services the Hub itself calls.

## Part 1. Consuming the open data API

The API is public and needs no key ([authentication.md](authentication.md)). Use `v2` for new work ([api-overview.md](api-overview.md)).

| Use | Endpoints (v2) | Notes |
| --- | --- | --- |
| Show impact figures on another UEW site | `/stats` | Cached 60 seconds over HTTP, and on the server for `METRICS_CACHE_TTL` seconds (default 300). The ETag changes every second because of `meta.generated_at`, so do not rely on `304` here. |
| Embed a list of open programmes | `/programmes?availability=open` | Link to `links.html` for details and `links.apply` for applicants. |
| Publish the news feed elsewhere | `/news?sort=-published_at` | There is no RSS or Atom feed (no such route exists). |
| Research or reporting on projects and publications | `/projects`, `/publications` | Page with `meta.next_cursor`, `limit=100`. |
| Show the photo gallery | `/albums`, `/albums/{album}` | Use `thumb.url` in lists and `large.url` in a viewer. |
| Build filter menus | `/reference` | Avoids hard-coding focus areas, types and statuses. |
| Partner lists | `/partners` | No logo field exists. |

### Mobile apps

- Do a full sync once with a cursor loop, store records by `id`, and keep the time the sync started.
- Refresh with `updated_since=<last sync start>` and `If-None-Match` per URL. Deduplicate by `id`, because the filter is inclusive.
- Run a full resync now and then (for example weekly, or when the user pulls to refresh twice), because `v2` has no tombstones and cannot report unpublished records.
- Treat ids as opaque strings and money as decimal strings.
- Ignore unknown fields. Show an update prompt if `API-Version` or `GET /api` shows your version as `deprecated`, or if a `Sunset` header appears.
- Cache images by URL. Photo URLs include a `v` timestamp, so a changed photo gets a new URL.
- Do not call the API once per list row. A list response has everything the list needs.

### Partner dashboards and servers

- Run one scheduled job per data set (for example every 5 or 15 minutes), not one request per page view. All users behind one server share one rate limit of 60 per minute.
- Store the data in your own database and serve your users from it.
- Log `Retry-After` and back off on `429`. See [rate-limiting.md](rate-limiting.md).
- Send a descriptive `User-Agent`. The server does not require one.
- Do not scrape HTML pages when the API has the data.

### Caching proxies and CDNs

- Success responses are `Cache-Control: public, max-age=60, s-maxage=60` (set `API_CACHE_SECONDS` to change `v2`; `v1` is fixed at 60). A shared cache can serve them.
- Cache by full URL including the query string. Responses do not set `Vary`.
- The weak ETag lets a proxy revalidate with `If-None-Match`.
- Errors are `no-cache, private`, so proxies should not store them.
- If you put a CDN between clients and Render, keep `TRUSTED_PROXIES` correct so the limiter sees client IPs rather than the CDN's. See [rate-limiting.md](rate-limiting.md#client-ip-detection).
- Images from `/media/photos/...` are `public, max-age=31536000, immutable` for published albums. Safe to cache for a long time.

Guidelines for every integrator:

1. Cache on your side for at least 60 seconds.
2. Follow `links.next` (`v2`) or `meta.next_cursor` rather than building URLs by hand.
3. Ignore fields you do not recognise. New fields can be added to an existing version without a version bump. This policy is in `config/api.php`, not enforced by code.
4. Check `GET /api` or the `Deprecation` and `Sunset` headers to learn when a version retires.

There is no SDK, no Postman collection and no OpenAPI file in the repository.
## Part 2. Non-API web endpoints used by machines

These routes live in [`routes/web.php`](../../routes/web.php). They do not use the JSON envelope. They are not rate limited by `throttle:api`. `/unsubscribe` POST has `throttle:20,1`.

| Route | Name | Consumer | Behaviour |
| --- | --- | --- | --- |
| `GET /sitemap.xml` | `site.sitemap` | Search engines | XML sitemap (`application/xml`). Lists home, programmes, projects, publications, news, partners, gallery, community and contact pages, plus visible programmes (up to 500), public projects (1,000), published posts (1,000), published albums with a cover (500) and published CMS pages. Cached for one hour (`seo.sitemap` cache key) and sent with `Cache-Control: public, max-age=3600`. |
| `GET /robots.txt` | `site.robots` | Crawlers | Plain text. When `SEARCH_INDEXING=true` it disallows `/workspace`, `/portal`, `/notifications`, `/documents/`, `/unsubscribe/` and `/*/apply`, and points to the sitemap. When indexing is off (the default) it answers `Disallow: /` for everything. |
| `GET /media/photos/{photo}/{size}` | `media.photo` | Browsers, image proxies | Streams a gallery photo. `{photo}` is numeric and `{size}` is `large` or `thumb`. Photos in published albums return `Cache-Control: public, max-age=31536000, immutable`. Photos in unpublished albums are served only to signed-in staff allowed to view them, with `private, no-store`. Anyone else gets 404. The URL carries a `v` query value (the photo's `updated_at` timestamp) to bust caches. 2 album endpoints return these URLs as `large.url` and `thumb.url`. |
| `GET /documents/projects/{project}/report` | `site.documents.project-report` | Browsers, link checkers | Streams a project's PDF report inline. 404 unless the project is public and has a report. `{project}` is a slug. Linked from `report_url` in both API versions. |
| `GET /documents/publications/{publication}` | `site.documents.publication` | Browsers | Streams the publication PDF inline and increments `downloads_count` through `PublicationService::recordDownload`. 404 when there is no document. Linked from `document_url` in both API versions. |
| `GET /unsubscribe/{user}` | `site.unsubscribe` | People opening a mail link | Signed URL. Shows a confirmation page. Does not unsubscribe, so mail scanners that pre-open links cause no change. |
| `POST /unsubscribe/{user}` | `site.unsubscribe.store` | Mail providers and the confirmation page | Signed URL, `throttle:20,1`, exempt from CSRF. Unsubscribes the user. See [webhooks.md](webhooks.md). |
| `GET /up` | none | Render, the Docker `HEALTHCHECK`, uptime monitors | Laravel health route, configured as `health: '/up'` in `bootstrap/app.php`. Returns 200 when the framework boots. It does not query the database. |

Notes:

- `{user}` in the unsubscribe routes accepts digits only (`whereNumber`). The signature, not the id, authorises the call.
- `/documents/*` is marked `noindex` by `SecurityHeaders` and disallowed in `robots.txt`. Documents are resolved from the database record, never from a filename in the URL.
- On the free Render plan uploaded documents live on the container disk and are lost on redeploy unless `DOCUMENTS_DISK=s3` is set (comment in `render.yaml`). A `report_url` or `document_url` can then return 404 even though the API lists it. `ImportLegacyDocuments` (`hub:import-documents`) re-copies bundled legacy PDFs on each boot.
- `GET /up` was not in the generated `php artisan route:list` list. It is registered by the framework, not by `routes/web.php`.

## Part 3. Outbound integrations (what the Hub calls)

| Service | Purpose | Configured by |
| --- | --- | --- |
| SMTP or a mail API | Verification codes, notifications, broadcasts | `MAIL_*` variables, or the Site settings screen in the staff workspace (stored in the `settings` table, SMTP password encrypted with `APP_KEY`). `render.yaml` defaults to `MAIL_MAILER=log`, which writes mail to the log and sends nothing. |
| S3-compatible object storage | Documents (`DOCUMENTS_DISK=s3`) and gallery photos (`GALLERY_DISK=s3`) | `AWS_*` variables |
| PostgreSQL | Data store on Render | `DB_URL` |

The application code has no HTTP client calls to third-party APIs (no `Http::` facade usage was found under `app/`). `config/services.php` carries Laravel's stock entries for Postmark, Resend, SES and Slack. Nothing in `app/` uses the Slack entry.

## Part 4. Other deployment entry points

`vercel.json` and `api/index.php` form an alternative deployment on Vercel (PHP runtime `vercel-php@0.8.0`, all requests routed to `api/index.php`, `QUEUE_CONNECTION=sync`, `DOCUMENTS_DISK=s3`). The live site runs on Render. Its completeness as a deployment is not documented here.

> **Requires confirmation:** whether the Vercel deployment has been used and tested. Nothing in CI builds or tests it.
