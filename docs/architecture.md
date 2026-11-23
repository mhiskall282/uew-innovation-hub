# Architecture

## Overview

One Laravel 12 application serves three surfaces with different audiences and trust levels:

```
                 ┌───────────────────────────── Laravel 12 application ─────────────────────────────┐
 Public  ──────► │  routes/web.php  site.*        Site\*Controller       → views/site/*               │
 Staff   ──────► │  routes/web.php  workspace.*   Workspace\*Controller  → views/workspace/*          │
 Apps    ──────► │  routes/api.php  api.v1.*      Api\V1\*Controller     → Http/Resources/Api/V1/*    │
                 │                                   │                                              │
                 │          FormRequests (validation) │ Policies + Gates (authorization)            │
                 │                                   ▼                                              │
                 │   Services: ProjectService, GrantApplicationService, PostService,                │
                 │             PublicationService, UserService, MetricsService,                     │
                 │             DocumentStorage, AuditLogger, ReportExportService                    │
                 │                                   │                                              │
                 │            Eloquent models (+ enums, scopes, concerns)                          │
                 └───────────────┬─────────────────┬─────────────────┬──────────────────────────────┘
                                 ▼                 ▼                 ▼
                          SQL database       Cache store       Documents disk         Queue → Mail
                       (SQLite / Postgres)  (database/Redis)  (local or S3/R2)
```

- **Public site** (`/`): read-only pages plus two write actions, grant submission and tracking lookup. It never links to the workspace.
- **Workspace** (`/workspace`): every route requires an authenticated, active account and the `workspace.access` permission, then a policy check per action.
- **API** (`/api/v1`): read-only, public data only, rate limited, cacheable.

## Layers

| Layer | Location | Responsibility |
| --- | --- | --- |
| Routes | `routes/web.php`, `routes/api.php` | URL map, middleware (auth, active account, permission gates, throttles) |
| Form requests | `app/Http/Requests/{Site,Workspace,Auth}` | All input validation and normalisation |
| Controllers | `app/Http/Controllers/{Site,Workspace,Api/V1,Auth}` | Thin: authorize, call a service, return a view or JSON |
| Policies | `app/Policies` | Per-model rules (e.g. interns may edit only their own unpublished drafts) |
| Services | `app/Services` | Business logic, transactions, file handling, audit, notifications |
| Models | `app/Models` | Persistence, relations, casts to enums, query scopes |
| Enums | `app/Enums` | Roles, permissions and status vocabularies with labels and colours |
| Resources | `app/Http/Resources/Api/V1` | The public API contract; only whitelisted fields leave the server |
| Views | `resources/views` | Blade templates built from a shared `x-ui.*` component library |

Rules the codebase follows:

- Controllers never write to models directly for domain records; services do, inside transactions.
- Privileged fields (`users.role`, `users.is_active`, grant `status`/`reviewer_*`, post `published_at`) are not mass assignable. Only services set them.
- Every mutation goes through `AuditLogger::record()`, which takes the actor and IP from the server session, never from input.
- Cached figures are invalidated by the `FlushesMetrics` model trait, not by hand.

## Authorization model

`App\Enums\Role` maps each role to a list of `App\Enums\Permission` cases. `AppServiceProvider` registers each permission as a Gate, so the same names work in routes (`can:audit.view`), Blade (`@can('content.publish')`) and code (`$user->hasPermission(...)`). Model policies build on those permissions and add record-level rules. A deactivated user has no permissions at all and is signed out on the next request by `EnsureAccountIsActive`.

Changing what a role can do means editing `Role::permissions()` and the matrix in `tests/Feature/Workspace/AccessControlTest.php`.

## Data model

```
users ─┬─< projects (created_by)            organizations >─┬─< project_organizations >─┬─ projects
       ├─< posts (author_id)                                 │   (role per link)          │
       ├─< grant_applications (reviewed_by)                  │                            ├─< publications (project_id)
       └─< activity_logs (user_id)                                                        │
activity_logs.subject_type/subject_id ──► any model (polymorphic)
```

