# Input Validation and Output Encoding

> **Audience:** developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Backend development](../05-developer-guide/backend-development.md), [Data protection](data-protection.md), [Threat model](threat-model.md), [Security testing](../10-testing-and-quality/security-testing.md)

This page explains how user input is validated at the boundary, how output is escaped, and where raw HTML is emitted on purpose. It lists every `{!! !!}` in the Blade views and why each is safe or needs care.

## Validation at the boundary

- Forms use `FormRequest` classes in `app/Http/Requests/{Auth,Portal,Site,Workspace}`. A few simple endpoints validate inline (`PasswordResetController`, `EmailLinkController::reset`, `AlbumController`).
- Enum-like fields use `Rule::in(...)`. Examples: `gender` against `Demographics::GENDERS`, `disability` against `Demographics::DISABILITY`.
- Programme application forms are defined as JSON by staff. [`FormSchema`](../../app/Support/FormSchema.php) normalises keys, drops invalid fields, caps labels at 200 characters, options at 120, help at 300, and builds per-field rules at submission. Unknown `prefill` sources are discarded (`form_schema_normalises_keys_and_drops_invalid_fields`).
- The sign-in request caps e-mail and password at 255 characters.
- Database access uses Eloquent and the query builder with bindings. `scripts/security-scan.ps1` greps `app/` for raw SQL with interpolated variables. Raw expressions that exist, such as `selectRaw('gender, COUNT(*) ...')` in `InsightsService`, use constant strings.
- Search input escapes LIKE wildcards (`docs/security.md`; see the `Searchable` trait used by models).
- API v1 filters are validated and bad filters return a 422 envelope (`invalid_filters_return_a_422_envelope`). `per_page` must be 1 to 50 (`between:1,50` in each `Api\V1` list controller).
- API v2 validates every list in `Api\V2\ApiController::validateList()`: `limit` is an integer from 1 to 100, `cursor` a string up to 512 characters, `updated_since` a date, `sort` must be one of the keys returned by the endpoint's `sorts()` whitelist (`Rule::in`), and `include` is a string up to 200 characters whose comma-separated items must all appear in the endpoint's `includes()` whitelist (`requestedIncludes()`). The only include is `partners` on projects. Failures return 422 `validation_failed` with the field messages in `details` (`ApiV2Test::test_errors_use_the_v2_envelope_with_machine_readable_codes`). `sort` values map to fixed column and direction pairs, so no client text reaches `ORDER BY`.
- The v2 `cursor` is only length-checked. The framework decodes it and, to our reading, treats an undecodable cursor as the first page. **Requires confirmation:** no test covers a malformed cursor.

## Mass assignment

