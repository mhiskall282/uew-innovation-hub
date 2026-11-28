# Backend Architecture

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Application architecture](application-architecture.md), [Business logic](business-logic.md), [Dependency map](dependency-map.md), [Backend development](../05-developer-guide/backend-development.md), [Environment configuration](../09-deployment-and-operations/environment-configuration.md)

This page describes the server side in detail: bootstrapping, the service provider, the service catalogue, models and traits, storage, caching, queues, mail, notifications, error handling and console commands.

## Bootstrapping

[`bootstrap/app.php`](../../bootstrap/app.php) configures the application.

| Setting | Value |
| --- | --- |
| Routing | `routes/web.php`, `routes/api.php` (which loads `routes/api/v1.php` and `routes/api/v2.php`), `routes/console.php`; health check `/up` |
| Middleware aliases | `active` = `EnsureAccountIsActive`, `verified.email` = `EnsureEmailIsVerified`, `onboarded` = `EnsureOnboarded`, `api.version` = `ApiVersion` |
| Group additions | `SecurityHeaders` appended to `web` and `api` |
| CSRF exception | `unsubscribe/*` (RFC 8058 one-click POST from mail providers; the signed URL authorises it) |
| Trusted proxies | From `TRUSTED_PROXIES` (`*` or a comma list). `render.yaml` sets `*`. Without it, client IPs, rate limits and the audit log see the proxy address. |
| Guest redirect | `route('login')` |
| Redirect when already signed in | `User::homeUrl()`: workspace for staff, portal for members |
| JSON rendering | Any `api/*` request, or any request that expects JSON |
| Invalid signature | Web: `auth.link-expired` view with status 403. API: falls through to the API envelope as a 403. |

## AppServiceProvider

[`AppServiceProvider`](../../app/Providers/AppServiceProvider.php) is the only custom provider (`bootstrap/providers.php`).

| What | Detail |
| --- | --- |
| Singleton | `SettingsService` is shared per request. |
| Permission gates | One `Gate::define` for each `Permission` case, calling `User::hasPermission()`. |
| Rate limiters | `api` per IP, `API_RATE_LIMIT_PER_MINUTE` (default 60), shared by `/api`, v1 and v2; `contact-messages` 3/min and 20/day per IP; `registrations` 5/min and 30/day per IP; `password-codes` 3/min per IP and 10/hour per e-mail+IP. |
| Mail settings | `MailSettings::apply()` overrides mail config from stored settings, except during unit tests. |
| Mail safety net | Outside production, `MAIL_ALWAYS_TO` (config `mail.always_to`) sends every message to one address. Ignored in production. |
| Lazy loading | `Model::preventLazyLoading()` in every environment except production. |
| Password rules | Production: minimum 12, letters, mixed case, numbers. Other environments: minimum 8. |
| HTTPS | `URL::forceHttps()` when production and `APP_URL` starts with `https://`. |
| Pagination views | `pagination.default` and `pagination.simple`. |

Other fixed throttles are declared on routes (`throttle:10,1` for sign-in, `throttle:5,10` for code resend, and so on). See [Rate limiting](../06-api-reference/rate-limiting.md).

## Open data API

| Part | Where | Notes |
| --- | --- | --- |
| Version list | `Api\VersionsController` (`GET /api`) | Reads `config/api.php`: current version, status, deprecation and sunset dates, docs URL. |
| Version middleware | [`ApiVersion`](../../app/Http/Middleware/ApiVersion.php), alias `api.version` | Sets `API-Version` on every response. Adds `Deprecation`, `Sunset` and `Link rel="deprecation"` when `API_V1_*` or `API_V2_*` dates are set. |
| v1 | `app/Http/Controllers/Api/V1`, `app/Http/Resources/Api/V1` | Frozen contract. `ApiController` gives `ok()`, `paginated()`, `perPage()` (1 to 50) and a 60 second public cache with a weak ETag. |
| v2 | `app/Http/Controllers/Api/V2`, `app/Http/Resources/Api/V2` | `V2\ApiController` validates `limit` (1 to 100), `cursor`, `sort`, `updated_since` and `include`, cursor-paginates, and builds `{data, meta, links}` with `Cache-Control` and a weak ETag. Controllers override `sorts()` and `includes()` to whitelist values. `V2\Resource` formats ids as strings, money as `{amount, currency}` and dates in ISO 8601. |
| Config | [`config/api.php`](../../config/api.php) | `current`, per-version status and dates, `rate_limit_per_minute`, `cache_seconds`, v2 `default_limit` 20 and `max_limit` 100. |
| Tests | `tests/Feature/Api/PublicApiTest.php` (v1), `tests/Feature/Api/ApiV2Test.php` (v2, version headers, ETags) | |

