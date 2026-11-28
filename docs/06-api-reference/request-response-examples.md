# Request and response examples

> **Audience:** Developers, integrators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [API overview](api-overview.md), [Endpoints](endpoints.md), [Error codes](error-codes.md), [Rate limiting](rate-limiting.md)

Copy-paste examples for `v1` and `v2` against the live site. Every value in a response body is illustrative. Real titles, slugs, counts and dates differ and change over time. The field names and shapes match the resource classes. Use `curl` (bash, or `curl.exe` in PowerShell) or `Invoke-RestMethod` (PowerShell).

```bash
BASE=https://uew-innovation-hub.onrender.com
```

```powershell
$Base = 'https://uew-innovation-hub.onrender.com'
```

> **Note:** in Windows PowerShell 5.1, `curl` is an alias for `Invoke-WebRequest`. Type `curl.exe` to run the real curl.

The live site runs on Render's free plan. The first request after a quiet period can take tens of seconds while the service wakes up.

## API v2

### List versions

```bash
curl -s "$BASE/api"
```

```powershell
Invoke-RestMethod "$Base/api" | ConvertTo-Json -Depth 5
```

```json
{
  "data": {
    "name": "UEW Innovation Hub API",
    "current": "v2",
    "documentation": "https://github.com/mhiskall282/uew-innovation-hub/tree/main/docs/06-api-reference",
    "versions": [
      { "version": "v1", "status": "supported", "url": "https://uew-innovation-hub.onrender.com/api/v1", "deprecated_at": null, "sunset_at": null },
      { "version": "v2", "status": "current", "url": "https://uew-innovation-hub.onrender.com/api/v2", "deprecated_at": null, "sunset_at": null }
    ]
  }
}
```

### First page of a list

```bash
curl -s -i "$BASE/api/v2/projects?limit=2"
```

```powershell
$r = Invoke-RestMethod "$Base/api/v2/projects?limit=2"
$r.meta
```

Headers (illustrative):

```text
HTTP/2 200
api-version: v2
cache-control: max-age=60, public, s-maxage=60
etag: W/"8ad7baaac7fc087cf3360e55ef74b6d7"
x-ratelimit-limit: 60
x-ratelimit-remaining: 59
```

Body (shortened):

```json
{
  "data": [
    {
      "id": "community-clean-water-sensing-and-solar-pumping",
      "title": "Community Clean Water Sensing and Solar Pumping",
      "summary": "Real-time water quality monitoring ...",
      "focus_area": "Community Outreach",
      "status": "active",
      "start_date": "2025-03-01",
      "end_date": "2026-12-31",
      "beneficiaries": { "target": 15000, "reached": 14200, "progress_percent": 95 },
      "grant": { "amount": "450000.00", "currency": "GHS" },
      "lead_researcher": "Dr. Example Name",
      "department": "Department of Example",
      "report_url": null,
      "updated_at": "2026-11-24T10:22:18+00:00",
      "links": {
        "self": "https://uew-innovation-hub.onrender.com/api/v2/projects/community-clean-water-sensing-and-solar-pumping",
        "html": "https://uew-innovation-hub.onrender.com/projects/community-clean-water-sensing-and-solar-pumping"
      }
    }
  ],
  "meta": { "per_page": 2, "has_more": true, "next_cursor": "eyJpZCI6MTAsIl9wb2ludHNUb05leHRJdGVtcyI6dHJ1ZX0", "prev_cursor": null },
  "links": {
    "self": "https://uew-innovation-hub.onrender.com/api/v2/projects?limit=2",
    "next": "https://uew-innovation-hub.onrender.com/api/v2/projects?limit=2&cursor=eyJpZCI6MTAsIl9wb2ludHNUb05leHRJdGVtcyI6dHJ1ZX0",
    "prev": null
  }
}
```

The cursor and the `html` URL pattern are illustrative.

### Cursor paging loop

Follow `meta.next_cursor` until `meta.has_more` is `false`.

```bash
# Requires jq. Collects every project id.
cursor=""
while :; do
  url="$BASE/api/v2/projects?limit=100"
  [ -n "$cursor" ] && url="$url&cursor=$cursor"
  page=$(curl -s "$url")
  echo "$page" | jq -r '.data[].id'
  [ "$(echo "$page" | jq -r '.meta.has_more')" = "true" ] || break
  cursor=$(echo "$page" | jq -r '.meta.next_cursor')
  sleep 1   # stay far below 60 requests per minute
done
```

