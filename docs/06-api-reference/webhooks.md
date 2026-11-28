# Webhooks

> **Audience:** Developers, integrators, operators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [API integrations](api-integrations.md), [API overview](api-overview.md), [Email preferences and unsubscribe](../02-user-guide/feature-guides/email-preferences-and-unsubscribe.md), [Health checks](../09-deployment-and-operations/health-checks.md)

The Hub has no webhooks. This is true for both API versions (`v1` and `v2`). It does not send event notifications to other systems, and it has no endpoint where a third party can register a callback. There is also no inbound webhook receiver for payment, mail-delivery or source-control events.

## What exists instead

Two machine-to-machine web endpoints exist. Neither is a webhook in the usual sense (a configurable callback for application events).

| Endpoint | Direction | Caller | Purpose |
| --- | --- | --- | --- |
| `POST /unsubscribe/{user}` (`site.unsubscribe.store`) | Inbound | Mail providers such as Gmail and Yahoo | RFC 8058 one-click unsubscribe |
| `GET /up` | Inbound | Render, the Docker `HEALTHCHECK`, uptime monitors | Liveness check |

## RFC 8058 one-click unsubscribe

**Simple explanation:** every announcement e-mail contains an unsubscribe link. Gmail and Yahoo show an "Unsubscribe" button next to the sender name. When a reader presses it, the mail provider sends a request to the Hub in the background. The Hub removes the reader from announcement mail.

**Technical explanation:** [`TemplatedMail::headers()`](../../app/Mail/TemplatedMail.php) adds two headers when an unsubscribe URL is set:

```text
List-Unsubscribe: <https://uew-innovation-hub.onrender.com/unsubscribe/42?signature=...>
List-Unsubscribe-Post: List-Unsubscribe=One-Click
```

The mail provider then sends `POST` to the URL in `List-Unsubscribe` with the body `List-Unsubscribe=One-Click` (form-encoded).

| Property | Value |
| --- | --- |
| Route | `POST /unsubscribe/{user}`, name `site.unsubscribe.store` |
| Controller | [`UnsubscribeController@store`](../../app/Http/Controllers/Site/UnsubscribeController.php) |
| Middleware | `signed`, `throttle:20,1` (20 per minute). The route is in the `web` group, so `SecurityHeaders` applies. |
| Authentication | The URL signature, made with `APP_KEY`. No session, no CSRF token. |
| CSRF | Exempt. `bootstrap/app.php` calls `validateCsrfTokens(except: ['unsubscribe/*'])`. |
| Path parameter | `user`, digits only (`whereNumber('user')`), the member's id |
| Body | Provider sends `List-Unsubscribe=One-Click`. The code checks `input('List-Unsubscribe') === 'One-Click'`. |
| Effect | `MemberService::unsubscribe($user)` turns off the member's announcement e-mail opt-in (`users.marketing_opt_in`). |
| Success response, one-click request | `204 No Content` with an empty body |
| Success response, browser form post | HTML page `site.unsubscribed` (status 200) |
| Invalid or expired signature | `403` with the `auth.link-expired` page (from the `InvalidSignatureException` handler in `bootstrap/app.php`) |
| Over the rate limit | `429` |

The matching `GET /unsubscribe/{user}` shows a confirmation page and changes nothing. This stops mail scanners that pre-fetch links from unsubscribing people by accident.

Example (what a provider sends; do not run it against a real member):

```bash
curl -X POST \
  -d "List-Unsubscribe=One-Click" \
  "https://uew-innovation-hub.onrender.com/unsubscribe/42?signature=<signature-from-the-mail>"
```

```powershell
Invoke-WebRequest -Method Post -Body @{ 'List-Unsubscribe' = 'One-Click' } `
  -Uri 'https://uew-innovation-hub.onrender.com/unsubscribe/42?signature=<signature-from-the-mail>'
```

> **Warning:** a valid signed URL unsubscribes the member it names. Treat these URLs as secrets. They are signed with `APP_KEY`, so changing `APP_KEY` invalidates every unsubscribe link already sent.

Points to know:

- The link is created in `SendBroadcast` ([source](../../app/Jobs/SendBroadcast.php)) with `URL::signedRoute('site.unsubscribe', ['user' => id])`. It has no `expires` value, so it never times out. The signature covers the URL, so the same link works for the `POST`.
- The unsubscribe changes the opt-in flag only. It does not delete the account or stop transactional mail such as verification codes.
- Invalid signatures are rejected before the controller runs.

## Health endpoint

`GET /up` is Laravel's built-in health route, enabled in [`bootstrap/app.php`](../../bootstrap/app.php) with `health: '/up'`.

| Property | Value |
| --- | --- |
| Used by | `healthCheckPath: /up` in `render.yaml`, and the `HEALTHCHECK` in the `Dockerfile` (`curl -fsS http://127.0.0.1:${PORT}/up`, every 30 seconds, 5-second timeout, 3 retries) |
| Authentication | None |
| Success | `200` with an HTML status page |
| Failure | A 5xx when the framework cannot boot |
| What it checks | That the application boots. It does not test the database, the queue worker or storage. |

See [health-checks.md](../09-deployment-and-operations/health-checks.md) for operational use.

## Outbound webhooks

None. The code has no HTTP client usage (`Http::` facade) under `app/`. Event e-mail goes through the mail transport, not through callbacks. Broadcast e-mail is a feature for members, not an integration point. See [api-integrations.md](api-integrations.md).

> **Recommended, not implemented:** if partners need to react to new programmes or publications, add signed outbound webhooks (HMAC header, retry through the existing database queue). For now they can poll `/api/v2/programmes?updated_since=...` and `/api/v2/news?updated_since=...` and send `If-None-Match`, within the 60-requests-per-minute limit. See [rate-limiting.md](rate-limiting.md). `v2` has no tombstones, so polling cannot report deleted or unpublished records.
