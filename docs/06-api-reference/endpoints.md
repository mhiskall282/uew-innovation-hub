# API endpoints

> **Audience:** Developers, integrators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [API overview](api-overview.md), [Request and response examples](request-response-examples.md), [Error codes](error-codes.md), [Rate limiting](rate-limiting.md), the output of `php artisan route:list`

Reference for the 28 API routes: 1 version index, 12 under `v1` and 15 under `v2`. All are `GET` (also answer `HEAD`), need no authentication, and use the shared `throttle:api` limiter. Field lists come from the `toArray()` methods of the resource classes.

Base URL: `https://uew-innovation-hub.onrender.com`. Route names are shown so you can find them in `php artisan route:list --path=api`.

## Conventions used below

- Optional query parameters carry the exact Laravel validation rules from the controller.
- "Hidden record" means the record exists but is not public. The API returns `404`, not `403`.
- Status codes on every endpoint: `200`, `304` (matching `If-None-Match`), `405` (wrong method), `429` (rate limit), `500`. Lists and filtered endpoints can also return `422`. Detail endpoints can return `404`.
- Dates are `YYYY-MM-DD`. Timestamps are ISO 8601. In `v1` they use `toIso8601String()`, in `v2` the `ATOM` format. Both include an offset such as `+00:00`.

## Version index

| Method and path | Route name | Description |
| --- | --- | --- |
| `GET /api` | `api.versions` | Lists versions. |

Response (`data`): `name`, `current` (`"v2"`), `documentation` (URL), `versions[]` with `version`, `status` (`supported`, `current` or `deprecated`), `url`, `deprecated_at` (date or `null`), `sunset_at` (date or `null`). Not wrapped in `success`. No `API-Version` header (the route has no `api.version` middleware).

---

# API v1

Envelope: `{ "success": true, "data": ..., "meta": ..., "links": ... }`. Lists return `meta` (`current_page`, `per_page`, `total`, `last_page`) and `links` (`first`, `prev`, `next`, `last`). Detail and discovery responses have no `meta` unless noted.

Every v1 list accepts:

| Parameter | Rule | Default |
| --- | --- | --- |
| `per_page` | `nullable`, `integer`, `between:1,50` | 15 |
| `page` | Not validated. Laravel reads it as the page number. | 1 |

`422` body on a bad parameter: `{ "success": false, "message": "The given data was invalid.", "errors": { "<field>": ["..."] } }`.

## GET /api/v1 (`api.v1.index`)

Discovery document. `data`: `name`, `version` (`"v1"`), `documentation` (from `hub.api_docs_url`), `endpoints` (URLs for `stats`, `projects`, `publications`, `news`, `partners`, `programmes`, `community`). Codes: 200, 304.

## GET /api/v1/stats (`api.v1.stats`)

Headline figures for public projects. `data`: `projects`, `active_projects`, `beneficiaries_reached` (integers), `funding_secured` (float), `currency` (`"GHS"`), `partners`, `publications`. `meta.generated_at`. Source: `MetricsService::publicStats()` (cached). Codes: 200, 304.

## GET /api/v1/projects (`api.v1.projects.index`)

Public projects, newest `start_date` first, then `id` descending.

| Parameter | Rule |
| --- | --- |
| `search` | `nullable`, `string`, `max:100` |
| `focus_area` | `nullable`, `string`, `in:` `config('hub.focus_areas')` |
| `status` | `nullable`, enum `ProjectStatus` (`pending`, `active`, `completed`, `suspended`) |
| `year` | `nullable`, `integer`, `between:1990,2100` |
| `per_page` | see above |

`focus_area` values: `STEM`, `Educational Tech`, `Agriculture`, `Inclusive Education`, `Digital Humanities`, `Community Outreach`.

Item fields: `slug`, `title`, `summary`, `focus_area`, `status`, `start_date`, `end_date`, `beneficiaries` (`target`, `reached`, `progress_percent`), `grant_amount` (float or `null`), `currency`, `lead_researcher`, `department`, `partners` (`[{ name, role }]`), `report_url` (or `null`), `url`. Codes: 200, 304, 422.

## GET /api/v1/projects/{project} (`api.v1.projects.show`)

One public project. `{project}` is the slug. Same fields as the list item. Hidden project or unknown slug: `404`. Codes: 200, 304, 404.