```powershell
$ids = @()
$cursor = $null
do {
  $url = "$Base/api/v2/projects?limit=100"
  if ($cursor) { $url += '&cursor=' + [uri]::EscapeDataString($cursor) }
  $page = Invoke-RestMethod $url
  $ids += $page.data.id
  $cursor = $page.meta.next_cursor
  Start-Sleep -Seconds 1
} while ($page.meta.has_more)
$ids.Count
```

### Incremental sync with updated_since

Store the time you started the last sync, then ask for changes since then. The filter is inclusive (`>=`), so deduplicate by `id`.

```bash
curl -s -G "$BASE/api/v2/projects" \
  --data-urlencode "updated_since=2026-11-24T00:00:00+00:00" \
  --data-urlencode "limit=100"
```

```powershell
$since = [uri]::EscapeDataString('2026-11-24T00:00:00+00:00')
Invoke-RestMethod "$Base/api/v2/projects?updated_since=$since&limit=100"
```

A plain date such as `updated_since=2026-11-24` also works (`date` rule). A value like `notadate` returns `422 validation_failed`.

### Sorting and filters

```bash
# Featured news, oldest first
curl -s "$BASE/api/v2/news?featured=true&sort=published_at&limit=5"

# Open programmes of type fellowship
curl -s "$BASE/api/v2/programmes?availability=open&type=fellowship"

# Publications of one project (slug is illustrative)
curl -s "$BASE/api/v2/publications?project=community-clean-water-sensing-and-solar-pumping"
```

```powershell
Invoke-RestMethod "$Base/api/v2/news?featured=true&sort=published_at&limit=5"
Invoke-RestMethod "$Base/api/v2/programmes?availability=open&type=fellowship"
```

### include=partners

```bash
curl -s "$BASE/api/v2/projects?limit=1&include=partners"
```

```powershell
(Invoke-RestMethod "$Base/api/v2/projects?limit=1&include=partners").data[0].partners
```

```json
"partners": [
  { "id": "3", "name": "Example Funding Partner", "role": "Funding Partner" }
]
```

### Single record

```bash
curl -s "$BASE/api/v2/news/call-for-applications-2026-student-innovation-fellowship"
```

The response has `data` only, with `content` (Markdown) and `content_format: "markdown"` added.

### Money

```json
"funding": { "amount": "50000.00", "currency": "GHS" }
```

`amount` is a decimal string. Parse it with a decimal type, not a float.

### Conditional request: If-None-Match and 304

```bash
# 1. Save the ETag
curl -s -D headers.txt -o body.json "$BASE/api/v2/reference"
etag=$(grep -i '^etag:' headers.txt | cut -d' ' -f2- | tr -d '\r')

# 2. Revalidate
curl -s -i -H "If-None-Match: $etag" "$BASE/api/v2/reference"
```

```powershell
$first = Invoke-WebRequest "$Base/api/v2/reference"
$etag = $first.Headers['ETag']

try {
  Invoke-WebRequest "$Base/api/v2/reference" -Headers @{ 'If-None-Match' = $etag }
} catch {
  $_.Exception.Response.StatusCode   # NotModified (304)
}
```

In PowerShell 5.1 a `304` raises an exception, as shown. In PowerShell 7, use `-SkipHttpErrorCheck` and read `.StatusCode`.

```text
HTTP/2 304
api-version: v2
cache-control: max-age=60, public, s-maxage=60
etag: W/"..."
```

There is no body on a `304`. The ETag only matches for the same URL and query string. Do not use this on `/stats`, whose body contains a timestamp.

### Reference lists

```bash
curl -s "$BASE/api/v2/reference"
```

```json
{
  "data": {
    "focus_areas": ["STEM", "Educational Tech", "Agriculture", "Inclusive Education", "Digital Humanities", "Community Outreach"],
    "project_statuses": [{ "value": "pending", "label": "Pending" }, { "value": "active", "label": "Active" }],
    "programme_availability": ["open", "upcoming", "closed"],
    "currency": "GHS"
  }
}
```

(shortened; labels illustrative)

### Albums

```bash
curl -s "$BASE/api/v2/albums?limit=5"
curl -s "$BASE/api/v2/albums/<album-slug>"
```

The detail response adds `description` and `photos[]`. Each photo has `large.url` and `thumb.url` pointing at `/media/photos/{photo}/{size}`.

### Community

```bash
curl -s "$BASE/api/v2/community?limit=10"
```

