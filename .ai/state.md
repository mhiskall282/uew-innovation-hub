# Current System State

> **Platform**: UEW Innovation Hub
> **Last updated**: 2026-11-24
> **Version**: 1.3.0 (see `changelog.md`)
> **Live test site**: https://uew-innovation-hub.onrender.com (Render, Docker + PostgreSQL, auto-deploys `main`)

## 1. Summary

Laravel 12 / PHP 8.4 / Blade / Tailwind v4 / Alpine.js. One `users` table with roles `super_admin`, `officer`, `reviewer`, `intern`, `viewer` (staff) and `member`. Permission matrix in `app/Enums/Role.php`; every `Permission` case is a Gate.

## 2. Modules

| Module | Key code | Status |
| --- | --- | --- |
| Public site | `Site\*Controller`, `components/layouts/site`, `x-ui.page-hero`, `App\Support\Photos` | Live |
| Accounts (sign-up, OTP verify, onboarding, reset) | `Auth\*`, `MemberService`, `VerificationService` | Live |
| Programmes + form builder (profile pre-fill) | `ProgramService`, `FormSchema`, `Demographics` | Live |
| Applications (drafts, submit, review, bulk, snapshot) | `ApplicationService`, `ProgramApplicationPolicy` | Live |
| Notifications (bell, inbox) | `NotificationCenter`, `HubNotification`, `NotificationController` | New in 1.3 |
| Application messages | `ConversationService`, `Portal/Workspace\MessageController` | New in 1.3 |
| Progress & budget reports, Incubation board | `ProgressReportService`, `ProgressReportPolicy` | New in 1.3 |
| Badges, XP, community leaderboard | `EngagementService`, `Achievement`, `Site\CommunityController` | New in 1.3 |
| Members, broadcasts, CMS pages, templates, SMTP settings | `MemberController`, `BroadcastService`, `PageController`, `EmailTemplates`, `MailSettings` | Live |
| Portfolio (projects, publications, partners, news) | `ProjectService`, `PublicationService`, `PostService` | Live |
| Legacy grant applications | `GrantApplicationService` (read/review only) | Live |
| Reports | `Workspace\ReportController` | Live |
| Open API v1 | `Api\V1\*` (stats, projects, publications, news, partners, programmes, community) | Live |

## 3. Verification

- 197 PHPUnit tests (feature + unit) incl. `RouteSmokeTest` (every GET route x 8 actors, no 5xx), `PrivilegesTest` (144 role/route pairs), `ApiV2Test` and `OperationalFixesTest`. Playwright suites in `tests/e2e`.
- Documentation: `docs/README.md` portal; known issues in `docs/12-maintenance/technical-debt-register.md`; owner actions in `docs/12-maintenance/project-handover.md`.
- API: v2 current, v1 frozen (`config/api.php`).
- CI: `.github/workflows/ci.yml` (Pint, build, Blade lint, SQLite + PostgreSQL tests, audits, Docker build).
- Live black-box check: security headers, cookies, CSRF, sensitive paths, auth redirects (see `docs/security.md`).

## 4. Open items

- Uploaded documents on Render's free plan are lost on redeploy until `DOCUMENTS_DISK=s3` (R2/S3) is configured.
- Render free Postgres expires after 30 days; move to a paid plan or Neon before launch.
- Real SMTP credentials to be entered in Site settings → E-mail (currently `MAIL_MAILER=log`).
- Set `SEED_DEMO_DATA=false` and use a fresh database for the real launch.
- CSP keeps `'unsafe-eval'` for Alpine.js.