## GET /api/v1/publications (`api.v1.publications.index`)

Newest `publish_date` first, then `id` descending.

| Parameter | Rule |
| --- | --- |
| `search` | `nullable`, `string`, `max:100` (matches title, authors, journal name) |
| `type` | `nullable`, `string`, `in:` `config('hub.publication_types')` |
| `per_page` | see above |

`type` values: `Research Paper`, `Policy Brief`, `Newsletter`, `Technical Report`, `Annual Report`.

Item fields: `slug`, `title`, `type`, `authors`, `abstract`, `publish_date`, `journal`, `doi_url`, `downloads`, `document_url` (or `null`), `project` (`{ slug, title }` only if the project is public, otherwise `null`). Codes: 200, 304, 422.

## GET /api/v1/publications/{publication} (`api.v1.publications.show`)

One publication by slug. Same fields. There is no visibility check beyond the record existing. Codes: 200, 304, 404.

## GET /api/v1/news (`api.v1.news.index`)

Published posts, newest `published_at` first. Drafts and scheduled posts are excluded.

| Parameter | Rule |
| --- | --- |
| `type` | `nullable`, enum `PostType` (`news`, `blog`, `announcement`) |
| `per_page` | see above |

Item fields: `slug`, `title`, `type`, `category`, `summary` (falls back to the first 200 characters of the stripped content), `author` (name), `is_featured`, `published_at`, `reading_minutes`, `url`. The `content` field is not in lists. Codes: 200, 304, 422.

## GET /api/v1/news/{post} (`api.v1.news.show`)

One published post by slug. Same fields plus `content`. Unpublished or scheduled post: `404`. Codes: 200, 304, 404.

## GET /api/v1/partners (`api.v1.partners.index`)

Partner organisations ordered by name. The contact e-mail is never returned. No parameters other than `per_page` (not validated here: values are clamped to 1 to 50 instead of returning `422`).

Item fields: `id` (integer), `name`, `type`, `website`, `public_projects` (count of public projects). Codes: 200, 304.

## GET /api/v1/programmes (`api.v1.programmes.index`)

Published and closed programmes. Draft and archived programmes never appear. Order: featured first, then `opens_at` descending, then `id` descending.

| Parameter | Rule |
| --- | --- |
| `search` | `nullable`, `string`, `max:100` (title, summary) |
| `type` | `nullable`, enum `ProgramType` (`grant`, `fellowship`, `incubation`, `competition`, `training`, `event`) |
| `focus_area` | `nullable`, `string`, `in:` `config('hub.focus_areas')` |
| `availability` | `nullable`, `in:open,upcoming,closed` |
| `per_page` | see above |

Item fields: `slug`, `title`, `type`, `type_label`, `focus_area`, `summary`, `availability` (a label: `Open`, `Closed` or `Opens <d Mon>`), `is_accepting_applications`, `is_featured`, `funding_amount` (float or `null`), `currency`, `location`, `opens_at`, `closes_at`, `starts_at`, `url`, `apply_url`. `description` and `eligibility` are in the detail response only. Codes: 200, 304, 422.

## GET /api/v1/programmes/{program} (`api.v1.programmes.show`)

One programme by slug. Same fields plus `description` and `eligibility`. Draft or archived: `404`. Codes: 200, 304, 404.

## GET /api/v1/community (`api.v1.community`)

Top 25 members who opted in to the public leaderboard, ordered by XP then id. No parameters. `data` is an array of: `rank`, `name`, `affiliation` (label or `null`), `xp`, `level` (name), `badges` (`[{ type, title, awarded_at }]`), `profile_url`. No e-mail, phone or demographic data. Codes: 200, 304.

---

# API v2

Envelope: `{ "data": ..., "meta": ..., "links": ... }`. Errors: `{ "error": { "status", "code", "message", "details" } }` (see [error-codes.md](error-codes.md)).

Every v2 list accepts these parameters, validated by `ApiController::validateList()`:

| Parameter | Rule | Notes |
| --- | --- | --- |
| `limit` | `nullable`, `integer`, `between:1,100` | Default 20 (`config('api.v2.default_limit')`). |
| `cursor` | `nullable`, `string`, `max:512` | Use `meta.next_cursor`. A bad value gives the first page. |
| `sort` | `nullable`, `string`, `in:` endpoint whitelist | Default is the first key of the whitelist. |
| `updated_since` | `nullable`, `date` | Returns records with `updated_at >= value`. Parsed with `Carbon::parse`. URL-encode `+` in offsets as `%2B`. |
| `include` | `nullable`, `string`, `max:200` | Comma list. Enforced on `/projects` only (allowed: `partners`). |

