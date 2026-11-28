# Data Protection

> **Audience:** administrators, developers, the Hub's data protection contact · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Authorization security](authorization-security.md), [Database overview](../07-database/database-overview.md), [Data retention](../07-database/data-retention.md), [Backup and restore](../07-database/backup-and-restore.md), [Secrets management](secrets-management.md)

This page lists which personal data the system stores, who can see it, how files and secrets are protected at rest and in transit, and what is missing. The Hub operates under Ghana's Data Protection Act, 2012 (Act 843); `docs/security.md` and the report PDF footer refer to it. This page is a technical description, not legal advice.

> **Requires confirmation:** the Hub's registration with the Data Protection Commission, its retention periods, and its privacy notice wording. The Privacy page is CMS content (`PageSeeder`), editable by staff with `pages.manage`.

## Personal data held

| Data | Table and columns | Who sees it | Public? |
| --- | --- | --- | --- |
| Account identity | `users`: `name`, `email`, `phone`, `department`, `institution`, `bio` | Staff with `members.manage` (members), `users.manage` (staff) | Only `name`, affiliation, institution, bio, XP and badges, and only when `public_profile` is true |
| Demographics | `users`: `gender`, `date_of_birth`, `region`, `education_level`, `disability` | Officer and Hub Director on the member page; totals on reports | Never |
| Applicant snapshot | `program_applications.applicant_snapshot` (JSON frozen at submission) | Applicant; reviewers without gender and disability | Never |
| Application answers and files | `program_applications` answers, PDFs on the documents disk | Applicant; users with `applications.review` | Never |
| Messages and progress reports | `application_messages`, `progress_reports` | Author and reviewers | Never |
| Contact enquiries | `contact_messages`: name, e-mail, phone, subject, message | Staff with `messages.manage` | Never |
| Sign-in metadata | `users.last_login_at`, `last_login_ip`; `activity_logs.ip_address` | Hub Director (`audit.view`) | Never |
| Legacy grant applications | `grant_applications` | Staff with `grants.review` | Never |
| Partner contacts | `organizations` contact e-mail | Staff with `partners.manage` | Never. The API returns `website` but not the contact e-mail (`PublicApiTest::partners_endpoint_never_exposes_contact_emails`) |

Column detail is in [tables and columns](../07-database/tables-and-columns.md).

## Collection and consent

- Registration collects name, e-mail, phone, gender, date of birth, region and password (`RegisterRequest`). Profile and onboarding add affiliation, institution, education level, disability and interests.
- `marketing_opt_in` defaults to `false`. Broadcasts only reach opted-in members and every broadcast carries a signed one-click unsubscribe link and RFC 8058 headers (`broadcast_reaches_only_opted_in_matching_members...`).
- `public_profile` defaults to `false`. The community page, `/api/v1/community` and `/api/v2/community` show only opted-in members.
- `docs/security.md` mentions explicit consent on grant applications. That form is the legacy flow; **Requires confirmation:** whether programme applications record a consent checkbox. No such field was found in `ApplicationService`.

## Minimisation and exposure through the API

API Resources in `app/Http/Resources/Api/V1` and `app/Http/Resources/Api/V2` whitelist fields. Project, programme, news, partner, publication, community and stats endpoints never return e-mail addresses, phone numbers or demographic fields, in either version. The v2 album endpoints return album and photo data only for published albums and existing files. v2 `updated_since` lets a client copy all public data incrementally, and v2 has no tombstones, so a copy can keep a record that was later unpublished or deleted. **Requires confirmation:** whether the Hub accepts that downstream copies of unpublished records cannot be recalled through the API. Programme forms are hidden (`public_programmes_api_hides_drafts_and_forms`). The `community` endpoint returns name, affiliation, institution, bio, XP and badges for opted-in members only.

## Data in transit

