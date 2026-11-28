# API Testing

> **Audience:** Developers, integrators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [testing-strategy](testing-strategy.md), [API overview](../06-api-reference/api-overview.md), [endpoints](../06-api-reference/endpoints.md), [error codes](../06-api-reference/error-codes.md), [rate limiting](../06-api-reference/rate-limiting.md)

The open data API is read-only, public and unauthenticated. It has two versions. Tests live in `../../tests/Feature/Api/PublicApiTest.php` (v1, 7 tests) and `../../tests/Feature/Api/ApiV2Test.php` (v2 and versioning, 11 tests). Both use `RefreshDatabase`, factories and `getJson()`. This page lists each test and what it asserts, explains how to add tests for a new endpoint, and recommends contract tests for the frozen v1.

```powershell
php artisan test tests/Feature/Api
php vendor/bin/phpunit tests/Feature/Api --testdox
```

```bash
php artisan test tests/Feature/Api
```

## v1: `PublicApiTest` (7 tests)

v1 envelope: `{success, data, meta, links}`; errors `{success:false, message, errors}`; page-number pagination.

| Test | What it asserts |
| --- | --- |
| `test_index_lists_endpoints` | `GET /api/v1` returns 200, `success: true`, and `data.version` plus an `endpoints` object with `stats`, `projects`, `publications`, `news`, `partners` |
| `test_stats_only_count_public_projects` | One public and one internal project exist. `/api/v1/stats` counts 1 project, 100 beneficiaries (not 1000) and currency `GHS` |
| `test_projects_are_paginated_filtered_and_hide_internal_fields` | `focus_area=STEM&per_page=2` gives 2 rows, `meta.total` 3, `meta.per_page` 2, a `links.next`, and the documented keys (`slug`, `title`, `focus_area`, `status`, `beneficiaries.{target,reached,progress_percent}`, `url`). The internal project's slug is absent from the body. `created_by`, `is_public` and `report_file_path` are not keys. The internal project's detail URL returns 404 with exactly `{success:false, message:"Resource not found."}` |
| `test_invalid_filters_return_a_422_envelope` | `focus_area=Astrology&per_page=500` gives 422, `success: false`, validation errors on `focus_area` and `per_page` |
| `test_publications_and_news_endpoints` | Publication `type` filter works. News list has only published posts and no `content` key. News detail has `content`. A draft post's detail is 404 |
| `test_partners_endpoint_never_exposes_contact_emails` | A partner's `contact_email` string does not appear anywhere in the body |
| `test_responses_are_cacheable_and_rate_limited` | `Cache-Control` contains `public` and `max-age=60`; `X-RateLimit-Limit` is `60` |

## v2 and versioning: `ApiV2Test` (11 tests)

v2 envelope: `{data, meta, links}`; errors `{error:{status, code, message, details}}`; cursor pagination; string ids.

