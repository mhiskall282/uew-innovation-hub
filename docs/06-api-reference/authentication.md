# API authentication

> **Audience:** Developers, integrators, security reviewers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [API overview](api-overview.md), [Rate limiting](rate-limiting.md), [Authentication architecture](../04-architecture/authentication-architecture.md), [Data protection](../08-security/data-protection.md)

Both versions of the open data API (`/api/v1` and `/api/v2`) are public and unauthenticated by design. There are no API keys, tokens, OAuth flows or sessions. Any client can call them. The only control on volume is the per-IP [rate limit](rate-limiting.md). This page lists what that means, which data the API deliberately leaves out, and a recommended (not implemented) way to add authenticated endpoints later.

## What this means

| Question | Answer |
| --- | --- |
| Do I need an `Authorization` header? | No. It is ignored. |
| Are cookies or sessions needed? | No. The `api` route group does not start a session. |
| Is there a CSRF token? | No. Both versions are `GET` and `HEAD` only and change no data. Other methods return `405`. |
| Which permissions apply? | None. The routes in [`routes/api.php`](../../routes/api.php) use only the `api.version` and `throttle:api` middleware. No `auth`, `can:` or role checks. |
| Do staff or members see more through the API? | No. The response is the same for everyone. |
| Is Laravel Sanctum installed? | Not configured for the API. No token authentication exists. See the recommendation below. |
| Can v1 and v2 return `401` or `403`? | The handler maps those exceptions to the error envelopes ([error-codes.md](error-codes.md)), but no current route raises them. |

> **Warning:** do not embed secrets in clients of this API. It needs none, and anything in a browser app is visible to users.

Both versions send CORS headers that allow any origin (`Access-Control-Allow-Origin: *`, no credentials). Browser code on any site can read the API.

## Data that is included

| Endpoint | Data published (v1 and v2 unless noted) |
| --- | --- |
| `/stats` | Counts and sums over public projects, plus the number of organisations and publications |
| `/projects` | Public projects: title, summary, focus area, status, dates, beneficiary numbers, grant amount, lead researcher, department, partner names and roles, report download URL |
| `/publications` | Title, type, authors, abstract, publish date, journal, DOI URL, download count, document URL, linked public project |
| `/news` | Published posts, with the author's display name |
| `/partners` | Organisation name, type, website, count of public projects |
| `/programmes` | Published and closed programmes, including funding amount, dates and the apply URL |
| `/community` | Members who turned on a public profile and have XP above 0: name, affiliation, XP, level, badge titles. v1 returns the top 25. v2 returns up to `limit` (default 25, maximum 100). |
| `/albums` (v2 only) | Published albums with a cover, event date, location, linked visible programme, photo URLs |
| `/reference` (v2 only) | Filter values: focus areas, statuses, types, categories |

## Data that is excluded

The exclusions come from query scopes in the controllers and the field lists in the resource classes (both versions).

| Excluded data | How it is excluded |
| --- | --- |
| Internal projects (`projects.is_public = false`) | `Project::public()` on lists. `show` returns `404` when `is_public` is false. `/stats` counts public projects only. |
| Project `created_by`, `legacy_id`, `is_public`, `report_file_path` | Not in either `ProjectResource`. Only a `report_url` link is given when a report exists. |
| Partner contact e-mail | Not in either `PartnerResource` (a test asserts it never appears). |
| Draft, scheduled and soft-deleted posts | `Post::published()` and an `isPublished()` check on `show`. |
| Post cover image path and author e-mail | Not in the resources. Only the author's `name` is loaded. |
| Draft and archived programmes | `Program::visible()` and an `isVisible()` check on `show`. |
| Programme application form, capacity and applicant counts | Not in either `ProgramResource` or `ProgrammeResource`. The capacity count is used only to compute `is_accepting_applications`. |
| Applications, legacy grant applications, contact messages, broadcasts, progress reports, audit log | No API route reads them. |
| Member e-mail, phone, date of birth, gender, region, disability, education level, bio, interests | The community endpoint selects only `id`, `name`, `affiliation`, `xp` and badge fields. |
| Members who did not opt in, and staff accounts | `User::onLeaderboard()` (member role, active account, public profile on, XP above 0). |
| Unpublished albums and photos whose files are missing | `Album::published()`, `is_published` check on `show`, and `AlbumPhoto::isAvailable()`. |
| Publication PDF paths | Only `document_url` is returned, and only when the publication has a document. |

Points to know:

- A publication linked to an internal project is still listed, with `project: null`. The publication itself is public by design.
- The community list shows real display names and badge titles. A member controls this with the "public profile" setting. See [badges and leaderboard](../02-user-guide/feature-guides/badges-and-leaderboard.md).
- Document and photo URLs point at web routes (`/documents/...`, `/media/photos/...`) that stream files. They are not part of the JSON API. See [api-integrations.md](api-integrations.md).
- `v2` has no tombstones, so a client cannot learn from the API that a record was unpublished.

## Abuse controls that exist

- Rate limit of 60 requests per minute per IP address (`API_RATE_LIMIT_PER_MINUTE`). Behind Render's proxy, `TRUSTED_PROXIES=*` makes Laravel use the real client IP.
- Query validation. Unknown parameters are ignored. Known parameters with bad values return `422`.
- `v2` whitelists `sort` and `include`, and caps `limit` at 100. `v1` caps `per_page` at 50.
- Short shared caching (60 seconds by default), so a CDN can absorb repeated traffic.
- Security headers on every API response.

## Recommended, not implemented: authenticated endpoints

Nothing below exists in the code. It is a path to follow if the Hub needs endpoints that expose non-public data, such as a member's own applications, or higher limits for partners.

1. **Use Laravel Sanctum personal access tokens.** Install `laravel/sanctum`, add the `HasApiTokens` trait to `User`, and run its migration for `personal_access_tokens`. Tokens fit a server-to-server or mobile client. Cookie-based SPA authentication is not needed.
2. **Put authenticated routes in a separate group,** for example `Route::middleware(['auth:sanctum', 'throttle:api-authenticated'])->prefix('v2/me')`. Keep the public routes unchanged so cached public responses stay shareable.
3. **Use token abilities mapped to the existing `Permission` enum.** Check them with `can:` or Gate checks, as the staff workspace does, not with new ad-hoc rules.
4. **Never cache private responses publicly.** Send `Cache-Control: private, no-store` and `Vary: Authorization`.
5. **Rate-limit per token,** not per IP: a limiter that returns `Limit::perMinute(n)->by($request->user()->currentAccessToken()->id)`.
6. **Hash tokens at rest** (Sanctum does this), show the plain token once, support revocation, and log token creation and revocation in the audit log (`activity_logs`).
7. **Add new authenticated behaviour as additive `v2` endpoints, or as `v3`.** Do not change the public contract of `v1` or `v2`.
8. **Write tests** next to `tests/Feature/Api/`, including a test that anonymous requests get `401` in the `v2` envelope (`unauthenticated`), which the handler already supports.

Alternative for partner dashboards that only need higher limits: optional API keys sent in a header and checked by a small middleware, with the key's hash stored in a table. Sanctum tokens cover the same need with less custom code.