Controllers in both versions read models through public scopes and return resources. They do not use services, except `StatsController`, which uses `MetricsService`. See [Request and response lifecycle](request-response-lifecycle.md#api-request-lifecycle) and [ADR-14](architectural-decisions.md#adr-14-api-versioning-by-url-prefix).

## Service catalogue

All services are in `app/Services`, `final`, constructor-injected. Dependencies between them are in [Dependency map](dependency-map.md).

| Service | Responsibility |
| --- | --- |
| `ApplicationService` | A member's draft, submit, withdraw; staff review and bulk review; application file download; CSV export (formula-injection guard, audited as `application.exported`); XP and notices. |
| `AuditLogger` | Single write path to `activity_logs`. Actor and IP come from the session and request. `diff()` builds field-level changes and hides password values. |
| `BroadcastService` | Audience query (verified, active, opted-in members), moves a broadcast to `sending`, dispatches `SendBroadcast`. A job failure sets the status `failed`. |
| `ContactMessageService` | Stores public enquiries, notifies staff with `messages.manage`, status changes. |
| `ConversationService` | Application messages between applicant and staff. Plain text. Read markers. |
| `DocumentStorage` | Store, replace, delete, check and stream PDFs from the documents disk. Resolves legacy file names. Rejects paths that leave the folder. |
| `EngagementService` | XP, levels and achievements (badges). Awarding the same type for the same programme twice adds nothing. |
| `GalleryService` | Albums and photos. Decodes and re-encodes every upload with GD. Resizes, flattens, applies EXIF orientation. Refuses images over 30 megapixels. |
| `GrantApplicationService` | Review of legacy grant applications and the status e-mail. |
| `InsightsService` | Figures for the Reports page and PDF, for a `DateRange`. Demographics are totals only. |
| `MailSettings` | Outgoing mail configured in the workspace. SMTP password stored encrypted with `APP_KEY`. |
| `MemberService` | Registration, e-mail verification (code or link), onboarding, profile, unsubscribe. |
| `MetricsService` | Cached public and workspace figures. Flushed by the `FlushesMetrics` trait. |
| `NotificationCenter` | In-app (`database` channel) notifications to one user or to staff holding a permission. Never throws. |
| `PostService` | News and blog posts. Decides `published_at` according to the editor's permission. |
| `ProgramService` | Programmes: create, update, delete, status changes, announcement broadcast. |
| `ProgressReportService` | Submit and review progress and budget reports. Totals are recomputed on the server. |
| `ProjectService` | Projects, report file, partner links. |
| `PublicationService` | Publications, PDF file, download counter. |
| `ReportExportService` | Streams the project portfolio as CSV in chunks, with formula-injection guard. Audits each export as `report.projects_exported`. |
| `SettingsService` | Key/value site settings merged over defaults, cached forever under `hub.settings` until changed. |
| `StaffNotifier` | Queues e-mail to active staff holding a permission who have not opted out. |
| `UserService` | Staff accounts, password changes, session revocation, last-Hub-Director guard, self-role guard. |
| `VerificationService` | Six-digit codes and signed-link records. See [Authentication architecture](authentication-architecture.md). |

## Models and traits

20 Eloquent models in `app/Models`. Enums are cast on the model (for example `User::role` to `Role`). Three traits:

| Trait | Effect | Used by |
| --- | --- | --- |
| `HasUniqueSlug` | Builds a unique slug from the title on create, including soft-deleted rows. Route key is `slug`. | `Album`, `Post`, `Program`, `Project`, `Publication` |
| `Searchable` | `scopeSearch()`: case-insensitive, escapes `LIKE` wildcards, same behaviour on SQLite, MySQL and PostgreSQL. | `ActivityLog`, `ContactMessage`, `GrantApplication`, `Organization`, `Post`, `Program`, `ProgramApplication`, `Project`, `Publication`, `User` |
| `FlushesMetrics` | Calls `MetricsService::flush()` on model writes. | `GrantApplication`, `Organization`, `Project`, `Publication`, `ProgramApplication` |

Soft deletes: `GrantApplication`, `Post`, `Program`, `Project`, `Publication`.

## Storage

| Disk | Root | Used for | Notes |
| --- | --- | --- | --- |
| `private` | `storage/app/private` | Documents when `DOCUMENTS_DISK=private` (default) | `serve` false |
| `local` | `storage/app/private` | Laravel default | `serve` true |
| `gallery` | `storage/app/gallery` | Gallery photos when `GALLERY_DISK=gallery` (default) | `serve` false. Photos are served through `/media/photos/...`. |
| `public` | `storage/app/public` | Not used by the application code paths documented here | The `public/storage` symlink is not created by the entrypoint. |
| `s3` | Bucket from `AWS_*` | Documents or gallery when set to `s3` | Works with Cloudflare R2 (`AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT`). |

`DocumentStorage::store()` always names the file `{prefix}_{Ymd}_{16 random chars}.pdf` and returns the path relative to the disk root. Folders: `reports`, `publications`, `proposals` (legacy proposals fall back to `reports`), and `applications` for programme uploads.

## Caching, sessions and queues

| Concern | Driver (default and Render) | Keys and behaviour |
| --- | --- | --- |
| Session | `database` (`SESSION_DRIVER`), 120 minutes | Table `sessions` has `user_id`, so `UserService::revokeOtherSessions()` can delete a user's other sessions. |
| Cache | `database` (`CACHE_STORE`) | `hub.settings` (forever, flushed on change); `hub.metrics.public`, `hub.metrics.workspace`, `hub.metrics.years`, `hub.metrics.open_applications` (TTL `METRICS_CACHE_TTL`, default 300 s); rate limiter counters. |
| Queue | `database` (`QUEUE_CONNECTION`) | Table `jobs`. Worker started by `docker/entrypoint.sh`. |
| Failed jobs | `failed_jobs` table | `php artisan queue:failed` and `queue:retry` are Laravel built-ins. |

> **Warning:** Rate limiters share the `cache` table. Clearing the cache (`php artisan cache:clear`) also resets all sign-in lockouts and verification resend timers.

## Mail

| Class | Queued | Purpose |
| --- | --- | --- |
| `TemplatedMail` | Only when sent with `->queue()` | One class for every templated message. `templateKey` selects a definition in `EmailTemplates`. Adds `List-Unsubscribe` headers when `unsubscribeUrl` is given. |
| `GrantStatusUpdatedMail` | Yes (`ShouldQueue`) | Legacy grant decision |
| `NewContactMessageStaffMail` | Yes | Staff notice of a new enquiry |
| `PasswordChangedMail` | Yes | Security notice after a password change |

`TemplatedMail` has `$tries = 3`. `VerificationService::issue()` uses `Mail::send()`, so code e-mails go out inside the request. A mail failure there surfaces to the user as a server error. Welcome mail, application notices and broadcasts use `->queue()`.

## Jobs

`SendBroadcast` (`app/Jobs`): `$tries = 1`, `$timeout = 600`. It loads the broadcast, returns if the status is not `sending`, then streams recipients with `lazyById(200)` and queues one `TemplatedMail` per member with a signed unsubscribe link. It finally sets status `sent`, `recipients_count` and `sent_at`.

`SendBroadcast::failed()` runs when the job throws. It sets the broadcast status to `failed` (`BroadcastStatus::Failed`, shown in red) and writes the audit action `broadcast.failed` with the first 300 characters of the error.

> **Warning:** If the job fails part-way, some members may already have mail queued. There is no automatic retry (`$tries = 1`) and no screen to resend a failed broadcast. Check `failed_jobs` and the audit log.

## In-app notifications

`HubNotification` uses only the `database` channel. `NotificationCenter` writes to the `notifications` table. The bell component and `/notifications` read them. Links are stored as relative paths (`HubNotification::relative()`), so they survive a change of domain.

## Error handling

- Web: Laravel defaults plus custom views in `resources/views/errors` for 403, 404, 419, 429, 500, 503.
- API v1: `{"success": false, "message": "...", "errors": {...}}`. API v2: `{"error": {"status", "code", "message", "details"}}` with codes `bad_request`, `unauthenticated`, `forbidden`, `not_found`, `method_not_allowed`, `validation_failed`, `rate_limited`, `server_error`, `service_unavailable` and `http_error`. Both are mapped in `bootstrap/app.php`. When `APP_DEBUG` is true the 500 message is the exception message. See [API error codes](../06-api-reference/error-codes.md).
- Notification and welcome-mail failures are logged as warnings and swallowed.
- `LOG_CHANNEL=stderr` on Render, so logs appear in the Render log stream. See [Logging and monitoring](../09-deployment-and-operations/logging-and-monitoring.md).

## Console commands

| Command | Source | Purpose |
| --- | --- | --- |
| `php artisan hub:create-admin` | `CreateAdministrator` | Creates the first Hub Director from `ADMIN_EMAIL`, `ADMIN_NAME`, `ADMIN_PASSWORD` if none exists. |
| `php artisan hub:import-documents {--source=}` | `ImportLegacyDocuments` | Copies legacy report and publication PDFs onto the documents disk. |
| `php artisan hub:secure-demo-accounts {--dry-run}` | `SecureDemoAccounts` | Replaces well-known default passwords with `SEED_PASSWORD`. |

`routes/console.php` registers no schedule. See [Architectural decisions](architectural-decisions.md).