| Test | What it asserts |
| --- | --- |
| `test_version_index_lists_both_versions_and_marks_v2_current` | `GET /api` lists v1 (`supported`) and v2 (`current`), `data.current` is `v2`. `/api/v2` has `API-Version: v2`, endpoints (`projects`, `albums`, `reference`) and `conventions`. `/api/v1` has `API-Version: v1` and still returns `success: true` |
| `test_deprecated_versions_send_deprecation_and_sunset_headers` | With `api.versions.v1.deprecated_at` and `sunset_at` set, v1 sends `Deprecation: @<timestamp>`, `Sunset: Thu, 01 Jul 2027 00:00:00 GMT` and a `Link` header with `rel="deprecation"`. `/api` shows v1 as `deprecated` with `sunset_at`. v2 sends neither header |
| `test_projects_use_cursor_pagination_without_gaps_or_duplicates` | Five public projects, `limit=2`. Follows `meta.next_cursor` until empty. Sees 5 unique ids, no internal project, `meta.has_more` true on page one, no `meta.total`, no top-level `success`. Internal project detail is 404 |
| `test_project_shape_money_includes_and_hidden_fields` | `id` equals the slug. `grant` is `{amount:"1500.00", currency:"GHS"}`. `partners` appears only with `?include=partners` (partner `id` is a string) and always on detail (with `role`). Required keys and `links.{self,html}` exist. `created_by`, `is_public` and a partner's private e-mail are absent |
| `test_updated_since_returns_only_changed_records` | One project is back-dated 10 days. `updated_since=<yesterday>` returns only the new one |
| `test_errors_use_the_v2_envelope_with_machine_readable_codes` | `limit=500&sort=title&include=secrets` gives 422 `validation_failed` with `details` for `limit` and `sort`. Invalid `include` gives details for `include`. Unknown news slug gives exactly `{error:{status:404, code:"not_found", message:"Resource not found."}}`. Unknown path under `/api/v2` is `not_found`. v1 keeps its old envelope on 404 |
| `test_news_sorting_filters_and_content_only_on_detail` | Default order newest first; `sort=published_at` reverses; `category` filter; list has no `content`; detail has `content` and `content_format: markdown`; draft is 404 |
| `test_programmes_report_capacity_without_a_query_per_row` | Full and open programmes (draft excluded). `availability=open` returns both with the right `is_accepting_applications` and `availability`. Query count is at most 4 (see [performance-testing](performance-testing.md)) |
| `test_albums_list_published_events_with_available_photos_only` | Uses `Storage::fake('gallery')`. Only the published album is listed with `photo_count` 2. Detail lists only the photo whose file still exists, with `large` and `thumb` URLs. Draft album is 404 |
| `test_reference_stats_partners_publications_and_community` | `/reference` returns `currency`, `focus_areas`, `project_statuses`, `programme_types`, `post_types`. `/stats` has `funding_secured.amount` and `meta.generated_at`. Partner search works and hides contact e-mail. Publications filter by type. `/community` is an empty `data` array when nobody opted in |
| `test_responses_support_etags_caching_and_shared_rate_limits` | For `/api/v1/projects` and `/api/v2/projects`: `X-RateLimit-Limit` is 60, an `ETag` exists, `Cache-Control` has `max-age=60`, and repeating with `If-None-Match` returns 304 |

### Related tests outside the Api folder

| Test | What it asserts |
| --- | --- |
| `Portal/EngagementTest::test_public_programmes_api_hides_drafts_and_forms` | v1 programmes hide drafts and the application form schema |
| `Workspace/OperationalFixesTest::test_programme_lists_do_not_count_applications_once_per_programme` | v1 `/api/v1/programmes` with 6 programmes uses at most 4 queries |
| `SecurityHeadersTest::test_api_responses_carry_security_headers` | `/api/v1` has `X-Content-Type-Options: nosniff` |

## Not covered by the existing tests

Each item is a gap, found by comparing the controllers in `app/Http/Controllers/Api` with the tests.

| Gap | Detail |
| --- | --- |
| Rate-limit rejection | Tests read `X-RateLimit-Limit` but never exceed the limit, so the 429 response and the v2 `rate_limited` code are not asserted |
| `405 method_not_allowed`, `500 server_error`, `503 service_unavailable`, `http_error` | The v2 error codes are rendered in `bootstrap/app.php`. Only `validation_failed` and `not_found` are asserted. `unauthenticated` and `forbidden` have no public route to trigger them |
| Invalid or tampered `cursor`, invalid `updated_since` | Not asserted |
| v1 endpoints without a dedicated test | `/api/v1/publications/{publication}`, `/api/v1/programmes/{program}` (detail), `/api/v1/community` |
| v2 detail routes | `/api/v2/publications/{publication}`, `/api/v2/programmes/{program}` (detail), `/api/v2/community?limit=`. There is no v2 partner detail route |
| `If-None-Match` on detail routes and a changed ETag after an update | Only list routes are tested |
| Known limitation: no tombstones | `updated_since` cannot detect deleted or unpublished records. No test documents this behaviour |
| `API_*` environment variables | Tests set `config()` directly, not the env variables |

## How to add tests for a new endpoint

