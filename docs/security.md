# Security

> **Note:** This is an earlier summary. The maintained, code-verified documentation is in the [documentation portal](README.md); see [08 Security](08-security/security-overview.md). Where the two disagree, the portal is correct.

## Access control

- The public website has no link to the staff workspace. Workspace pages send `noindex, nofollow`.
- Every workspace route requires sign-in, an active account and the `workspace.access` permission. Each action then runs a model policy (`app/Policies`), and pages are additionally gated by permission names (`can:*`).
- Roles and permissions are defined once in `app/Enums/Role.php`. `tests/Feature/Workspace/AccessControlTest.php` checks every role against every page.
- Privilege fields cannot be mass assigned: user `role` and `is_active`, grant `status`, `reviewer_notes`, `reviewed_by`, and post `published_at` are set only by services.
- Interns can only create drafts and edit or delete their own unpublished posts; publish requests from them are ignored.
- The last active Hub Director cannot be demoted, deactivated or deleted, and admins cannot deactivate or delete themselves.

## Authentication

- No credentials, demo accounts or role switchers appear on the sign-in page.
- Wrong password, unknown email and deactivated account return the same message, so the form cannot reveal which staff emails exist.
- Five failed attempts per email and IP lock sign-in for 15 minutes; the route is also limited to 10 requests per minute per IP.
- The session ID is regenerated on sign-in and invalidated on sign-out (prevents session fixation).
- Deactivating a user, or resetting their password, rotates their remember-me token and deletes their stored sessions. `EnsureAccountIsActive` also signs out a deactivated user on their next request regardless of session driver.
- Production passwords need at least 12 characters with upper and lower case letters and a number. Passwords are hashed with bcrypt.
- The first administrator is created with `php artisan hub:create-admin` from environment variables. There are no default production passwords.

## Applicant and personal data (Data Protection Act, 2012, Act 843)

- Grant applications require explicit consent.
- Applications and proposal PDFs are visible only to roles with `grants.review`.
- Public tracking needs both the reference number and the email used to apply, so references cannot be enumerated. Tracking shows status only, never panel notes.
- Partner contact emails are internal and never appear on the public site or API.
- The API exposes only whitelisted fields of public records (`app/Http/Resources/Api/V1`).

## Input and output

- Every form has a FormRequest with explicit rules; enum fields are validated against the enum.
- Uploads must be PDF by extension and MIME type, up to `MAX_UPLOAD_KB` (20 MB default). Files are stored under random server-generated names outside `public/`.
- Documents are served by record, not by filename. Stored paths are resolved only inside their own folder, so `../` traversal is rejected.
- Blade escapes output by default. Post bodies are rendered from Markdown with raw HTML escaped and unsafe links disabled.
- Spreadsheet formula injection is neutralised in CSV exports.
- The grant form has a honeypot field and per-IP rate limits (3 per minute, 20 per day). Tracking is limited to 10 per minute.
- Eloquent query builder everywhere (prepared statements). Search input escapes LIKE wildcards.
- CSRF protection on all web forms.

## HTTP headers

Set by `app/Http/Middleware/SecurityHeaders.php` on web and API responses:

- `Content-Security-Policy`: `default-src 'self'`, no third-party scripts, styles or fonts (fonts are self-hosted), `object-src 'none'`, `frame-ancestors 'self'`, `form-action 'self'`
- `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`, `Cross-Origin-Opener-Policy: same-origin`
- `Strict-Transport-Security` on HTTPS requests

The CSP allows `'unsafe-eval'` for Alpine.js expressions and `'unsafe-inline'` styles for Alpine transitions and progress bar widths. Removing them would require Alpine's CSP build.

## Audit trail

`activity_logs` records sign-ins, failed sign-ins, sign-outs, password changes, and every create, update and delete of projects, grants, posts, publications, partners and users. Updates store a field-level before/after diff; passwords are recorded as "changed" without values. Actor and IP come from the server session. The application has no code path that edits or deletes log rows. The Hub Director can search the log at `/workspace/audit`.

## Production checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, a unique `APP_KEY`
- [ ] HTTPS only; `SESSION_SECURE_COOKIE=true`; `TRUSTED_PROXIES` set for the platform
- [ ] `SEED_DEMO_DATA=false`; first admin created with `hub:create-admin`; demo accounts not present
- [ ] Strong `ADMIN_PASSWORD`, changed after first sign-in
- [ ] Database backups scheduled
- [ ] Documents on S3/R2 with a private bucket if the host has no persistent disk
- [ ] Real SMTP or mail API configured, sending domain with SPF/DKIM
- [ ] `composer audit` and `npm audit` clean before each release

## Known items

- The `legacy/` folder in this repository contains the old system's hard-coded local database credentials (`root` / `mysql`). It is not deployed, but because the repository is public, treat those credentials as compromised: change them on any machine still running the legacy MySQL server.
- There is no self-service password reset by email; the Hub Director resets passwords from the workspace. Add Laravel's password broker if staff need self-service resets.
- Two-factor authentication is not implemented yet.

## Engagement features (notifications, messages, reports, badges)

| Risk | Control |
| --- | --- |
| Reading another member's notifications | Notifications are always loaded through the signed-in user's own relation; other IDs return 404. |
| Open redirect via notification links | Links are stored as relative paths. The redirect only follows paths starting with a single `/` with no backslashes or control characters. |
| Reading or posting in someone else's application thread | `ProgramApplicationPolicy::message` allows only the applicant or staff with `applications.review`, and never on drafts. |
| Viewing or editing another member's progress report | `ProgressReportPolicy` limits viewing to the author and reviewers, and editing to the author while changes are requested. The portal also returns 404 for reports that aren't yours. |
| Inflated budget totals | `budget_spent` is recomputed on the server from the line items; posted totals are ignored. Categories are whitelisted. |
| XP or badge tampering | Only staff with `members.manage` award badges; standard badges have fixed XP, duplicates are ignored, and every award or removal is audited. |
| Leaking personal data on the community page or API | Opt-in only (`public_profile`), and only name, affiliation, institution, bio, XP and badges are exposed. |
| Reviewer bias | Reviewers see the applicant snapshot without gender or disability. Demographics appear only as totals on Reports. |
| Message flooding | Message and report submissions are rate limited per user. |

## Verified against the live site

A black-box check of the deployment confirmed: HSTS, CSP, `X-Content-Type-Options`, `X-Frame-Options` and `Referrer-Policy` headers; `Secure`, `HttpOnly`, `SameSite=Lax` session cookies; `.env` and `.git` blocked; no framework debug endpoints; protected pages redirect to sign-in; form posts without a CSRF token are rejected with 419; oversized API page sizes are rejected with 422.

Known trade-off: the CSP allows `'unsafe-eval'` because Alpine.js evaluates its inline expressions. Moving to Alpine's CSP build would remove this but requires rewriting every inline expression as a registered component.