- Production forces HTTPS URLs when `APP_URL` starts with `https://` (`URL::forceHttps()` in `AppServiceProvider`).
- `Strict-Transport-Security: max-age=31536000; includeSubDomains` is sent on secure requests.
- Render terminates TLS. `TRUSTED_PROXIES=*` in `render.yaml` makes Laravel trust the forwarded protocol and client IP. See [secure configuration](secure-configuration.md).
- SMTP uses STARTTLS or SSL/TLS according to the mail setting (`MailSettings::ENCRYPTIONS`). Choosing "None" is possible and is not blocked.

## Data at rest

| Item | Protection | Gap |
| --- | --- | --- |
| Passwords | bcrypt | none |
| One-time codes | bcrypt, 10 minute life | none |
| SMTP password in `settings` | `Crypt::encryptString` with `APP_KEY`; redacted from audit diffs; never sent to the browser (`smtp_password_is_stored_encrypted_and_kept_when_left_blank`) | Rotating `APP_KEY` makes it undecryptable (see [incident response](security-incident-response.md)) |
| Other PII columns | Plain text in PostgreSQL | No column-level encryption. Recommended, not implemented |
| Sessions | Plain rows in `sessions` | `SESSION_ENCRYPT=false`. Session payloads hold CSRF token and flash data, not PII, but `password_reset_email` is stored during reset |
| Documents | `DOCUMENTS_DISK` = `private` (local, not web-served) or `s3` (private bucket) | On Render's free plan the local disk is ephemeral. Use S3-compatible storage for real data |
| Gallery photos | `gallery` disk, written with visibility `private`, served through `media.photo` route only for published albums | See [GalleryService](../../app/Services/GalleryService.php) |
| Database | Render PostgreSQL | Provider-managed. No application-level encryption or backup job is in the repository |
| Backups | Not implemented in the repository | See [backup and restore](../07-database/backup-and-restore.md) |

## Files and uploads

- Documents are stored under `reports/`, `publications/` or `proposals/` with generated names `<prefix>_<yyyymmdd>_<16 random chars>.pdf` ([`DocumentStorage::store`](../../app/Services/DocumentStorage.php)). The client file name is never used.
- Downloads stream from the disk with `Content-Type: application/pdf`, `X-Content-Type-Options: nosniff`, `Cache-Control: private, max-age=0, must-revalidate`, and a slugified file name. Paths containing `..` or outside the allowed folders resolve to 404 (`stored_paths_cannot_escape_their_folder`).
- Gallery photos are decoded with GD and re-encoded as JPEG in two sizes, which removes EXIF (including GPS) and any appended payload. A fixture in `GalleryTest` asserts that a fake GPS marker does not survive. Orientation is read first so photos stay upright.
- `app/Support/Photos.php` is not an upload handler. It is a static catalogue of four bundled campus photos with licence credits.

## Audit trail

`AuditLogger` writes `activity_logs` (actor, action, description, IP, properties, subject). Passwords and `remember_token` are recorded as `changed`; the SMTP password is redacted through `SettingsService::update(..., redact: ['mail_password'])`. The model is append-only by convention. There is no database trigger or permission that prevents a database administrator from editing rows.

## Data subject requests and deletion

No self-service export or account deletion exists for members. Deleting a member is a staff action. Related rows may remain (applications, audit entries keep `user_id`). **Requires confirmation:** the cascade rules for member deletion; see foreign keys in [indexes and constraints](../07-database/indexes-and-constraints.md).

## Gaps

| Gap | Note |
| --- | --- |
| No retention or pruning jobs | `activity_logs`, `verification_codes`, notifications and `failed_jobs` grow without limit |
| No member self-service export or deletion | Handled manually by staff |
| No column-level encryption for PII | Database access exposes it |
| Members CSV file is unprotected once downloaded (the export is audited as `member.exported`) | See [authorization security](authorization-security.md#demographics-privacy) |
| Free Render disk is ephemeral | Uploaded documents can be lost unless S3 is configured |