Models declare `$fillable` explicitly (no model uses `$guarded = []`). Privileged fields (`role`, `is_active`, `email_verified_at`, grant `status`, `reviewed_by`, post `published_at`) are set only in services. See [authorization security](authorization-security.md#privilege-escalation-controls).

## File upload validation

| Upload | Rules | Source |
| --- | --- | --- |
| Project report PDF | `file`, `mimes:pdf`, `mimetypes:application/pdf`, `max:MAX_UPLOAD_KB` (default 20480) | [`ProjectRequest`](../../app/Http/Requests/Workspace/ProjectRequest.php) |
| Publication PDF | same | [`PublicationRequest`](../../app/Http/Requests/Workspace/PublicationRequest.php) |
| Application PDF field | same, built per field | `FormSchema` line 119 |
| Gallery photo | `file`, `image`, `mimes:jpg,jpeg,png,webp`, `max:10240` (KB), `dimensions:min_width=300,min_height=300` | [`AlbumController`](../../app/Http/Controllers/Workspace/AlbumController.php) |

Laravel's `mimes` and `mimetypes` rules detect type from content, not only from the extension. PDFs are not scanned for malware and their internal structure is not checked: a file that begins as a PDF and carries other content passes. They are served with `nosniff` and an `application/pdf` type, which limits browser risk. Antivirus scanning is Recommended, not implemented.

Photos get a second check in [`GalleryService::decode`](../../app/Services/GalleryService.php): `getimagesize` must report JPEG, PNG or WebP; images above 30 megapixels are refused before decoding (decompression-bomb guard); `imagecreatefromstring` must succeed. Output is always a fresh JPEG.

PHP limits in the container (`docker/php.ini`): `upload_max_filesize=25M`, `post_max_size=30M`, `max_file_uploads=5`. The root `.user.ini` sets 100M, but PHP in the Docker image runs as an Apache module, which ignores `.user.ini`; it only applies on CGI/FPM hosting. The two files disagree and should be reconciled. **Requires confirmation:** which value applies on any non-Docker host.

## Bots and abuse

- Honeypot field `company_website` (`max:0`) on sign-up ([`RegisterRequest`](../../app/Http/Requests/Auth/RegisterRequest.php)) and the contact form ([`StoreContactMessageRequest`](../../app/Http/Requests/Site/StoreContactMessageRequest.php)). A filled field produces a generic failure message.
- Rate limiters in [`AppServiceProvider`](../../app/Providers/AppServiceProvider.php): `api` per IP (`API_RATE_LIMIT_PER_MINUTE`, default 60, shared by v1 and v2); `contact-messages` 3/min and 20/day per IP; `registrations` 5/min and 30/day per IP; `password-codes` 3/min per IP and 10/hour per `email|ip`.
- Inline route throttles: messages 20/min, reports 10/min, apply 30/min, workspace photo upload 20/min, staff messages 30/min, test e-mail 5/min, PDF export 10/min.
- No CAPTCHA. Recommended, not implemented.

## Output encoding

Blade `{{ }}` escapes everything by default. Raw output with `{!! !!}` appears in these places only:

| File | Expression | Why it is safe |
| --- | --- | --- |
| `site/page.blade.php:12` | `Str::markdown($page->body, ['html_input' => 'escape', 'allow_unsafe_links' => false])` | Raw HTML in Markdown is escaped; `javascript:` links are dropped |
| `site/news/show.blade.php:26` | same, for `$post->content` | same |
| `site/programs/show.blade.php:29,36` | same, for `description` and `eligibility` | same |
| `components/layouts/site.blade.php:48` | `json_encode($organisation, JSON_UNESCAPED_SLASHES \| JSON_HEX_TAG \| JSON_HEX_AMP)` inside `application/ld+json` | `JSON_HEX_TAG` prevents `</script>` breakout |
| `emails/templated.blade.php:2` | `$rendered['html']` | Built by [`EmailTemplates::render`](../../app/Support/EmailTemplates.php): placeholder values are escaped with `addcslashes` before Markdown, then Markdown runs with `html_input => escape`. Tested in `member_supplied_text_cannot_inject_html_into_emails` |
| `emails/templated-text.blade.php:1,8` | `strip_tags(...)` | Plain-text part |
| `components/emails/layout.blade.php:95` | `$footerNote` | Raw HTML. The only caller that sets it is `SendBroadcast`, which passes `e($unsubscribe)` inside fixed markup. Any new caller must escape its values |
| `workspace/broadcasts/show.blade.php:9` | `$preview['html']` | Same renderer as the e-mail |
| `workspace/reports/pdf.blade.php:189,198` | `$bar(...)` | Returns a fixed `<div>` with a numeric width and a constant colour |

Markdown is sanitised by CommonMark options only (no HTML purifier is installed). Staff authors (officers, interns) are trusted but cannot inject HTML or script, because HTML is escaped (`cms_page_markdown_cannot_inject_html_or_scripts`). The CSP also blocks inline scripts and third-party script origins (`script-src 'self' 'unsafe-eval'`).

## Other output controls

- CSV exports neutralise spreadsheet formulas (`csv_export_streams_filtered_rows_and_neutralises_formulas`).
- Notification links are accepted only as relative paths beginning with a single `/`, with no backslash or control characters (open-redirect guard).
- The unsubscribe and e-mail-link pages show a masked e-mail (`EmailLinkController::mask`).
- Filenames in `Content-Disposition` are slugified (`DocumentStorage::download`).

## Gaps

| Gap | Note |
| --- | --- |
| No malware scan on PDFs | Recommended, not implemented |
| No CAPTCHA | Honeypot and rate limits only |
| `.user.ini` and `docker/php.ini` disagree on upload limits | Cosmetic in Docker |
| `footerNote` is raw HTML | Safe today; future callers must escape |
