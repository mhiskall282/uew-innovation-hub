# API rate limiting and caching

> **Audience:** Developers, integrators, operators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [API overview](api-overview.md), [Error codes](error-codes.md), [Environment configuration](../09-deployment-and-operations/environment-configuration.md), [Scaling considerations](../09-deployment-and-operations/scaling-considerations.md)

Every API route, in both versions and `GET /api`, goes through one limiter named `api`. By default it allows 60 requests per minute per client IP address. Past that, the server answers `429` until the minute window ends. Successful responses are also cacheable for 60 seconds, and clients can revalidate with ETags.

## The limiter

Defined in `registerRateLimiters()` in [`app/Providers/AppServiceProvider.php`](../../app/Providers/AppServiceProvider.php):

```php
RateLimiter::for('api', fn (Request $request) => Limit::perMinute(max(1, (int) config('api.rate_limit_per_minute', 60)))->by($request->ip()));
```

[`routes/api.php`](../../routes/api.php) attaches it with `throttle:api` to `GET /api`, to the `v1` group and to the `v2` group.

| Property | Value |
| --- | --- |
| Limit | `API_RATE_LIMIT_PER_MINUTE`, default 60 (`config/api.php` key `rate_limit_per_minute`). Minimum 1. |
| Window | 1 minute |
| Key | Client IP address (`$request->ip()`) |
| Shared by | All 28 API routes. One counter per IP, not per endpoint and not per version. |
| Storage | The configured cache store. `CACHE_STORE=database` in `render.yaml` and `.env.example`, so counters live in the `cache` table. |
| Other limiters | None for the API. The `contact-messages`, `registrations` and `password-codes` limiters belong to web forms. |

To change the limit, set `API_RATE_LIMIT_PER_MINUTE` in the environment and redeploy. See [environment-configuration.md](../09-deployment-and-operations/environment-configuration.md).

A response served from a browser cache or a CDN never reaches the server and does not count. A `304` does reach the server and does count.

## Rate-limit headers

| Header | On | Meaning |
| --- | --- | --- |
| `X-RateLimit-Limit` | Throttled routes, every response | The per-minute limit (`60` by default). Asserted by the tests. |
| `X-RateLimit-Remaining` | Same | Requests left in the current window. |
| `Retry-After` | `429` only | Seconds until you may retry. |
| `X-RateLimit-Reset` | `429` only | Unix timestamp when the window resets. |

These are Laravel's standard throttle headers. Cross-origin browser JavaScript cannot read them, because the default CORS configuration exposes no response headers (see [API overview](api-overview.md#behaviour-common-to-both-versions)).

`429` bodies:

```json
{ "error": { "status": 429, "code": "rate_limited", "message": "Too many requests. Please slow down." } }
```

```json
{ "success": false, "message": "Too many requests. Please slow down." }
```

The first is `v2`, the second `v1`. A request to an unknown path such as `/api/v3/x` does not pass the limiter and has no rate-limit headers.

## Caching headers

| Version | Header on success | Source |
| --- | --- | --- |
| v1 | `Cache-Control: max-age=60, public, s-maxage=60` and a weak `ETag` | Constant `CACHE_SECONDS = 60` in [`V1/ApiController.php`](../../app/Http/Controllers/Api/V1/ApiController.php). `API_CACHE_SECONDS` is ignored by `v1`. |
| v2 | `Cache-Control: max-age=<API_CACHE_SECONDS>, public, s-maxage=<API_CACHE_SECONDS>` and a weak `ETag` | `respond()` in [`V2/ApiController.php`](../../app/Http/Controllers/Api/V2/ApiController.php). Default 60. |
| `GET /api` | Same `Cache-Control`, no ETag | [`VersionsController`](../../app/Http/Controllers/Api/VersionsController.php) |
| Errors | `Cache-Control: no-cache, private`, no ETag | Laravel default |

The ETag is `W/"<md5 of the JSON body>"`. A request with a matching `If-None-Match` gets `304 Not Modified` and no body, from either version. Public data such as `/stats` is also cached on the server for `METRICS_CACHE_TTL` seconds.

Because `public` and `s-maxage` are set, a CDN or shared proxy in front of the site may serve the same copy to all clients for 60 seconds. Only identical URLs share a cache entry. A CDN in front of Render would also change which IP Laravel sees. See [Client IP detection](#client-ip-detection).

## Client guidance

- **Budget your requests.** 60 per minute is one per second on average. A full sync of 1,000 projects with `limit=100` is 10 requests.
- **Use big pages.** `v2`: `limit=100`. `v1`: `per_page=50`.
- **Sync incrementally.** After the first sync, use `updated_since` (v2) so a refresh is usually one request.
- **Revalidate with ETags.** Store the `ETag` per URL and send `If-None-Match`. A `304` saves transfer. It still costs one request from your budget.
- **Respect `Cache-Control`.** Do not poll the same URL more often than every 60 seconds. The data is cached that long anyway.
- **Back off on `429`.** Wait `Retry-After` seconds. If the header is missing, use exponential backoff (1 s, 2 s, 4 s, up to about 60 s) with random jitter. Do not retry in a tight loop.
- **Back off on 5xx.** The live site runs on Render's free plan and can be slow to wake.
- **Watch `X-RateLimit-Remaining`** (server-side clients) and slow down before it hits 0.
- **Share one client per IP.** All clients behind one NAT or one server share one counter.
- **Reuse connections** (HTTP keep-alive).

PowerShell helper that waits on `429`:

```powershell
function Get-Hub($url) {
    for ($attempt = 1; $attempt -le 5; $attempt++) {
        try { return Invoke-RestMethod -Uri $url }
        catch {
            $resp = $_.Exception.Response
            if ($resp -and [int]$resp.StatusCode -eq 429) {
                $wait = 0
                $h = $resp.Headers['Retry-After']
                if ($h) { [int]::TryParse(($h | Select-Object -First 1), [ref]$wait) | Out-Null }
                if ($wait -lt 1) { $wait = [Math]::Pow(2, $attempt) }
                Start-Sleep -Seconds $wait
            } else { throw }
        }
    }
    throw "Gave up after 5 attempts: $url"
}
```

```bash
# bash: retry on 429 using Retry-After
curl -s --retry 5 --retry-delay 2 "$BASE/api/v2/projects?limit=100"
```

`curl --retry` also retries on `429` and honours `Retry-After`.

## Client IP detection

The limit is per IP, so the app must see the real client address. [`bootstrap/app.php`](../../bootstrap/app.php) reads `TRUSTED_PROXIES`, and `render.yaml` sets it to `*`. Without it, every request behind a load balancer would share the balancer's IP, and one busy client could block everyone. If another CDN is put in front of Render, check that the forwarded client IP is the one Laravel uses.

## Limits of this design

- The limit is per IP only. There are no per-client keys, so a heavy partner cannot be given a higher limit.
- `TRUSTED_PROXIES=*` trusts any `X-Forwarded-For` value that reaches the app. If the app were reachable without the Render proxy, a client could spoof its IP and avoid the limit.
- Counters are in the database cache table. Each throttled request writes to it, which costs a database operation per request.

> **Recommended, not implemented:** per-key limits for registered partners (see [authentication.md](authentication.md)) and a Redis cache store for the counters once traffic grows (`.env.example` mentions Redis as optional).
