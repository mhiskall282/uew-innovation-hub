# Design Patterns

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Application architecture](application-architecture.md), [Backend architecture](backend-architecture.md), [Coding standards](../05-developer-guide/coding-standards.md), [Adding new features](../05-developer-guide/adding-new-features.md), [Refactoring guidelines](../12-maintenance/refactoring-guidelines.md)

This page names the patterns the code uses, shows one example file for each, and says when to follow them. New code should match them. The last section lists places where the code departs from its own patterns.

## Patterns in use

| Pattern | Where | Example | Follow it when |
| --- | --- | --- | --- |
| **Service layer** | `app/Services` (24 `final` classes) | `ApplicationService::save()` does the transaction, file storage, audit and notification for one use case | Adding any action that writes data or touches more than one model. Keep controllers to authorise, call, respond. |
| **Constructor injection** | Services and some controllers | `ProgramService` receives `AuditLogger` and `BroadcastService` | Always. Laravel's container resolves them. |
| **Enum with behaviour** | `app/Enums` | `Role::permissions()` holds the role matrix. `ApplicationStatus::canWithdraw()`, `isFinal()`, `label()`, `applicantMessage()` | A fixed vocabulary needs labels, colours or rules. One source of truth. |
| **Gate per permission** | `AppServiceProvider::registerPermissionGates()` | Every `Permission` case becomes a Gate. `can:projects.manage` in routes, `@can` in Blade | Adding a permission: add the enum case and it is a gate automatically. |
| **Policy for record rules** | `app/Policies` | `PostPolicy::update()`: author and draft only for interns | The rule depends on the record (owner, state), not just the role. |
| **Form Request** | `app/Http/Requests` | `LoginRequest` holds rules plus the throttle and `authenticate()` | Validation with more than a few rules, or input normalisation (`prepareForValidation`). |
| **Schema-driven forms** | `FormSchema`, `programs.form_schema` | Rules for member answers are built from the programme's JSON field list at request time | Fields that staff define without a deploy. Browser form is a convenience, server rules are the contract. |
| **API Resource (whitelist)** | `app/Http/Resources/Api/V1`, `app/Http/Resources/Api/V2` | `ProjectResource::toArray()` lists each public field explicitly | Anything that leaves through the API. New model columns do not leak automatically. |
| **Template method / base controller** | `Api\V1\ApiController`, `Api\V2\ApiController` | v1: `ok()`, `paginated()`, `perPage()`, `cached()`. v2: `validateList()`, `paginate()`, `respond()`, with `sorts()` and `includes()` as whitelists that each endpoint overrides. Every endpoint of a version gets the same envelope and cache headers. | New API endpoints extend the base controller of their version. |
| **Model concerns (traits)** | `app/Models/Concerns` | `HasUniqueSlug`, `Searchable`, `FlushesMetrics` | Behaviour shared by several models. |
| **Model event hooks** | `FlushesMetrics` registers `saved`, `deleted`, `restored` closures. `HasUniqueSlug` hooks `creating`. | Cache invalidation without calling flush from controllers | Keep invalidation next to the data, not in each controller. |
| **Query scopes** | `Project::public()`, `Post::published()`, `Program::visible()`, `User::scopeOnLeaderboard()`, `scopeSearch()` | Visibility is decided in the query, so a forgotten check in a view cannot expose a draft | Any "who may see this" filter. |
| **Derived state** | `Post::state()`, `Program::availability()`, `isAcceptingApplications()` | State computed from timestamps at read time. No scheduler | A state changes only because time passes. |
| **Snapshot** | `Demographics::snapshot($user)` stored on `program_applications.applicant_snapshot` | Reports reflect the applicant at submit time | Data that must not change when a profile changes later. |
| **Audit trail as a service** | `AuditLogger::record()` and `diff()` | `UserService::update()` calls `diff()` before `save()` then `record()` | Every state change that someone may need to explain later. |
| **Fail-safe side effects** | `NotificationCenter::safely()`, `StaffNotifier`, try/catch around welcome mail | A notification failure is logged and never fails the request | Side effects that are nice to have, not part of the transaction. |
| **Pessimistic locking** | `VerificationService::consumeLink()` and `check()` use `lockForUpdate()` inside `DB::transaction` | Parallel guesses are counted one by one | Counters or one-time tokens where races matter. |
| **Streaming responses** | `ReportExportService`, `ApplicationService::exportCsv()`, `DocumentStorage::download()` | `lazy(500)` and `streamDownload()` keep memory flat | Large exports and files. |
| **Value helpers in `Support`** | `DateRange`, `Format`, `Demographics`, `Photos` | Pure functions and static helpers with no container dependency | Logic that does not need services. |
| **Anonymous Blade components** | `resources/views/components/ui/*` | `<x-ui.button variant="gold">` | UI pieces used in more than one view. |
| **Database-defined content with code defaults** | `EmailTemplates`, `SettingsService` | Rows in `email_templates` and `settings` override defaults in code | Content that non-developers edit. Code is the fallback when a row is absent. |
| **Idempotent boot steps** | `docker/entrypoint.sh` | `migrate --force`, `PageSeeder`, `hub:secure-demo-accounts`, `hub:import-documents` are safe to repeat | Deployment commands. |
| **Defence in depth for authorisation** | Route `can:` plus policy plus service guard | `UserService` blocks self role change even if a controller forgot | Any privilege-sensitive action. |

