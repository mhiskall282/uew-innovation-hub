# API Problems

> **Audience:** Developers, integrators · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [API overview](../06-api-reference/api-overview.md), [Endpoints](../06-api-reference/endpoints.md), [Error codes](../06-api-reference/error-codes.md), [Rate limiting](../06-api-reference/rate-limiting.md), [`docs/api.md`](../api.md)

The open data API lives under `/api/v1` (route names `api.v1.*`). It is read-only JSON, needs no sign-in and is limited to 60 requests per minute per IP (`RateLimiter::for('api')`). Routes are in `routes/api.php`. Errors use one JSON envelope that `bootstrap/app.php` builds for every `api/*` request.

## Error envelope

```json
{ "success": false, "message": "Resource not found." }
```

Validation errors add an `errors` object. The envelope is produced for any request whose path starts with `api/` or that asks for JSON.

| HTTP | `message` | Typical cause |
| --- | --- | --- |
| 404 | `Resource not found.` | Unknown slug, or the record is not public or not published |
| 422 | `The given data was invalid.` | Bad query parameter (for example `per_page=500`) |
| 429 | `Too many requests. Please slow down.` | Over 60 requests per minute from one IP |
| 401 | `Unauthenticated.` | Should not occur: no API route needs sign-in |
| 403 | `This action is unauthorized.` | Should not occur on current routes |
| 500 | `Server error.` (with `APP_DEBUG=true`, the exception text) | Server fault. Check the log |
| other | the exception's own message or `Request failed.` | HTTP exceptions |

## 404 for a record that exists

**Symptoms:** `/api/v1/projects/{slug}` returns 404 although the project is in the workspace.

**Possible causes:**

1. The project is **internal**. `ProjectController::show` aborts with 404 unless `is_public` is true.
2. The project was archived (soft-deleted).
3. News posts must be published (`published_at` in the past). Programmes must be `published` or `closed`.
4. The URL uses the numeric ID. Records bind by **slug** (`HasUniqueSlug::getRouteKeyName`).
5. The slug changed after the title was edited. Check the current slug in the workspace.

**Diagnostic steps:**

```powershell
Invoke-RestMethod https://uew-innovation-hub.onrender.com/api/v1/projects | ConvertTo-Json -Depth 3
```

```bash
curl -s https://uew-innovation-hub.onrender.com/api/v1/projects
```

**Resolution:** Set visibility to public, publish the item, or use the slug returned by the list endpoint.

## 429 "Too many requests"

**Symptoms:** JSON 429 with `Retry-After`.

**Causes:** More than 60 requests per minute from one IP. Shared IPs (offices, cloud functions) share the budget. Behind a proxy the app counts every client as the proxy unless `TRUSTED_PROXIES=*` is set, so the limit can be hit by all visitors together.

**Diagnostic steps:** Read `X-RateLimit-Limit`, `X-RateLimit-Remaining` and `Retry-After`:

```powershell
(Invoke-WebRequest https://uew-innovation-hub.onrender.com/api/v1/stats).Headers
```

**Resolution:** Wait for `Retry-After`. Cache responses on your side, since the server already sends `Cache-Control: public` with a short max-age (60 seconds per `docs/api.md`). Locally, `php artisan cache:clear` resets the counters.

**Prevention:** Poll less often. If a partner needs more, raise the limit in `AppServiceProvider::registerRateLimiters()`. That is a code change.

## 422 on list endpoints

**Symptoms:** `{"success":false,"message":"The given data was invalid.","errors":{"per_page":[...]}}`.

**Cause:** Parameters outside the rules. `per_page` must be 1 to 50 (`ApiController::MAX_PER_PAGE = 50`). Projects also validate `year` (1990 to 2100), `focus_area` (one of `config('hub.focus_areas')`), `status` (a `ProjectStatus` value) and `search` (max 100 characters).

**Resolution:** Fix the parameter. Valid focus areas: STEM, Educational Tech, Agriculture, Inclusive Education, Digital Humanities, Community Outreach.

## HTML instead of JSON

**Symptoms:** An error page in HTML for a URL you thought was the API.

**Cause:** The path does not start with `api/`. The public site is at `/`, the API at `/api/v1`. A missing `/v1` gives the JSON 404 for `api/*` but a typo such as `/apis/v1` gives the website 404 page.

**Resolution:** Send `Accept: application/json` and use the exact base URL `/api/v1`.

## First request is very slow or times out

**Cause:** The Render free service sleeps after 15 minutes. The first request wakes the container, which runs migrations and starts the worker. This can take about a minute.

**Resolution:** Retry with a longer timeout (for example 90 seconds). **Prevention:** For integrations, use a paid plan or expect a warm-up. See [performance problems](performance-problems.md#render-cold-start).

## CORS errors in a browser app

**Symptoms:** "blocked by CORS policy" when calling the API from another site's JavaScript.

**Facts:** The repository has no `config/cors.php`, so Laravel's built-in default applies (`vendor/laravel/framework/config/cors.php`): paths `api/*`, all origins, all methods. Cross-origin reads of `/api/v1/*` should work.

**Possible causes:** The request goes to a non-API path, a proxy strips headers, or the response is an error from a layer before Laravel (gateway timeout during a cold start has no CORS headers, and the browser reports it as CORS).

**Diagnostic steps:** Check the status code in the browser Network tab. Call the URL with curl and look for `Access-Control-Allow-Origin` when you send an `Origin` header:

```bash
curl -i -H "Origin: https://example.org" https://uew-innovation-hub.onrender.com/api/v1/stats
```

**Resolution:** Retry after the service is awake. If a deliberate restriction is needed, publish a `config/cors.php` (code change).

## Stale data

**Symptoms:** A change in the workspace does not show in the API at once.

**Causes:** The API response is cached for about 60 seconds by browsers and shared caches. Aggregated numbers (`/api/v1/stats`) come from `MetricsService`, cached for `METRICS_CACHE_TTL` (default 300 seconds).

**Resolution:** Wait. Locally, `php artisan cache:clear`.

## Tests for the API

```powershell
php artisan test tests\Feature\Api
```

```bash
php artisan test tests/Feature/Api
```

See [API testing](../10-testing-and-quality/api-testing.md).

## Prevention checklist

- Use slugs from the list endpoint, not IDs.
- Respect `Retry-After`.
- Keep `per_page` at 50 or lower.
- Treat the API as public data only. It never returns member personal data. The community endpoint lists only members who chose to appear.
