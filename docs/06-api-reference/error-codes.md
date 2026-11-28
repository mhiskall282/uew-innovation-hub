# API error codes

> **Audience:** Developers, integrators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [API overview](api-overview.md), [Rate limiting](rate-limiting.md), [Request and response examples](request-response-examples.md), [API problems](../11-troubleshooting/api-problems.md)

The two API versions report errors in different JSON shapes. `v1` keeps its original shape. `v2` adds a stable, machine-readable `code`. Both are produced by one exception handler in [`bootstrap/app.php`](../../bootstrap/app.php).

## How errors are chosen

`shouldRenderJsonWhen` forces JSON for any path starting with `api/`. The handler maps the exception to a status and message:

| Exception | Status | Message |
| --- | --- | --- |
| `ValidationException` | 422 | `The given data was invalid.` (field messages go in `errors` or `details`) |
| `ModelNotFoundException`, `NotFoundHttpException` | 404 | `Resource not found.` |
| `AuthenticationException` | 401 | `Unauthenticated.` |
| `AuthorizationException` | 403 | `This action is unauthorized.` |
| `ThrottleRequestsException` | 429 | `Too many requests. Please slow down.` |
| Any other HTTP exception | its own status | its own message, or `Request failed.` |
| Anything else | 500 | `Server error.` (the real message only when `APP_DEBUG` is true) |

Headers of the exception are kept, so `429` carries `Retry-After` and `405` carries `Allow`. If a path starts with `api/v2`, the `v2` envelope is used. Every other API path, including `/api`, `/api/v1/...` and unknown paths such as `/api/v3/x`, gets the `v1` envelope.

## v2 envelope

```json
{
  "error": {
    "status": 422,
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "details": { "limit": ["The limit field must be between 1 and 100."] }
  }
}
```

`details` is present only when there is something to say (validation errors). Match on `error.code`, not on `message`.

### v2 code table

| `code` | HTTP status | When it occurs |
| --- | --- | --- |
| `bad_request` | 400 | A `400` raised by the framework, for example a malformed request. No endpoint raises it deliberately. |
| `unauthenticated` | 401 | An `AuthenticationException`. No public endpoint needs authentication, so you should not see it today. |
| `forbidden` | 403 | An `AuthorizationException` or a `403` abort. Not used by current endpoints. |
| `not_found` | 404 | Unknown path under `/api/v2`, unknown slug, or a record that is not public (hidden project, draft programme, unpublished post or album). |
| `method_not_allowed` | 405 | A method other than `GET` or `HEAD`. The response has `Allow: GET, HEAD`. |
| `validation_failed` | 422 | A query parameter fails validation: `limit` outside 1 to 100, unknown `sort`, `updated_since` not a date, `year` outside 1990 to 2100, unknown enum or list value, unknown `include` on `/projects`. `details` maps each field to its messages. |
| `rate_limited` | 429 | More requests than the per-minute limit. `Retry-After` gives the seconds to wait. |
| `server_error` | 500 | Unhandled exception. |
| `service_unavailable` | 503 | A `503`, for example the framework's maintenance mode. |
| `http_error` | any other | Any HTTP status without its own code (for example 408, 409, 410, 413, 414, 418, 502, 504). |

The `status` field in the body always equals the HTTP status.

## v1 envelope

```json
{ "success": false, "message": "The given data was invalid.", "errors": { "per_page": ["The per page field must be between 1 and 50."] } }
```

`errors` is present only for validation failures. There is no `code`, so clients must use the HTTP status. Typical statuses:

| Status | Cause |
| --- | --- |
| 404 | Unknown path or slug, or a hidden record. Body: `{ "success": false, "message": "Resource not found." }` |
| 405 | Wrong method. The message names the method and the supported ones. |
| 422 | A filter fails validation (`per_page` outside 1 to 50 on lists that validate it, `year`, `status`, `type`, `focus_area`, `availability`, `search` longer than 100). |
| 429 | Rate limit. |
| 500 | Server error. |

## What does not produce an error

- An invalid `cursor` on `v2` is not rejected. It returns the first page.
- An unknown `include` on `v2` endpoints other than `/projects` is ignored.
- A non-numeric `page` on `v1` falls back to page 1.
- `v1` partners clamp `per_page` instead of returning `422`.

## Handling errors in a client

| Status | Action |
| --- | --- |
| 304 | Not an error. Use your cached copy. |
| 404 | Drop or hide the record. During a sync, a detail `404` means the record is gone or unpublished. |
| 422 | Fix the request. Do not retry unchanged. |
| 429 | Wait `Retry-After` seconds, then retry with backoff. See [rate-limiting.md](rate-limiting.md). |
| 500, 502, 503, 504 | Retry with exponential backoff and a limit. The live site runs on Render's free plan and may be waking up. |

Error responses use `Cache-Control: no-cache, private` and carry no ETag.