Default sort whitelist: `-id` (newest id first, the default) and `id`. News adds `-published_at` (default) and `published_at`.

List `meta`: `per_page`, `has_more`, `next_cursor`, `prev_cursor`. List `links`: `self` (full request URL), `next`, `prev` (`null` when absent).

Every record has `updated_at` and `links.self` (except partners, which have no `links`, and community members).

## GET /api/v2 (`api.v2.index`)

Discovery. `data`: `name`, `version` (`"v2"`), `documentation`, `endpoints` (URLs for `stats`, `reference`, `projects`, `publications`, `news`, `partners`, `programmes`, `albums`, `community`), `conventions` (`pagination`, `sync`, `ids`, `money`, `caching`, as strings). Codes: 200, 304.

## GET /api/v2/stats (`api.v2.stats`)

`data`: `projects`, `active_projects`, `beneficiaries_reached` (integers), `funding_secured` (`{ amount, currency }`), `partners`, `publications`. `meta.generated_at`. Because `generated_at` changes every second, the ETag rarely matches. Codes: 200, 304.

## GET /api/v2/reference (`api.v2.reference`)

Allowed filter values, so clients need not hard-code them. `data`: `focus_areas` (strings), `project_statuses` (`[{ value, label }]`), `publication_types`, `post_types` (`[{ value, label }]`), `post_categories`, `programme_types` (`[{ value, label }]`), `programme_availability` (`open`, `upcoming`, `closed`), `partner_types`, `currency` (`"GHS"`). No parameters. Codes: 200, 304.

## GET /api/v2/projects (`api.v2.projects.index`)

Public projects.

| Parameter | Rule |
| --- | --- |
| `search` | `nullable`, `string`, `max:100` |
| `focus_area` | `nullable`, `string`, `in:` `config('hub.focus_areas')` |
| `status` | `nullable`, enum `ProjectStatus` |
| `year` | `nullable`, `integer`, `between:1990,2100` |
| shared | `limit`, `cursor`, `sort`, `updated_since`, `include` |

An unknown `include` value (for example `include=secrets`) returns `422` with `details.include`.

Item fields: `id` (slug), `title`, `summary`, `focus_area`, `status`, `start_date`, `end_date`, `beneficiaries` (`target`, `reached`, `progress_percent`), `grant` (`{ amount, currency }` or `null`), `lead_researcher`, `department`, `partners` (only with `include=partners`: `[{ id, name, role }]`), `report_url`, `updated_at`, `links` (`self`, `html`). Codes: 200, 304, 422.

## GET /api/v2/projects/{project} (`api.v2.projects.show`)

One public project by slug, always with `partners`. Hidden or unknown: `404`. Codes: 200, 304, 404.

## GET /api/v2/publications (`api.v2.publications.index`)

| Parameter | Rule |
| --- | --- |
| `search` | `nullable`, `string`, `max:100` |
| `type` | `nullable`, `string`, `in:` `config('hub.publication_types')` |
| `project` | `nullable`, `string`, `max:255` (slug of a public project) |
| shared | `limit`, `cursor`, `sort`, `updated_since`, `include` |

Item fields: `id` (slug), `title`, `type`, `authors`, `abstract`, `published_on`, `journal`, `doi_url`, `downloads`, `document_url`, `project` (`{ id, title, links.self }` if public, else `null`), `updated_at`, `links.self`. Codes: 200, 304, 422.

## GET /api/v2/publications/{publication} (`api.v2.publications.show`)

One publication by slug. Same fields. Codes: 200, 304, 404.

## GET /api/v2/news (`api.v2.news.index`)

Published posts only. Default sort `-published_at`.

| Parameter | Rule |
| --- | --- |
| `type` | `nullable`, enum `PostType` |
| `category` | `nullable`, `string`, `in:` `config('hub.post_categories')` |
| `featured` | `nullable`, `boolean` (`true`, `false`, `1`, `0`) |
| shared | `limit`, `cursor`, `sort` (`-published_at`, `published_at`, `-id`, `id`), `updated_since`, `include` |

