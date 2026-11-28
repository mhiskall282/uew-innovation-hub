# Non-functional requirements

> **Audience:** Product owners, architects, testers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Functional requirements](functional-requirements.md), [Security overview](../08-security/security-overview.md), [Scaling considerations](../09-deployment-and-operations/scaling-considerations.md), [Testing strategy](../10-testing-and-quality/testing-strategy.md)

Non-functional requirements (NFR-n) describe quality: security, performance, accessibility, reliability, maintainability. Labels: **Implemented (verified in `<file>`)**, **Inferred**, **Proposed**. The platform has no written service-level targets, so performance and availability items are Proposed.

## Security and privacy

| ID | Requirement | Label |
| --- | --- | --- |
| NFR-1 | All forms are CSRF protected. | Implemented (verified in `tests/Feature/SecurityHeadersTest.php::test_forms_are_csrf_protected`) |
| NFR-2 | Responses carry security headers and a strict content security policy (with `unsafe-eval` for Alpine.js). | Implemented (verified in `app/Http/Middleware/SecurityHeaders.php`, `SecurityHeadersTest`; accepted risk F-11) |
| NFR-3 | Access is enforced on the server by permissions and policies, not by hiding links. | Implemented (verified in `PrivilegesTest::test_every_state_changing_workspace_route_enforces_its_permission`, `RouteSmokeTest`) |
| NFR-4 | One-time codes are hashed, single use, expire in 10 minutes, allow 5 attempts. | Implemented (verified in `VerificationService`, `MemberAccountTest`) |
| NFR-5 | Rate limits: sign-in 10 per minute, contact form 3 per minute and 20 per day per IP, registration 5 per minute and 30 per day, API 60 per minute. | Implemented (verified in `routes/web.php`, `AppServiceProvider::boot()`) |
| NFR-6 | Role, active flag and verification date cannot be mass-assigned. | Implemented (verified in `app/Models/User.php`, `MemberAccountTest::test_role_cannot_be_injected_at_sign_up`) |
| NFR-7 | The stored SMTP password is encrypted. | Implemented (verified in `AdministrationTest::test_smtp_password_is_stored_encrypted_and_kept_when_left_blank`) |
| NFR-8 | Uploaded documents are streamed through the application from a private disk, with `nosniff`, and stored paths cannot escape their folder. | Implemented (verified in `DocumentAccessTest`) |
| NFR-9 | Gallery images are decoded and re-encoded; disguised files and tiny images are rejected; metadata stripped. | Implemented (verified in `GalleryTest`) |
| NFR-10 | CSV exports neutralise cells that start with `=`, `+`, `-`, `@`. | Implemented (verified in `ApplicationService::cell()`) |
| NFR-11 | Well-known default passwords are refused when `BLOCK_DEFAULT_PASSWORDS=true`; demo accounts are moved to `SEED_PASSWORD`. | Implemented (verified in `SecurityHardeningTest`, `SecureDemoAccountsTest`). **Not switched on** in `render.yaml`. |
| NFR-12 | Staff multi-factor authentication. | Proposed (F-12) |
| NFR-13 | A legal review of Privacy and Terms before launch. | Proposed (handover report) |
| NFR-14 | Pages in the workspace are not indexed by search engines; the whole site is `noindex` unless `SEARCH_INDEXING=true`. | Implemented (verified in `AccessControlTest::test_workspace_pages_are_not_indexed_by_search_engines`, `SecurityHardeningTest`) |

## Performance and capacity

| ID | Requirement | Label |
| --- | --- | --- |
| NFR-15 | Public and dashboard metrics are cached for 300 seconds by default. | Implemented (verified in `config/hub.php` `metrics_ttl`) |
| NFR-16 | API responses are cacheable for 60 seconds and paginated at most 50 per page. | Implemented (verified in `Api\V1\ApiController`) |
| NFR-17 | Large exports and broadcasts use streaming and chunked reads (`lazy(500)`, `lazyById(200)`). | Implemented (verified in `ApplicationService::exportCsv()`, `SendBroadcast`) |
| NFR-18 | Page speed targets (for example a response time budget). | Proposed. Performance was not measured; the audit run was cancelled (handover report, "Performance: 60"). `tests/e2e/measure-performance.mjs` exists. |
| NFR-19 | Broadcast size limits. | Inferred: no cap is in code; the queue worker handles 200 recipients per chunk with a 600-second job timeout. |

## Reliability and availability

| ID | Requirement | Label |
| --- | --- | --- |
| NFR-20 | A health check at `/up`. | Implemented (verified in `SecurityHeadersTest::test_health_check_is_available`, `render.yaml` `healthCheckPath`) |
| NFR-21 | Migrations run on every boot and are safe to repeat. | Implemented (verified in `docker/entrypoint.sh`) |
| NFR-22 | A queue worker is restarted if it exits. | Implemented (verified in `docker/entrypoint.sh`) |
| NFR-23 | Available at all times. | Not met on the test site (free plan sleeps). Proposed: paid plan, uptime monitor. |
| NFR-24 | Database backups and a restore test. | Proposed. No backup job exists in the repository; free Render Postgres expires after 30 days. |
| NFR-25 | Missing uploaded files must not break pages. | Implemented for gallery (verified in `GalleryTest::test_photos_whose_files_were_lost_are_hidden_not_broken`) |

## Usability and accessibility

| ID | Requirement | Label |
| --- | --- | --- |
| NFR-26 | WCAG 2.2 A/AA with no automated violations on 30+ screens. | Implemented (verified in `tests/e2e/a11y.spec.ts`; result reported in `docs/audit`) |
| NFR-27 | Layout works from 320 px to 1920 px with no horizontal scrolling. | Implemented (verified in `tests/e2e/responsive.spec.ts`) |
| NFR-28 | Animations respect `prefers-reduced-motion`. | Implemented (verified in `resources/css/app.css`) |
| NFR-29 | Screen-reader testing by a person. | Proposed (not done, per audit report) |
| NFR-30 | British English text and Ghana cedi currency (`GH₵`). | Implemented (verified in `config/hub.php`) |

## Maintainability and operations

| ID | Requirement | Label |
| --- | --- | --- |
| NFR-31 | Code style is checked automatically (Pint). | Implemented (verified in `.github/workflows/ci.yml`) |
| NFR-32 | Tests run on SQLite and PostgreSQL in CI. | Implemented (verified in `.github/workflows/ci.yml`) |
| NFR-33 | Dependencies are audited in CI. | Implemented (verified in `.github/workflows/ci.yml`, "Dependency audit") |
| NFR-34 | Business logic lives in services, not controllers. | Implemented (verified in `app/Services`); `AGENTS.md` states it as a commitment |
| NFR-35 | Environment-specific values come from environment variables. | Implemented (verified in `config/hub.php`, `.env.example`). `GALLERY_DISK` is missing from `.env.example`. |
| NFR-36 | Logs go to stderr in production. | Implemented (verified in `render.yaml` `LOG_CHANNEL=stderr`) |
| NFR-37 | Structured monitoring and alerts. | Proposed. None found in the repository. |
| NFR-38 | A browser-compatibility matrix. | Implemented in tests: Chromium, Firefox, WebKit and mobile (verified in `package.json` scripts and CI; WebKit/iOS runs only in CI per handover report) |