## Conventions that support the patterns

- Every PHP file declares `strict_types=1`.
- Services are `final`. Models are not.
- Privileged columns are assigned directly (`$user->role = ...`) and excluded from `$fillable`.
- Money and counts that must be right are computed on the server (`ProgressReportService`), not accepted from the form.
- Controllers return redirects with `->with('status', ...)` after writes.
- Names: services end in `Service`, policies in `Policy`, form requests in `Request`, resources in `Resource`. See [Naming conventions](../05-developer-guide/naming-conventions.md).

## Where the code departs from its patterns

| Departure | Where | Effect |
| --- | --- | --- |
| Service locator calls (`app(ConversationService::class)`) | `Portal\ApplicationController` lines 40 and 51, `LoginRequest` (`app(AuditLogger::class)`), `RegisterRequest::authorize()` (`app(SettingsService::class)`), `Support\EmailTemplates` | Hides dependencies and makes them harder to mock. Constructor or method injection would match the rest. |
| Queries in layouts | `components/layouts/workspace.blade.php` calls `ContactMessage::newCount()` and `ProgressReport::where(...)->count()` directly, plus `MetricsService` | Data access in a view. The values are small counts, but they run on every workspace page. |
| `app(\App\Services\SettingsService::class)` in Blade | `components/emails/layout.blade.php` and `emails/password-changed.blade.php` | Same concern. |
| Inline validation | About 16 `$request->validate()` calls in controllers | Mixed with 16 FormRequest classes. Not wrong for small forms. Larger ones (`AlbumController`, `SettingsController`) would be clearer as form requests. |
| Policy-only gating on some resource routes | `projects`, `posts`, `publications`, `organizations`, `users`, `grants` | Not a pattern violation, but different from the `can:` group style used elsewhere. Safe because every action authorises. See [Authorization architecture](authorization-architecture.md#findings). |
| Status transitions are not enforced | `ApplicationService::review()` | Enums describe the states but any status can be set from any other. |
| Legacy-named column used for a new purpose | `users.notify_new_grants` gates programme application mail | The name no longer matches the use. |

## Anti-patterns to avoid in new code

- Putting writes in controllers or Blade.
- Checking a role name (`$user->role === Role::Officer`) instead of a permission. The code checks permissions, except for `isSuperAdmin()` inside the last-Director guard and `isMember()` for portal routing.
- Adding a state column that a timer would have to flip. Derive it from a timestamp as `posts` do.
- Returning Eloquent models from the API. Use a Resource.
- Adding third-party scripts or inline event handlers. The CSP blocks them.