`category` values: `General`, `Grants & Awards`, `Research Highlights`, `Technology & Society`, `Opportunities`, `Events`.

Item fields: `id` (slug), `title`, `type`, `category`, `summary`, `author`, `is_featured`, `published_at`, `reading_minutes`, `updated_at`, `links` (`self`, `html`). Lists omit `content` and `content_format`. Codes: 200, 304, 422.

## GET /api/v2/news/{post} (`api.v2.news.show`)

One published post by slug. Same fields plus `content` (Markdown) and `content_format` (`"markdown"`). Unpublished or scheduled: `404`. Codes: 200, 304, 404.

## GET /api/v2/partners (`api.v2.partners.index`)

| Parameter | Rule |
| --- | --- |
| `search` | `nullable`, `string`, `max:100` (name; case-insensitive on PostgreSQL) |
| `type` | `nullable`, `string`, `max:100` (exact match against the organisation type) |
| shared | `limit`, `cursor`, `sort`, `updated_since`, `include` |

Item fields: `id` (string), `name`, `type`, `website`, `public_projects`, `updated_at`. No contact e-mail, no `links`. There is no partner detail endpoint. Codes: 200, 304, 422.

## GET /api/v2/programmes (`api.v2.programmes.index`)

Published and closed programmes.

| Parameter | Rule |
| --- | --- |
| `search` | `nullable`, `string`, `max:100` |
| `type` | `nullable`, enum `ProgramType` |
| `focus_area` | `nullable`, `string`, `in:` `config('hub.focus_areas')` |
| `availability` | `nullable`, `in:open,upcoming,closed` |
| shared | `limit`, `cursor`, `sort`, `updated_since`, `include` |

Item fields: `id` (slug), `title`, `type`, `type_label`, `focus_area`, `summary`, `availability` (`open`, `upcoming` or `closed`), `is_accepting_applications`, `is_featured`, `funding` (`{ amount, currency }` or `null`), `location`, `opens_at`, `closes_at`, `starts_at`, `updated_at`, `links` (`self`, `html`, `apply`). Capacity is computed from one `withCount` subquery. Codes: 200, 304, 422.

## GET /api/v2/programmes/{program} (`api.v2.programmes.show`)

One programme by slug. Same fields plus `description` and `eligibility`. Draft or archived: `404`. Codes: 200, 304, 404.

## GET /api/v2/albums (`api.v2.albums.index`)

Published gallery albums that have a cover photo. Shared parameters only.

Item fields: `id` (slug), `title`, `summary`, `event_date`, `location`, `photo_count` (all photo rows of the album, including any whose file is missing), `cover` (a photo object, or `null` when the cover file is missing), `programme` (`{ id, title, links.self }` or `null`; only for visible programmes), `published_at`, `updated_at`, `links` (`self`, `html`). Codes: 200, 304, 422.

Photo object: `id` (string), `caption`, `alt`, `large` (`url`, `width`, `height`), `thumb` (`url`, `width`, `height`). The URLs point to `/media/photos/{photo}/{size}` (see [api-integrations.md](api-integrations.md)).

> **Requires confirmation:** whether editing or adding photos changes the album's `updated_at`. If it does not, `updated_since` on `/albums` may miss photo-only changes.

## GET /api/v2/albums/{album} (`api.v2.albums.show`)

One published album by slug. Same fields plus `description` and `photos[]`, which lists only photos whose files exist. Unpublished album: `404`. Codes: 200, 304, 404.

## GET /api/v2/community (`api.v2.community`)

Members who opted in to the leaderboard, ordered by XP then id.

| Parameter | Rule |
| --- | --- |
| `limit` | `nullable`, `integer`, `between:1,100`. Default 25. |

`cursor`, `sort`, `updated_since` and `include` are ignored. `data` is an array. There is no `meta` or `links`. Item fields: `rank`, `name`, `affiliation`, `xp`, `level`, `badges` (`[{ type, title, awarded_on }]`), `links.html`. Codes: 200, 304, 422.

---

## Data never returned

Contact e-mails of partners, member e-mail and phone, demographic data, application forms and applicant counts by status, internal project fields (creator, legacy id, visibility flag, stored file paths), drafts, scheduled posts, hidden projects, unpublished albums. See [authentication.md](authentication.md).