1. Put v2 tests in `ApiV2Test.php` or a new class in `tests/Feature/Api`. Do not change v1 tests to fit a new feature (see the contract-test recommendation below).
2. Create data with factories. Add an internal or draft record to every test of a public list, and assert it never appears.
3. Assert the shape with `assertJsonStructure` and the privacy rules with `assertJsonMissingPath` or `assertStringNotContainsString` on `getContent()`.
4. Assert the error envelope for a bad query (422) and a missing record (404).
5. Add the `ETag` and 304 check by extending the URL list in `test_responses_support_etags_caching_and_shared_rate_limits`.
6. If the list runs a subquery or relation, assert the query count (see below).
7. Run the whole `tests/Feature/Api` folder and `SecurityHeadersTest`.

```php
public function test_widgets_list_hides_drafts_and_uses_the_v2_envelope(): void
{
    $live = Widget::factory()->create();            // placeholder model and factory
    Widget::factory()->draft()->create();

    $this->getJson('/api/v2/widgets?limit=10')
        ->assertOk()
        ->assertHeader('API-Version', 'v2')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $live->slug)
        ->assertJsonMissingPath('success')
        ->assertJsonStructure(['data' => [['id', 'updated_at', 'links' => ['self']]], 'meta' => ['per_page', 'has_more'], 'links']);

    $this->getJson('/api/v2/widgets?limit=500')
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_failed');
}
```

`Widget` is a placeholder. There is no such model.

Query-count pattern used by the existing tests:

```php
DB::enableQueryLog();
$this->getJson('/api/v2/programmes')->assertOk();
$queries = count(DB::getQueryLog());
DB::disableQueryLog();
$this->assertLessThanOrEqual(4, $queries);
```

## Trying the API by hand

For a manual check against a running site, request the version list and a v2 list. Replace the host with your own when testing locally.

```powershell
Invoke-WebRequest https://uew-innovation-hub.onrender.com/api -UseBasicParsing | Select-Object StatusCode, Headers
(Invoke-WebRequest https://uew-innovation-hub.onrender.com/api/v2/projects?limit=1 -UseBasicParsing).Headers['ETag']
```

```bash
curl -sI https://uew-innovation-hub.onrender.com/api/v2/projects?limit=1
curl -s -H 'If-None-Match: <etag from the previous call>' -o /dev/null -w '%{http_code}\n' https://uew-innovation-hub.onrender.com/api/v2/projects?limit=1
```

## Recommended, not implemented: contract tests for frozen v1

v1 is declared unchanged (its contract), and the only additive change is the ETag and 304. Today `PublicApiTest` checks selected keys, so a renamed or extra key can pass. Recommended tests:

1. **Exact key snapshot.** For each v1 endpoint, store the expected JSON (with ids and dates replaced by fixed factory values) under `tests/Fixtures/api-v1/` and compare with `assertExactJson`. A fixture change then needs a deliberate commit. Use only synthetic data in fixtures.
2. **Route inventory.** Assert that the names and URIs of the 12 v1 routes (`index`, `stats`, `projects.index`, `projects.show`, `publications.index`, `publications.show`, `news.index`, `news.show`, `partners.index`, `programmes.index`, `programmes.show`, `community`) match a fixed list, so a removed route fails the build.
3. **Error envelope.** Assert 404, 422 and 429 bodies for v1 use `success:false`.
4. **Header contract.** Assert `API-Version: v1`, `X-RateLimit-Limit`, `Cache-Control` and ETag behaviour on every v1 endpoint, not only `projects`.
5. **Schema validation.** Optionally describe v1 in an OpenAPI file and validate responses against it. No OpenAPI file exists in the repository, so this requires writing one first.
6. **Sunset rehearsal.** The existing deprecation test uses `config()`. Add one that sets the real `API_V1_DEPRECATED_AT` and `API_V1_SUNSET_AT` values in a deploy preview and checks the headers on the live URL.

Also recommended: a 429 test (call the endpoint `limit + 1` times with a lowered `API_RATE_LIMIT_PER_MINUTE`), and a v2 test for each error code in `bootstrap/app.php`.
