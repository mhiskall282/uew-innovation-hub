# Changelog

All notable changes to the UEW Innovation Hub Enterprise platform will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [1.5.0] - 2026-11-30

### Added
- Open data API v2 at `/api/v2` (current): cursor pagination (`limit`, `cursor`), `updated_since` incremental sync, whitelisted `sort` and `include`, string ids, money objects, `{data, meta, links}` envelope, machine-readable error codes, `/reference` and `/albums`. Version index at `/api`; `API-Version` header; Deprecation/Sunset headers driven by `config/api.php`.
- Weak ETags and 304 responses on both API versions; CORS exposes API headers to browser apps (`config/cors.php`).
- `/up` health check also queries the database.
- Complete documentation set: `docs/README.md` portal and 13 sections (159 pages, 51 Mermaid diagrams), coverage matrix, changelog and final report; `scripts/validate-docs.php` and `scripts/mermaid-check.mjs` checkers.
- Failed state for broadcasts (`BroadcastStatus::Failed`), set by `SendBroadcast::failed()` and audited.

### Changed
- API v1 is frozen (no breaking changes); it honours `API_CACHE_SECONDS` and the shared, configurable `api` rate limit.
- CSV exports (members, projects, applications) are written to the audit log; the audit filter covers all 18 areas.
- Announcement e-mails are opt-in at onboarding (no pre-ticked box).
- Container upload limits: 12 files, 110 MB per request (gallery batches of 10 x 10 MB).

### Fixed
- Programme lists ran one capacity query per programme (now one subquery).
- Password hints on sign-up and reset now match the real rule outside production.
- Duplicate seeder guard, misplaced config comment, missing `.env.example` keys, unpinned Node version.

## [1.4.0] - 2026-11-27

### Added
- Event photo gallery: albums with multi-photo upload (re-encoded with GD, EXIF/GPS stripped, 1600px and 640px), captions, ordering and cover; public `/gallery` with an accessible lightbox; "Moments from the Hub" on the homepage; gallery entries in the sitemap. Interns draft, officers publish.
- Roles & permissions page (Hub Director): the full matrix in plain language.
- Admin-sent password-reset and verification e-mails (no admin ever sets or sees a password).
- Signed one-click links in verification and reset e-mails (confirm-on-POST, single use, expire with the code); RFC 8058 one-click unsubscribe.
- E-mails redesigned in the official UEW crest colours (blue #25267a, red #e41824) with a hosted crest image.

### Changed
- Nobody can change their own role.

### Fixed
- E-mail images missing in some clients (attached images replaced with a hosted HTTPS image).
- Malformed photo metadata can no longer crash an upload.

## [1.3.0] - 2026-11-24

### Added
- In-app notifications (`notifications` table, bell in site and workspace headers, `/notifications`) for submissions, status changes, messages, report reviews and badges.
- Application messages between applicants and staff.
- Progress and budget reports for accepted applicants; workspace Incubation board with spending vs funding per programme.
- Achievements (winner, finalist, participant, alumni, mentor, custom), XP levels, auto "Participant" badge on acceptance, opt-in public `/community` leaderboard and profiles.
- Member demographics at sign-up and onboarding; applicant snapshot frozen at submission; form-builder "pre-fill from profile".
- Reports: most active members, anonymous gender/age/region figures.
- Reports by period (presets + custom range, `App\Support\DateRange`, `InsightsService`) and branded PDF export with section selection (`/workspace/reports/pdf`, dompdf; GD added to the Docker image).
- API: `/api/v1/programmes`, `/api/v1/programmes/{slug}`, `/api/v1/community`.
- UEW co-branding: official crest in header, auth panel, workspace and footer; licensed campus photography (`App\Support\Photos`) in photo heroes with attribution.
- CI pipeline (`.github/workflows/ci.yml`): Pint, asset build, Blade compile check, tests on SQLite and PostgreSQL, composer/npm audit, Docker build.
- `RouteSmokeTest` (every GET route as every role), `EngagementTest`; docs: member guide, staff guide, stakeholder overview.

### Fixed
- 500 "no such table: notifications" (migration added; bell is fail-safe).
- Staff never received in-app notices: `Notification::send()` was given a LazyCollection chunk.
- Reports 500 when status counts were keyed by enum.
- Notification redirect refuses `/\host` style paths.

### Removed
- Development OTP display; "Local" environment badge in the workspace header.

## [1.0.0] - 2026-11-23

### Added
- **Full Laravel 12 Architecture**: Modern MVC skeleton with separation of concerns across Models, FormRequests, Policies, Controllers, and Domain Services.
- **Npontu SRE Cockpit Design**: Reusable Blade component primitives (`<x-npontu.button>`, `<x-npontu.badge>`, `<x-npontu.card>`, `<x-npontu.stat-card>`), collapsible dark navy sidebar, glowing telemetry indicators, and JetBrains Mono metric counters.
- **Institutional UEW Brand Tokens**: Embedded palette featuring UEW Deep Navy (`#0a1b4d`, `#102a72`), Royal Blue (`#3157d5`), Imperial Purple (`#6d28d9`), Academic Gold (`#f59e0b` / `#fbbf24`), and official UEW Crest.
- **Public Institutional Showcase**: Interactive landing page (`/`) featuring research pillars, live impact counters, featured initiatives, strategic partner directory, and call to action.
- **Dynamic CMS Subsystem**: Engine for publishing and editing News, Research Blogs, and Announcements (`/news-and-blogs` and `/admin/posts`), allowing staff to update content without modifying code.
- **Grant & Incubation Intake Subsystem**: Public proposal application portal (`/apply`) with tracking reference number generation (`UEW-GR-XXXXXX-YYYY`), PDF proposal upload, and committee review dashboard (`/admin/grants`).
- **Granular Multi-Role RBAC**:
  - `super_admin`: Full Hub Director privileges (user provisioning, project deletion, audit log access).
  - `officer`: Research Project Officer (initiative CRUD, grant evaluation, content publishing).
  - `reviewer`: Academic Dean (grant proposal review, evaluation notes).
  - `intern`: Student Intern / NSS Personnel (CMS news/blog drafting, documentation).
  - `viewer`: Public / Academic affiliate (read-only access).
- **Secure File Streaming**: Dedicated `FileStreamController` serving private storage PDFs with strict basename validation, traversal prevention, and `X-Content-Type-Options: nosniff`.
- **Automated Test Suite**: 22 automated feature and unit tests passing across all subsystems with 54 assertions.

### Changed
- Migrated legacy procedural scripts into organized `legacy/` directory for historical preservation.
- Replaced insecure direct web access to `reports/` and `publications/` with protected private storage streaming.
- Replaced raw procedural MySQL queries with Eloquent ORM prepared statements and migration seeders.
