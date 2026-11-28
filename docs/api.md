# Open data API (v1)

> **Note:** This is an earlier summary. The maintained, code-verified documentation is in the [documentation portal](README.md); see [06 API reference](06-api-reference/api-overview.md) (covers v1 and the current v2). Where the two disagree, the portal is correct.

Read-only JSON access to the Hub's public data. No authentication is needed.

- Base URL: `https://<your-domain>/api/v1`
- Rate limit: 60 requests per minute per IP (`X-RateLimit-Limit`, `X-RateLimit-Remaining` headers; `429` when exceeded)
- Caching: responses send `Cache-Control: public, max-age=60`
- Only public data is returned. Internal projects, unpublished posts, partner contact emails, applicants and staff are never exposed.

## Response format

Success:

```json
{
  "success": true,
  "data": [ ... ],
  "meta":  { "current_page": 1, "per_page": 15, "total": 42, "last_page": 3 },
  "links": { "first": "...", "prev": null, "next": "...", "last": "..." }
}
```

Single resources return `data` as an object and omit `links`.

Errors:

```json
{ "success": false, "message": "The given data was invalid.", "errors": { "per_page": ["..."] } }
```

| Status | Meaning |
| --- | --- |
| 404 | Unknown resource, or a resource that is not public |
| 422 | Invalid query parameter (`errors` lists the fields) |
| 429 | Rate limit exceeded |

List endpoints accept `per_page` (1 to 50, default 15) and `page`.

## Endpoints

### `GET /api/v1`

Discovery document with links to every endpoint.

### `GET /api/v1/stats`

```json
{
  "success": true,
  "data": {
    "projects": 6, "active_projects": 5, "beneficiaries_reached": 30180,
    "funding_secured": 1675000, "currency": "GHS", "partners": 7, "publications": 3
  },
  "meta": { "generated_at": "2026-11-25T10:00:00+00:00" }
}
```

### `GET /api/v1/projects`

| Parameter | Example | Notes |
| --- | --- | --- |
| `search` | `robotics` | Title, summary, lead researcher, department |
| `focus_area` | `STEM` | One of: STEM, Educational Tech, Agriculture, Inclusive Education, Digital Humanities, Community Outreach |
| `status` | `active` | `pending`, `active`, `completed`, `suspended` |
| `year` | `2025` | Start year |

Project object:

```json
{
  "slug": "stem-robotics-and-coding-incubator-for-high-schools",
  "title": "STEM Robotics and Coding Incubator for High Schools",
  "summary": "...",
  "focus_area": "STEM",
  "status": "active",
  "start_date": "2024-03-01",
  "end_date": "2025-06-30",
  "beneficiaries": { "target": 1500, "reached": 1250, "progress_percent": 83 },
  "grant_amount": 185000.0,
  "currency": "GHS",
  "lead_researcher": "Dr. Emmanuel Kusi",
  "department": "Department of ICT Education",
  "partners": [ { "name": "UNESCO", "role": "Funding Partner" } ],
  "report_url": "https://<domain>/documents/projects/stem-robotics-.../report",
  "url": "https://<domain>/projects/stem-robotics-..."
}
```

### `GET /api/v1/projects/{slug}`

One project. `404` if it does not exist or is internal.

### `GET /api/v1/publications`

Parameters: `search` (title, authors, journal), `type` (Research Paper, Policy Brief, Newsletter, Technical Report, Annual Report).

Fields: `slug`, `title`, `type`, `authors`, `abstract`, `publish_date`, `journal`, `doi_url`, `downloads`, `document_url`, `project` (`{slug, title}` when linked to a public project).

### `GET /api/v1/publications/{slug}`

### `GET /api/v1/news`

Published posts, newest first. Parameter: `type` (`news`, `blog`, `announcement`).

Fields: `slug`, `title`, `type`, `category`, `summary`, `author`, `is_featured`, `published_at` (ISO 8601), `reading_minutes`, `url`.

### `GET /api/v1/news/{slug}`

Same fields plus `content` (Markdown source).

### `GET /api/v1/partners`

Fields: `id`, `name`, `type`, `website`, `public_projects`.

### `GET /api/v1/programmes`

Published and closed programmes. Drafts and archived programmes are never returned, and neither are application forms or anything about applicants.

| Parameter | Values |
| --- | --- |
| `availability` | `open`, `upcoming`, `closed` (default: all visible) |
| `type` | `grant`, `fellowship`, `incubation`, `competition`, `training`, `event` |
| `focus_area` | One of the six focus areas, e.g. `STEM` |
| `search` | Title or summary text, up to 100 characters |
| `per_page` | 1 to 50 (default 15) |

Fields: `slug`, `title`, `type`, `type_label`, `focus_area`, `summary`, `availability`, `is_accepting_applications`, `is_featured`, `funding_amount`, `currency` (`GHS`), `location`, `opens_at`, `closes_at`, `starts_at`, `url`, `apply_url`.

### `GET /api/v1/programmes/{slug}`

Same fields plus `description` and `eligibility` (Markdown). Returns 404 for drafts and archived programmes.

### `GET /api/v1/community`

The top 25 members by XP who chose to appear on the public community page. Fields: `rank`, `name`, `affiliation`, `xp`, `level`, `badges` (`type`, `title`, `awarded_at`), `profile_url`. E-mail addresses, phone numbers and demographic data are never included.

## Security

- The API is read-only (`GET` only) and serves only data that is already public on the website.
- Requests are rate limited to 60 per minute per IP address. Over the limit you get `429` with a `Retry-After` header.
- Inputs are validated. Invalid filters return `422` with an `errors` object; nothing is passed to the database unchecked.
- Responses are cacheable for 60 seconds (`Cache-Control: public, max-age=60`).
- No API keys are needed, and none should be embedded in client code. If a future endpoint needs authentication it will use Laravel Sanctum tokens under `/api/v2`.

## Examples

```bash
curl https://<domain>/api/v1/stats
curl "https://<domain>/api/v1/projects?focus_area=Agriculture&per_page=5"
curl "https://<domain>/api/v1/news?type=announcement"
curl "https://<domain>/api/v1/programmes?availability=open&type=incubation"
curl https://<domain>/api/v1/community
```

```js
const res = await fetch('https://<domain>/api/v1/projects?status=active');
const { data, meta } = await res.json();
```

## Versioning

Breaking changes will ship under `/api/v2`; `/api/v1` keeps its current shape. New fields may be added to v1 responses, so clients should ignore fields they do not recognise.
