# Security Overview

> **Audience:** developers, administrators, reviewers of the system · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Threat model](threat-model.md), [Authentication security](authentication-security.md), [Authorization security](authorization-security.md), [Secure configuration](secure-configuration.md), [Technical debt register](../12-maintenance/technical-debt-register.md), [`docs/security.md`](../security.md)

This section documents how the UEW Innovation Hub protects accounts, personal data and uploaded files, which controls exist in code today, and which gaps remain. Every statement was checked against the source files linked in each page. Items that are recommended but not built are labelled "Recommended, not implemented".

## Scope and surfaces

| Surface | Path | Who can reach it | Main controls |
| --- | --- | --- | --- |
| Public website | `/` (`site.*`) | Anyone | CSRF on forms, honeypots, per-IP rate limits, Markdown escaping, `published` filters |
| Member portal | `/portal` (`portal.*`) | Verified, onboarded members | `auth`, `active`, `verified.email`, `onboarded` middleware, model policies |
| Staff workspace | `/workspace` (`workspace.*`) | Staff roles | Same middleware plus `can:workspace.access` and per-route `can:<permission>` |
| Open data API | `/api/v1`, `/api/v2` (`api.v1.*`, `api.v2.*`) | Anyone (read-only, by design) | `throttle:api` (60/min/IP, shared), whitelisted Resource fields, whitelisted v2 `sort` and `include` |
| E-mail links | `/email/verify/...`, `/password/reset/...`, `/unsubscribe/...` | Holder of the link | Signed URLs, one-time records, confirm-then-POST |
| Uploads | PDF documents, gallery photos | Staff and members | MIME checks, random names, private disks, GD re-encode for photos |
| Infrastructure | Render, Docker, PostgreSQL | Operators | Hardened `php.ini`, Apache vhost, non-debug production, HTTPS |

## Control summary

| Area | What exists | Source |
| --- | --- | --- |
| Password storage | bcrypt (`BCRYPT_ROUNDS`, default 12; tests use 4) | `.env.example`, `phpunit.xml` |
| Password rules | Production: 12+ characters, letters, mixed case, numbers. Other environments: 8+ | [`AppServiceProvider.php`](../../app/Providers/AppServiceProvider.php) |
| Sign-in throttling | 5 failures per e-mail+IP and 20 per account in 15 minutes; route limit 10/min/IP | [`LoginRequest.php`](../../app/Http/Requests/Auth/LoginRequest.php), `routes/web.php` |
| One-time codes | 6 digits, bcrypt-hashed, 10 minute expiry, 5 attempts, row-locked | [`VerificationService.php`](../../app/Services/VerificationService.php) |
| Session | Database driver, 120 minutes, regenerated on sign-in, invalidated on sign-out | [`SessionController.php`](../../app/Http/Controllers/Auth/SessionController.php) |
| Roles | Six roles, 20 permissions registered as gates, policies for records | [`Role.php`](../../app/Enums/Role.php), [`Permission.php`](../../app/Enums/Permission.php) |
| Headers | CSP, nosniff, frame, referrer, permissions, COOP, HSTS on HTTPS | [`SecurityHeaders.php`](../../app/Http/Middleware/SecurityHeaders.php) |
| Audit | Append-only `activity_logs` written by `AuditLogger` | [`AuditLogger.php`](../../app/Services/AuditLogger.php) |
| Uploads | PDFs by `mimes:pdf` + `mimetypes:application/pdf`; photos decoded and re-encoded as JPEG | [`DocumentStorage.php`](../../app/Services/DocumentStorage.php), [`GalleryService.php`](../../app/Services/GalleryService.php) |
| Dependency checks | `composer audit` and `npm audit --omit=dev --audit-level=high` in CI | [`ci.yml`](../../.github/workflows/ci.yml) |

## Known gaps (verified)

The full list with owners lives in the [technical debt register](../12-maintenance/technical-debt-register.md). The security-relevant ones:

| Gap | Evidence |
| --- | --- |
| No two-factor authentication for any role, including the Hub Director | No code path; `docs/security.md` also lists it as not implemented |
| `render.yaml` sets `SEED_DEMO_DATA=true` and does not set `BLOCK_DEFAULT_PASSWORDS` | [`render.yaml`](../../render.yaml). The hosted demo is protected only if `SEED_PASSWORD` is strong (see [secrets management](secrets-management.md)) |
| `render.yaml` sets `MAIL_MAILER=log` | With the log mailer, verification codes and reset links are written to the log stream. See [security monitoring](security-monitoring.md) |
| A tester password exists in git history, commit `87df882` | `scripts/security-scan.ps1` reports it; history was not rewritten |
| The CSP allows `'unsafe-eval'` and inline styles | `SecurityHeaders.php`; needed by Alpine.js |
| No automated secret scanning, SAST or Dependabot config in CI | `.github/workflows/ci.yml` has no such job; `.github/dependabot.yml` does not exist |
| No centralised log monitoring or alerting | Logs go to stderr (`LOG_CHANNEL=stderr`); nothing consumes them |
| No scheduled pruning of `verification_codes`, `activity_logs`, notifications or failed jobs | `routes/console.php` has only the default `inspire` command |
| The open data API is unauthenticated | By design; it exposes whitelisted public fields only |
| Programme questions can be set to pre-fill from gender or date of birth, and answers are visible to reviewers | See [authorization security](authorization-security.md#demographics-privacy) |

## Outdated existing documentation

[`docs/security.md`](../security.md) was written before the programme-application system. These statements no longer match the code:

- "There is no self-service password reset by email." A reset exists (`password.request`, `password.email`, `password.update`, plus a signed-link flow).
- Grant-form statements (consent, public tracking by reference number, honeypot on the grant form). `/apply` and `/track` are now redirects; applications use `programmes/{program}/apply`. `grant_applications` is the legacy table.
- The production checklist says `SEED_DEMO_DATA=false`; `render.yaml` sets it to `true` for the hosted demo.

## Reporting and response

For a suspected incident follow [security incident response](security-incident-response.md). For day-to-day practice see [security best practices](security-best-practices.md).