| Table | Notes |
| --- | --- |
| `users` | `role` (enum string), `is_active`, `last_login_at`, `last_login_ip` |
| `projects` | Soft deletes, unique `slug`, `status` enum, `is_public` flag, `legacy_id` from the old system, `report_file_path` |
| `organizations` | Partner directory; `contact_email` is internal only |
| `project_organizations` | Many-to-many with a `role` per link; unique per pair |
| `publications` | Soft deletes, `type`, `abstract`, `doi_url`, `pdf_file_path`, atomic `downloads_count` |
| `posts` | Soft deletes; state is derived from `published_at` (null = draft, future = scheduled, past = published) |
| `grant_applications` | Soft deletes; reference `UEW-GR-YYYY-XXXXXX`; `status` enum; reviewer fields set only by the service |
| `activity_logs` | Append-only audit trail with JSON `properties` (field diffs) and polymorphic subject |

Indexes cover the filters actually used: status, category, start date, `is_public`, `(type, published_at)`, `(status, created_at)`, `(user_id, created_at)` and others (see `database/migrations`).

Migrations are additive. `2026_11_25_000001_extend_platform_schema` added new columns with defaults and dropped nothing, so existing records survive upgrades.

## Key flows

**Grant application.** `POST /apply` → `StoreGrantApplicationRequest` (validation, consent, honeypot, PDF MIME check) → throttle `grant-submissions` (3/min, 20/day per IP) → `GrantApplicationService::submit()` stores the PDF under a generated name in `proposals/`, saves the row, writes the audit entry, then queues the confirmation email. The reference is handed to the confirmation page through the session, not the URL.

**Grant review.** `PUT /workspace/grants/{reference}` → policy `review` → `GrantApplicationService::review()` sets status, notes, reviewer and time in a transaction, logs the field diff, and emails the applicant only if the status changed and the reviewer ticked "notify".

**Publishing.** `PostService` ignores publish requests from users without `content.publish`, so an intern's post stays a draft whatever the form sends. Officers choose draft, publish now or schedule.

**Documents.** Files never sit under `public/`. Downloads go through controllers that load the record, check policy and visibility, then stream via `DocumentStorage`. Stored paths are generated server-side and resolved only inside their folder, which blocks path traversal. The disk is configurable (`DOCUMENTS_DISK`), so local disk and S3-compatible storage use the same code.

## Performance and scaling

- **Aggregates, not loops.** Dashboard and public figures come from a few `SUM/COUNT CASE` queries in `MetricsService`, cached for `METRICS_CACHE_TTL` seconds (default 300) and flushed on every relevant write.
- **No N+1 queries.** Lists eager-load relations with selected columns. `Model::preventLazyLoading()` is on outside production, so tests fail if a lazy load slips in.
- **Streaming exports.** The CSV export reads with `lazy(500)` and streams, so memory stays flat with any portfolio size.
- **Pagination everywhere**, API page size capped at 50.
- **HTTP caching.** API responses send `Cache-Control: public, max-age=60` so a CDN or browser can absorb repeated reads. Vite assets are fingerprinted and cached for a year.
- **Queued mail** keeps requests fast and retries failures (3 tries).
- **Stateless web tier.** Sessions, cache and queue use the database by default and can move to Redis by changing three environment variables. Documents can move to S3. That lets you run several app containers behind a load balancer.
- **Database portability.** Year extraction and case-insensitive search use driver-aware SQL, so the same code runs on SQLite, PostgreSQL and MySQL.

Growth path: database sessions/cache → Redis; single container with an in-process queue worker → a separate worker service (`RUN_QUEUE_WORKER=false` on web containers); local documents → S3/R2; add read replicas only if reporting load demands it.

## Front end

- Tailwind CSS v4 with UEW brand tokens in `resources/css/app.css` (`uew-navy`, `uew-royal`, `uew-purple`, `uew-gold`, `uew-ink`, `uew-canvas`).
- Alpine.js for small interactions (mobile nav, collapsible sidebar, password reveal, confirm dialogs, partner filter).
- Inter and JetBrains Mono are self-hosted through Fontsource, so there are no third-party requests and the strict Content-Security-Policy holds.
- `resources/views/components/ui/` holds the component library: `button`, `badge`, `card`, `stat`, `alert`, `empty`, `field`, `input`, `select`, `textarea`, `checkbox`, `progress`, `page-header`, `confirm-form`, `icon`, `crest`.
- The workspace layout follows the Npontu/Opsora operations style: dark grouped sidebar with a gold active indicator, light canvas, dense cards, monospace figures.

## Legacy system

`legacy/` holds the original procedural PHP application for reference. It is excluded from the Docker image and Vercel bundle and is never served. Its report and newsletter PDFs (`reports/`, `publications/`) are copied into the documents disk by `php artisan hub:import-documents`, and the demo seeder keeps their original `legacy_id` values.