```json
{
  "data": [
    {
      "rank": 1,
      "name": "Example Member",
      "affiliation": "Student",
      "xp": 120,
      "level": "Explorer",
      "badges": [{ "type": "first_application", "title": "First application", "awarded_on": "2026-11-20" }],
      "links": { "html": "https://uew-innovation-hub.onrender.com/community/7" }
    }
  ]
}
```

(illustrative)

## v2 error bodies

```bash
curl -s -i "$BASE/api/v2/projects?limit=500&sort=title&include=secrets"
```

```json
{
  "error": {
    "status": 422,
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "details": {
      "limit": ["The limit field must be between 1 and 100."],
      "sort": ["The selected sort is invalid."],
      "include": ["Unknown include: secrets. Allowed: partners."]
    }
  }
}
```

The message wording for each field is illustrative (Laravel language files).

```bash
curl -s -i "$BASE/api/v2/programmes/no-such-programme"
```

```json
{ "error": { "status": 404, "code": "not_found", "message": "Resource not found." } }
```

```bash
curl -s -i -X POST "$BASE/api/v2/projects"
```

```json
{ "error": { "status": 405, "code": "method_not_allowed", "message": "The POST method is not supported for route api/v2/projects. Supported methods: GET, HEAD." } }
```

A `429` response looks like this and carries a `Retry-After` header:

```json
{ "error": { "status": 429, "code": "rate_limited", "message": "Too many requests. Please slow down." } }
```

## API v1

### List with page numbers

```bash
curl -s "$BASE/api/v1/projects?per_page=2&page=1&status=active"
```

```powershell
$r = Invoke-RestMethod "$Base/api/v1/projects?per_page=2&page=1&status=active"
$r.meta.total
```

```json
{
  "success": true,
  "data": [
    {
      "slug": "community-clean-water-sensing-and-solar-pumping",
      "title": "Community Clean Water Sensing and Solar Pumping",
      "summary": "Real-time water quality monitoring ...",
      "focus_area": "Community Outreach",
      "status": "active",
      "start_date": "2025-03-01",
      "end_date": "2026-12-31",
      "beneficiaries": { "target": 15000, "reached": 14200, "progress_percent": 95 },
      "grant_amount": 450000,
      "currency": "GHS",
      "lead_researcher": "Dr. Example Name",
      "department": "Department of Example",
      "partners": [{ "name": "Example Funding Partner", "role": "Funding Partner" }],
      "report_url": null,
      "url": "https://uew-innovation-hub.onrender.com/projects/community-clean-water-sensing-and-solar-pumping"
    }
  ],
  "meta": { "current_page": 1, "per_page": 2, "total": 10, "last_page": 5 },
  "links": {
    "first": "https://uew-innovation-hub.onrender.com/api/v1/projects?per_page=2&status=active&page=1",
    "prev": null,
    "next": "https://uew-innovation-hub.onrender.com/api/v1/projects?per_page=2&status=active&page=2",
    "last": "https://uew-innovation-hub.onrender.com/api/v1/projects?per_page=2&status=active&page=5"
  }
}
```

### Page loop

```powershell
$page = 1
do {
  $r = Invoke-RestMethod "$Base/api/v1/publications?per_page=50&page=$page"
  $r.data | ForEach-Object { $_.slug }
  $page++
} while ($page -le $r.meta.last_page)
```

### Stats

```bash
curl -s "$BASE/api/v1/stats"
```

```json
{
  "success": true,
  "data": {
    "projects": 11,
    "active_projects": 10,
    "beneficiaries_reached": 59680,
    "funding_secured": 3205000,
    "currency": "GHS",
    "partners": 7,
    "publications": 4
  },
  "meta": { "generated_at": "2026-11-28T03:53:15+00:00" }
}
```

### Conditional request

```bash
curl -s -i -H 'If-None-Match: W/"<etag-from-earlier-response>"' "$BASE/api/v1/programmes"
```

A match returns `304` with no body, as in `v2`.

### v1 error bodies

```bash
curl -s -i "$BASE/api/v1/projects?per_page=0"
```

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": { "per_page": ["The per page field must be between 1 and 50."] }
}
```

```json
{ "success": false, "message": "Resource not found." }
```

## Version header and deprecation check

```bash
curl -s -I "$BASE/api/v1/stats" | grep -iE '^(api-version|deprecation|sunset|link)'
```

```powershell
(Invoke-WebRequest "$Base/api/v1/stats" -Method Head).Headers['API-Version']
```

`API-Version: v1` is always present. `Deprecation`, `Sunset` and `Link` appear only when an operator sets the dates (see [API overview](api-overview.md#versioning-and-deprecation-policy-as-implemented)).
