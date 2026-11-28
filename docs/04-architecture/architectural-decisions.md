# Architectural decisions

> **Audience:** Developers, maintainers, technical reviewers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [System architecture](system-architecture.md), [Design patterns](design-patterns.md), [Authorization architecture](authorization-architecture.md), [Authentication architecture](authentication-architecture.md), [Technical debt register](../12-maintenance/technical-debt-register.md)

This page lists the main design decisions behind the Hub. Each entry is marked **Documented ADR** when it comes from [`.ai/decisions.md`](../../.ai/decisions.md), or **Inferred from code** when the decision is visible in code, comments or configuration but has no written record. For inferred decisions, "Rejected alternatives" lists only what the code or its comments state. Where nothing is recorded, the entry says so instead of guessing.

## Index

| ADR | Decision | Source |
| --- | --- | --- |
| [ADR-01](#adr-01-laravel-12-replaces-the-procedural-php-system) | Laravel 12 replaces the procedural PHP system | Documented ADR (ADR-001) |
| [ADR-02](#adr-02-tailwind-css-v4-and-alpinejs-instead-of-a-spa) | Tailwind CSS v4 and Alpine.js instead of a SPA | Documented ADR (ADR-002) |
| [ADR-03](#adr-03-npontu-style-dashboard-with-uew-brand-colours) | Npontu-style dashboard with UEW brand colours | Documented ADR (ADR-003) |
| [ADR-04](#adr-04-normalised-schema-with-zero-loss-legacy-migration) | Normalised schema with zero-loss legacy migration | Documented ADR (ADR-004) |
| [ADR-05](#adr-05-files-are-never-under-public-and-are-streamed-by-controllers) | Files never under `public/`, streamed by controllers | Documented ADR (ADR-005) and inferred |
| [ADR-06](#adr-06-append-only-audit-log) | Append-only audit log | Documented ADR (ADR-006) |
| [ADR-07](#adr-07-e-mail-images-are-hosted-over-https) | E-mail images hosted over HTTPS (`MAIL_ASSET_URL`) | Inferred from code |
| [ADR-08](#adr-08-signed-e-mail-links-get-confirms-post-acts) | Signed e-mail links: GET confirms, POST acts | Inferred from code |
| [ADR-09](#adr-09-photos-are-re-encoded-with-gd-and-exif-is-dropped) | Photos re-encoded with GD, EXIF stripped | Inferred from code |
| [ADR-10](#adr-10-post-state-is-derived-from-published_at) | Post state derived from `published_at` | Inferred from code |
| [ADR-11](#adr-11-database-backed-session-cache-and-queue-with-an-in-container-worker) | Database-backed session, cache and queue, in-container worker | Inferred from code |
| [ADR-12](#adr-12-one-role-to-permission-matrix-registered-as-gates) | One role-to-permission matrix registered as Gates | Inferred from code |
| [ADR-13](#adr-13-nobody-changes-their-own-role) | No self role change | Inferred from code |
| [ADR-14](#adr-14-api-versioning-by-url-prefix) | API versioning by URL prefix, frozen v1, cursor-paginated v2 | Inferred from code |
| [ADR-15](#adr-15-block_default_passwords-is-opt-in) | `BLOCK_DEFAULT_PASSWORDS` is opt-in | Inferred from code |

## Documented ADRs

### ADR-01: Laravel 12 replaces the procedural PHP system

**Source:** Documented ADR (`.ai/decisions.md`, ADR-001). Status: accepted.

- **Context.** The old system was a set of standalone PHP files (`index.php`, `add_project.php`, `auth.php`, `export.php` and others) running direct MySQL queries and mixing HTML into the same files. The ADR describes it as prone to technical debt and security gaps. The code is kept in [`legacy/`](../../legacy/) for reference and is never served.
- **Decision.** Rebuild on Laravel 12 with MVC, Eloquent, form requests and Blade components.
- **Consequences.** CSRF, sessions, migrations and routing come from the framework. The app grew service classes, policies and enums (see [Backend architecture](backend-architecture.md)). The product is larger than the ADR's scope (programmes, applications, gallery, broadcasts); the ADR file itself still describes the original portfolio tool.
- **Rejected alternatives.** Keeping the procedural code and patching it. No other framework is mentioned.

### ADR-02: Tailwind CSS v4 and Alpine.js instead of a SPA

**Source:** Documented ADR (ADR-002). Status: accepted.

- **Context.** Modals, drawers, live filters and toasts are needed, without the cost of a single-page application.
- **Decision.** Tailwind CSS v4 with `@theme` tokens, plus Alpine.js 3, built with Vite. Pages are rendered by Blade on the server.
- **Consequences.** No client-side router or state store. The only HTTP client in the browser bundle is axios, set up in `resources/js/bootstrap.js`. See [Frontend architecture](frontend-architecture.md).
- **Rejected alternatives.** React and Vue are named in the ADR as the "SPA framework" option that was not chosen.

### ADR-03: Npontu-style dashboard with UEW brand colours

**Source:** Documented ADR (ADR-003). Status: accepted.

- **Context.** The project owner asked to keep UEW colours and logos and to adopt the button and dashboard style of the Npontu dashboards.
- **Decision.** Collapsible sidebar, KPI cards in JetBrains Mono, high-contrast buttons, UEW palette and the official crest.
- **Consequences.** Design tokens live in the Tailwind theme. The e-mail layout uses a separate palette sampled from the crest (`#25267a`, `#e41824`, see `resources/views/components/emails/layout.blade.php`), which differs from the web palette listed in `AGENTS.md`.
- **Rejected alternatives.** None recorded.

### ADR-04: Normalised schema with zero-loss legacy migration

**Source:** Documented ADR (ADR-004). Status: accepted.

- **Context.** The legacy schema used irregular names such as `project ID` and `Number of Beneficiaries`.
- **Decision.** Normalised tables (`projects`, `organizations`, `project_organizations`, `publications`, `users`, `activity_logs`) and a `MigrateLegacyDataSeeder` that keeps original integer ids.
- **Consequences.** The schema has since grown to 30 tables (see [Database architecture](database-architecture.md)). The legacy `grant_applications` table is kept so no data is lost, while new work goes through `program_applications`.
- **Rejected alternatives.** None recorded.

### ADR-05: Files are never under `public/` and are streamed by controllers

**Source:** Documented ADR (ADR-005, status accepted) plus code in [`DocumentStorage`](../../app/Services/DocumentStorage.php) and [`GalleryService`](../../app/Services/GalleryService.php).

- **Context.** In the legacy project, PDFs sat in web-accessible folders (`reports/`, `publications/`) protected only by `.htaccess`.
- **Decision.** Store documents on a private disk (`storage/app/private`) and serve them through controllers that authorise first. The ADR adds `X-Content-Type-Options: nosniff` and rate limiting. In code, `DocumentStorage` generates the stored name on the server, so client file names cannot cause path traversal. The disk is `hub.documents_disk` (`DOCUMENTS_DISK`), so an S3-compatible bucket works with the same code. Gallery photos use their own disk (`GALLERY_DISK`, default `gallery`, root `storage/app/gallery`).
- **Consequences.** Every download costs a PHP request. Public project reports and publications are available without sign-in but still pass through a controller (see [Integration architecture](integration-architecture.md)). On Render's free plan the container disk is ephemeral, so durable storage needs the S3 disks.
- **Rejected alternatives.** Files under `public/` guarded by web server rules (the legacy approach).
- **Check against code.** The ADR says the download controller is rate limited. The public document routes are not throttled (see [Integration architecture](integration-architecture.md)).

### ADR-06: Append-only audit log

**Source:** Documented ADR (ADR-006). Status: accepted.

- **Context.** Donor accountability needs a record of who changed project details, registered partners or exported data.
- **Decision.** An immutable `activity_logs` table with user, action verb, subject type and id, properties and IP address, written only through [`AuditLogger`](../../app/Services/AuditLogger.php).
- **Consequences.** Almost every service takes `AuditLogger` in its constructor (see [Dependency map](dependency-map.md)). Since commit `d1296b1`, CSV exports are audited too: `member.exported`, `report.projects_exported` and `application.exported`, next to the existing `report.exported` for PDF. A failed broadcast writes `broadcast.failed`.
- **Rejected alternatives.** None recorded.

## Decisions inferred from code

### ADR-07: E-mail images are hosted over HTTPS

**Source:** Inferred from code. The reasoning is in a comment in [`resources/views/components/emails/layout.blade.php`](../../resources/views/components/emails/layout.blade.php); the setting is `mail.asset_url` in [`config/mail.php`](../../config/mail.php).

- **Context.** Every e-mail shows the UEW crest. Many work and school mail systems block attached images or show them as attachments.
- **Decision.** Reference one public HTTPS URL, `{MAIL_ASSET_URL}/images/uew/email-crest.png`. `MAIL_ASSET_URL` defaults to `APP_URL`. Set it to the live site when sending from a local machine.
- **Consequences.** The image is only visible if the asset URL is reachable from the recipient's mail client. A local `APP_URL` (for example `http://localhost`) shows a broken image. Recipients whose client blocks remote images see no crest until they allow it.
- **Rejected alternatives.** Attached images with CID references (stated in the layout comment). Data URIs are not used either; the code does not record why.

### ADR-08: Signed e-mail links: GET confirms, POST acts

**Source:** Inferred from code. See [`EmailLinkController`](../../app/Http/Controllers/Auth/EmailLinkController.php), [`VerificationService`](../../app/Services/VerificationService.php) and `routes/web.php` (lines around 44 and 79).

- **Context.** E-mail security scanners and some clients pre-fetch every URL in a message.
- **Decision.** Verification and reset buttons are temporary signed URLs (`temporarySignedRoute`, route middleware `signed`) that point to one one-time code record. A GET only shows a confirmation page. The change happens on a POST to the same signed URL. The unsubscribe link follows the same pattern (`site.unsubscribe` and `site.unsubscribe.store`). `unsubscribe/*` is excluded from CSRF so mail providers can POST for one-click unsubscribe (RFC 8058); the signed URL authorises that request.
- **Consequences.** A scanner cannot verify an account, burn a reset link or unsubscribe a member. The user clicks once more. Expired or tampered links show the `auth.link-expired` page with status 403 (`bootstrap/app.php`).
- **Rejected alternatives.** Acting on GET (stated as the problem in the controller docblock).

### ADR-09: Photos are re-encoded with GD and EXIF is dropped

**Source:** Inferred from code. See the class comment in [`GalleryService`](../../app/Services/GalleryService.php).

- **Context.** Members and staff upload phone photos. These carry GPS metadata, may be rotated by EXIF tags, and could be files that only pretend to be images.
- **Decision.** Every upload is decoded with GD and re-encoded to JPEG (quality 82) in two sizes, 1600 px and 640 px. The original is not kept. Orientation from EXIF is applied before the metadata is dropped. Images over 30 megapixels are refused before decoding.
- **Consequences.** No GPS location leaks through the gallery, and a file that is not a real image fails to decode. The cost is CPU and memory during upload, and lossy output. `docker/php.ini` allows 10 photos per request (`post_max_size = 110M`, `max_file_uploads = 12`).
- **Rejected alternatives.** Storing the original file (the class comment says it is not kept). No other alternative is recorded.

### ADR-10: Post state is derived from `published_at`

**Source:** Inferred from code. See [`Post`](../../app/Models/Post.php) (class comment, `scopePublished`).

- **Context.** Staff want to schedule posts. The app has no scheduler: `routes/console.php` holds only Laravel's `inspire` command.
- **Decision.** A post has no status column. `published_at` null means draft, a future value means scheduled, and a past or current value means published. Public queries filter with `published_at <= now()`.
- **Consequences.** A scheduled post goes live at the right second with no cron job, queue job or deploy. There is no "published" event, so nothing happens at that moment (no e-mail, no cache purge). Cached API responses can lag by up to `API_CACHE_SECONDS`.
- **Rejected alternatives.** A status column updated by a scheduled command. Not recorded as discussed; the absence of a scheduler is the observable fact.

### ADR-11: Database-backed session, cache and queue, with an in-container worker

**Source:** Inferred from code. See `config/session.php`, `config/cache.php`, `config/queue.php`, `.env.example`, `render.yaml` and [`docker/entrypoint.sh`](../../docker/entrypoint.sh) step 6.

- **Context.** The app runs on Render's free plan as one Docker container with PostgreSQL. There is no Redis service in `render.yaml`.
- **Decision.** `SESSION_DRIVER`, `CACHE_STORE` and `QUEUE_CONNECTION` default to `database`. The entrypoint starts `php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --memory=128` in a background `while true` loop as `www-data`, unless `RUN_QUEUE_WORKER=false` or `QUEUE_CONNECTION=sync`. `.env.example` notes that Redis is recommended for all three once traffic grows.
- **Consequences.** One service to run and pay for. The worker shares the container's memory with Apache. A crashed worker restarts after 2 seconds, but a container restart stops in-flight jobs. `SendBroadcast` has `$tries = 1`, so a failed broadcast is marked `failed` and not retried. Rate-limit counters live in the `cache` table.
- **Rejected alternatives.** A separate worker service (supported by `RUN_QUEUE_WORKER=false`, not used on Render) and Redis (optional, not configured). `vercel.json` uses `QUEUE_CONNECTION=sync`.

### ADR-12: One role-to-permission matrix registered as Gates

**Source:** Inferred from code. See [`Role`](../../app/Enums/Role.php), [`Permission`](../../app/Enums/Permission.php) and `AppServiceProvider::registerPermissionGates()`.

- **Context.** Staff roles need different powers, and routes, policies and Blade views all need to ask the same question.
- **Decision.** The matrix lives only in `Role::permissions()` (the class comment says "and nowhere else"). `AppServiceProvider` defines one Gate per `Permission` case that calls `User::hasPermission()`. Routes use `can:projects.manage`, views use `@can`, and policies call the same permission checks.
- **Consequences.** Changing a role's powers is a one-file change, and the Roles page reads the same data. Roles are code, not database rows, so adding a role needs a deploy. See [Authorization architecture](authorization-architecture.md) for the matrix.
- **Rejected alternatives.** A database-driven roles and permissions package. Not recorded as discussed; `composer.json` has none.

### ADR-13: Nobody changes their own role

**Source:** Inferred from code. See `UserService::update()` and the guard `guardLastAdministrator()` in [`UserService`](../../app/Services/UserService.php).

- **Context.** The code comment names two risks: self-escalation, and accidental self-demotion that locks the Hub Director out of administration.
- **Decision.** `UserService::update()` throws a validation error when the actor edits their own account and the role differs. It also refuses to change the role of the only active Hub Director.
- **Consequences.** Role changes always need a second Hub Director. A single-director installation cannot change that director's role. Creating another director first is the way out.
- **Rejected alternatives.** None recorded.

### ADR-14: API versioning by URL prefix

**Source:** Inferred from code. See `routes/api.php`, `routes/api/v1.php`, `routes/api/v2.php`, [`config/api.php`](../../config/api.php), [`ApiVersion`](../../app/Http/Middleware/ApiVersion.php) and `bootstrap/app.php`. The retirement steps are written in a comment at the top of `config/api.php`.

- **Context.** The first version (v1) used page numbers and a `{success, data}` envelope. Clients that sync data need cursor pagination, `updated_since` and stable error codes, which would break v1 clients if changed in place.
- **Decision.** Each version has its own URL prefix (`/api/v1`, `/api/v2`), route file, controller namespace (`Api\V1`, `Api\V2`) and resource namespace. v1 is frozen: only additive changes (such as the weak ETag and 304 response) are allowed. v2 is the current version. Differences:

| Aspect | v1 | v2 |
| --- | --- | --- |
| Pagination | Page number (`per_page` 1 to 50, `total`, `last_page`) | Cursor (`limit` 1 to 100, default 20, `next_cursor`, no totals) |
| Success envelope | `{success, data, meta, links}` | `{data, meta, links}` |
| Error envelope | `{success: false, message, errors}` | `{error: {status, code, message, details}}` |
| Ids | Integers or slugs as stored | Strings |
| Money | As stored | `{amount: "1500.00", currency: "GHS"}` |
| Extras | none | `updated_since`, whitelisted `sort` and `include`, `/reference`, `/albums`, `/community?limit=` |

  Every API response gets an `API-Version` header from the `api.version` middleware. When `API_V1_DEPRECATED_AT` or `API_V1_SUNSET_AT` is set (same pattern for v2), it also adds `Deprecation` (RFC 9745), `Sunset` (RFC 8594) and a `Link: <docs>; rel="deprecation"` header. Both versions share the `api` rate limiter (`API_RATE_LIMIT_PER_MINUTE`, default 60 per IP).
- **Consequences.** Two sets of controllers and resources to maintain. Retiring a version needs only dates in the environment first and a route removal later. v2 has no tombstones, so `updated_since` sync cannot see records that were deleted or unpublished. Both versions are public, read-only and unauthenticated.
- **Rejected alternatives.** Versioning by header or by query parameter is not used. The code does not record the reasons. Changing v1 in place was ruled out by the "frozen" rule in `routes/api/v1.php`.

### ADR-15: `BLOCK_DEFAULT_PASSWORDS` is opt-in

**Source:** Inferred from code. See `config/hub.php` (`block_default_passwords`), [`LoginRequest`](../../app/Http/Requests/Auth/LoginRequest.php) and [`SecureDemoAccounts`](../../app/Console/Commands/SecureDemoAccounts.php).

- **Context.** Demo accounts are seeded with the `SEED_PASSWORD` value, which defaults to a well-known word. A tester password was also committed in git history (commit `87df882`).
- **Decision.** When `BLOCK_DEFAULT_PASSWORDS=true`, `LoginRequest` refuses sign-in with any password in `SecureDemoAccounts::WEAK` and writes the audit action `auth.default_password_refused`. The default is `false`. At boot, `docker/entrypoint.sh` runs `php artisan hub:secure-demo-accounts` to move accounts with weak passwords onto `SEED_PASSWORD`.
- **Consequences.** The config comment states the reason: switching it on must not lock testers out unannounced. The risk is that a hosted site with the flag off still accepts well-known passwords. The setting is not forced on in production. There is no forced password change after the first sign-in (see the [Technical debt register](../12-maintenance/technical-debt-register.md)).
- **Rejected alternatives.** Enabling it by default (rejected in the config comment because of the tester lock-out).

## How to add a new decision

1. Write the decision in [`.ai/decisions.md`](../../.ai/decisions.md) using `.ai/templates/adr.md`. Use the sections Context, Decision, Rationale and Status.
2. Add a row to the index above and a section with the next ADR number. Mark it **Documented ADR**.
3. When you find a decision in code that has no ADR, add it here as **Inferred from code** and say which file shows it.
