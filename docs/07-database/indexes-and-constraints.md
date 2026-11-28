# Indexes and Constraints

> **Audience:** Developers, operators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Tables and columns](tables-and-columns.md), [Entity relationships](entity-relationships.md), [Migrations](migrations.md), [Database troubleshooting](database-troubleshooting.md), [API overview](../06-api-reference/api-overview.md)

This page lists every index, unique constraint, foreign key and CHECK constraint in the schema, with the queries that use them. The list comes from a schema dump of a fresh migration run (see [tables-and-columns](../07-database/tables-and-columns.md)) (SQLite, all migrations applied). Index names are the ones Laravel generates (`<table>_<columns>_<index|unique>`), and PostgreSQL uses the same names. The "Used by" column comes from searching [`app/`](../../app) for the matching `where` and `orderBy` calls. Nothing here was measured with `EXPLAIN`, so "supports" means the leading columns match the query, not that the planner always chooses the index.

## Summary

| Kind | Count | Notes |
| --- | --- | --- |
| Foreign keys | 29 | 10 `CASCADE`, 19 `SET NULL`. No `RESTRICT` or `NO ACTION` rules. |
| CHECK constraints | 3 | `projects.status`, `posts.type`, `grant_applications.status`. |
| Unique constraints (excluding primary keys) | 15 | Slugs, references, e-mail, organisation name, two composite uniques, `failed_jobs.uuid`. |
| Tables with a non-integer primary key | 8 | String keys on `settings`, `email_templates`, `cache`, `cache_locks`, `sessions`, `password_reset_tokens` and `job_batches`, and a UUID key on `notifications`. |
| Soft deletes | 5 tables | Unique indexes on `slug` and `reference_no` still cover soft-deleted rows. |

SQLite enforces foreign keys because `config/database.php` sets `foreign_key_constraints` to `env('DB_FOREIGN_KEYS', true)`. Do not set `DB_FOREIGN_KEYS=false` outside a one-off import: cascades and `SET NULL` rules stop working.

## Foreign keys

Every foreign key is declared with `constrained()`, so it references `id` on the named table. "Cascade" deletes the child rows when the parent row is deleted. "Set null" sets the column to `NULL`.

| Child table.column | Parent | On delete | Why |
| --- | --- | --- | --- |
| `achievements.user_id` | `users` | Cascade | Badges belong to the member. |
| `achievements.program_id` | `programs` | Set null | A badge outlives its programme. |
| `achievements.awarded_by` | `users` | Set null | Keep the badge when the staff member leaves. |
| `activity_logs.user_id` | `users` | Set null | The audit trail must survive account deletion. |
| `album_photos.album_id` | `albums` | Cascade | Photos belong to the album. Files are deleted by `GalleryService` first. |
| `album_photos.uploaded_by` | `users` | Set null | |
| `albums.program_id` | `programs` | Set null | |
| `albums.created_by` | `users` | Set null | |
| `application_messages.program_application_id` | `program_applications` | Cascade | |
| `application_messages.user_id` | `users` | Set null | |
| `broadcasts.program_id` | `programs` | Set null | |
| `broadcasts.created_by` | `users` | Set null | |
| `contact_messages.handled_by` | `users` | Set null | |
| `email_templates.updated_by` | `users` | Set null | |
| `grant_applications.reviewed_by` | `users` | Set null | |
| `pages.updated_by` | `users` | Set null | |
| `posts.author_id` | `users` | Set null | |
| `program_applications.program_id` | `programs` | Cascade | Fires only on a hard delete of a programme. |
| `program_applications.user_id` | `users` | Cascade | |
| `program_applications.reviewed_by` | `users` | Set null | |
| `programs.created_by` | `users` | Set null | |
| `progress_reports.program_application_id` | `program_applications` | Cascade | |
| `progress_reports.user_id` | `users` | Cascade | |
| `progress_reports.reviewed_by` | `users` | Set null | |
| `project_organizations.project_id` | `projects` | Cascade | |
| `project_organizations.organization_id` | `organizations` | Cascade | |
| `projects.created_by` | `users` | Set null | |
| `publications.project_id` | `projects` | Set null | |
| `verification_codes.user_id` | `users` | Cascade | |

Columns that look like foreign keys but have none:

| Column | Why there is no constraint |
| --- | --- |
| `albums.cover_photo_id` | Would be a circular reference with `album_photos.album_id`. `GalleryService` maintains it. |
| `activity_logs.subject_type`, `subject_id` | Polymorphic. |
| `notifications.notifiable_type`, `notifiable_id` | Polymorphic (Laravel default). |
| `sessions.user_id` | Laravel default. Indexed only. |
| `projects.legacy_id`, `publications.legacy_id` | Ids from the old system. |

## CHECK constraints

| Table.column | Constraint | Source |
| --- | --- | --- |
| `projects.status` | `IN ('active','completed','suspended','pending')` | `$table->enum()` in `2026_11_23_000002` |
| `posts.type` | `IN ('news','blog','announcement')` | `2026_11_23_000006` |
| `grant_applications.status` | `IN ('submitted','under_review','shortlisted','approved','rejected')` | `2026_11_23_000007` |

I confirmed these on SQLite by migrating a throwaway database file and reading `sqlite_master`. PostgreSQL gets the same constraint from Laravel's grammar (a `varchar` column with a `CHECK`), which was not dumped for this page.

## Indexes by table

Primary keys are not repeated. "Unique" means a unique index. Composite indexes list their columns in order.

### Accounts

| Index | Columns | Used by |
| --- | --- | --- |
| `users_email_unique` | `email` (unique) | Sign-in lookup, `unique:users,email` validation, `User::firstOrNew(['email' => ...])` in seeders and `hub:create-admin`. |
| `users_role_index` | `role` | `guardLastAdministrator` (`role = super_admin and is_active`), `CreateAdministrator`, `User::scopeWithPermission` (`whereIn role`), `User::scopeMembers` (`role = member`). `scopeStaff` uses `role != member`, which a B-tree index does not serve well. |
| `users_role_public_profile_xp_index` | `role`, `public_profile`, `xp` | The leaderboard and community page: `role = member and public_profile = 1 and xp > 0 order by xp desc, id` (`User::scopeOnLeaderboard`, web `CommunityController`, API v1 and v2 `CommunityController`). |
| `verification_codes_user_id_purpose_consumed_at_index` | `user_id`, `purpose`, `consumed_at` | `VerificationService::issue` (burn old codes), `check` and `consumeLink` (`user_id`, `purpose`, `consumed_at is null`). |
| `sessions_user_id_index` | `user_id` | `UserService::revokeOtherSessions` and the list of devices on the profile page. |
| `sessions_last_activity_index` | `last_activity` | Laravel's session garbage collection, and the profile page ordering (`orderByDesc('last_activity')`). |

### Programmes and applications

| Index | Columns | Used by |
| --- | --- | --- |
| `programs_slug_unique` | `slug` (unique) | Route binding for `/programmes/{slug}` and the API. |
| `programs_type_index` | `type` | Programme list filters (web, API v1 and v2 `type`). |
| `programs_focus_area_index` | `focus_area` | `focus_area` filter. The dashboard ordering by interest uses a `CASE` expression and does not use it. |
| `programs_status_index` | `status` | `Program::scopeVisible` (`status in (published, closed)`), `scopeOpen`. |
| `programs_closes_at_index` | `closes_at` | `closes_at` comparisons in `scopeOpen` and `scopeClosedForApplications`. |
| `programs_status_closes_at_index` | `status`, `closes_at` | `scopeOpen` and the open list ordered by `closes_at`. |
| `program_applications_reference_unique` | `reference` (unique) | Route key for `/applications/{reference}` and the uniqueness loop in `generateReference`. |
| `program_applications_program_id_user_id_unique` | `program_id`, `user_id` (unique) | Enforces one application per member per programme. Also serves lookups by programme and member together. |
| `program_applications_program_id_status_index` | `program_id`, `status` | Capacity count (`withActiveApplicationsCount`, `whereNotIn status`), the review queue per programme, CSV export. |
| `program_applications_status_index` | `status` | Open-applications metrics (`hub.metrics.open_applications`) and the workspace queue filter. |
| `application_messages_program_application_id_id_index` | `program_application_id`, `id` | `ProgramApplication::messages` (`oldest('id')`) and the unread count in `ConversationService::markRead`. |
| `progress_reports_reference_unique` | `reference` (unique) | Route key and `generateReference`. |
| `progress_reports_program_application_id_period_end_index` | `program_application_id`, `period_end` | `ProgramApplication::progressReports` (`latest('period_end')`). |
| `progress_reports_status_index` | `status` | Review queue filter. |
| `achievements_user_id_program_id_type_unique` | `user_id`, `program_id`, `type` (unique) | Prevents awarding the same badge twice. See the `NULL` note below. |
| `achievements_user_id_awarded_at_index` | `user_id`, `awarded_at` | `User::achievements` (`latest('awarded_at')`) and the community profile. |

### Projects, partners, publications and legacy grants

| Index | Columns | Used by |
| --- | --- | --- |
| `projects_slug_unique` | `slug` (unique) | Route key. |
| `projects_legacy_id_index` | `legacy_id` | Data-migration lookups (`DemoContentSeeder` uses `legacy_id` to find projects). |
| `projects_category_index` | `category` | Category filter. |
| `projects_status_index` | `status` | Status filter and dashboard counts. |
| `projects_start_date_index` | `start_date` | `latest('start_date')` on every project list (web, API v1, workspace, export). |
| `projects_status_start_date_index` | `status`, `start_date` | Status filter with date ordering. |
| `projects_category_status_index` | `category`, `status` | Category with status filter. |
| `projects_is_public_index` | `is_public` | `Project::scopePublic` on every public query. Low selectivity when most projects are public. |
| `organizations_name_unique` | `name` (unique) | Duplicate check, `firstOrCreate(['name' => ...])` in the seeder, `orderBy('name')` for lists. |
| `project_organizations_project_id_organization_id_unique` | `project_id`, `organization_id` (unique) | One link per pair. Serves lookups by `project_id` (eager loading `Project::organizations`). |
| `publications_slug_unique` | `slug` (unique) | Route key. |
| `publications_legacy_id_index` | `legacy_id` | Data-migration lookups. |
| `publications_type_index` | `type` | Type filter. |
| `publications_publish_date_index` | `publish_date` | `latest('publish_date')` on lists. |
| `publications_type_publish_date_index` | `type`, `publish_date` | Type filter with date ordering. |
| `grant_applications_reference_no_unique` | `reference_no` (unique) | `generateReference` uniqueness check and staff search by reference. |
| `grant_applications_email_index` | `email` | Search by e-mail. |
| `grant_applications_category_index` | `category` | Category filter. |
| `grant_applications_status_index` | `status` | Status filter. Redundant with the next index for lookups that lead with `status`. |
| `grant_applications_status_created_at_index` | `status`, `created_at` | Queue filtered by status and ordered by `latest()`. |

`->unique()->index()` in `2026_11_23_000007` creates only the unique index. Laravel skips the second index when the first modifier is `unique`, so there is no duplicate on `reference_no`. An earlier version of this documentation claimed otherwise.

### Content and gallery

| Index | Columns | Used by |
| --- | --- | --- |
| `posts_slug_unique` | `slug` (unique) | Route key. |
| `posts_type_index` | `type` | Type filter. |
| `posts_category_index` | `category` | Category filter. |
| `posts_published_at_index` | `published_at` | `Post::scopePublished` (`published_at is not null and <= now`), `latest('published_at')` (web, API v1, v2, sitemap, home page). |
| `posts_type_published_at_index` | `type`, `published_at` | Type filter with published ordering. |
| `pages_slug_unique` | `slug` (unique) | Route key (`Page::getRouteKeyName`) and `firstOrCreate(['slug' => ...])` in `PageSeeder`. |
| `albums_slug_unique` | `slug` (unique) | Route key. |
| `albums_event_date_index` | `event_date` | `Album::scopeLatestEvents` ordering. The `CASE WHEN event_date IS NULL` term is not indexable. |
| `albums_is_published_event_date_index` | `is_published`, `event_date` | `Album::scopePublished` with date ordering. |
| `album_photos_album_id_sort_order_index` | `album_id`, `sort_order` | `Album::photos` (`orderBy sort_order, id`), cover selection. |

### Communication, settings and audit

| Index | Columns | Used by |
| --- | --- | --- |
| `contact_messages_status_created_at_index` | `status`, `created_at` | `ContactMessage::newCount` (`status = new`) and the inbox list. |
| `broadcasts_status_index` | `status` | Status filter. |
| `activity_logs_action_index` | `action` | Audit filter `action like 'area.%'` and search. A left-anchored `LIKE` can use a B-tree only when the collation allows it (the default PostgreSQL collation usually does not, and SQLite `LIKE` is case-insensitive). Treat it as an equality index. |
| `activity_logs_created_at_index` | `created_at` | Date-range reporting. |
| `activity_logs_user_id_created_at_index` | `user_id`, `created_at` | Audit filter by user. The page sorts by `id`, so a sort step remains. |
| `activity_logs_subject_type_subject_id_index` | `subject_type`, `subject_id` | Looking up the history of one record. |
| `email_templates`, `settings` | primary key `key` | Lookup by key. |

### Framework tables

| Index | Columns | Used by |
| --- | --- | --- |
| `cache_expiration_index` | `cache.expiration` | Finding and removing expired keys. |
| `cache_locks_expiration_index` | `cache_locks.expiration` | Lock expiry. |
| `jobs_queue_index` | `jobs.queue` | Worker polling (`queue:work`). |
| `failed_jobs_uuid_unique` | `failed_jobs.uuid` (unique) | `queue:retry` and `queue:forget` by uuid. |
| `notifications_notifiable_type_notifiable_id_index` | `notifiable_type`, `notifiable_id` | `$user->notifications()` (from `morphs()`). |
| `notifications_notifiable_type_notifiable_id_read_at_index` | `notifiable_type`, `notifiable_id`, `read_at` | `$user->unreadNotifications()` and "mark all read". Created by the extra `index()` call in `2026_11_29_000001`. The plain two-column index is redundant with it. |

## Do the indexes support the API v2 orderings?

`ApiController::paginate` in [`app/Http/Controllers/Api/V2/ApiController.php`](../../app/Http/Controllers/Api/V2/ApiController.php) uses `cursorPaginate` with one of two orderings.

| Ordering | Tables | Supported by | Verdict |
| --- | --- | --- | --- |
| `id` ascending or descending (default `-id`) | projects, publications, programmes, partners, albums | The primary key. | Fully supported. The planner can walk the primary key in either direction. |
| `published_at, id` (the `sort=-published_at` and `sort=published_at` choices on the news endpoint; the news default is still `-id`) | posts | `posts_published_at_index` (and `posts_type_published_at_index` when `type` is filtered). | Partly supported. The leading column matches and the `published_at is not null and published_at <= now` filter is a range on it. There is no composite `(published_at, id)` index. SQLite stores the row id in every index entry, so the tie-break comes free there. PostgreSQL has to sort within equal `published_at` values. With the current data size this is not a problem. |
| `xp desc, id` (fixed order, `limit` only, no cursor) | community (`users`) | `users_role_public_profile_xp_index` for the filter and `xp` order. | Mostly supported. The `id` tie-break is not in the index. |

Two other API facts follow from the indexes:

- `updated_since` filters on `updated_at`. **No table has an index on `updated_at`.** A request with `updated_since` scans the table, then applies the ordering. This is acceptable for the table sizes in the seed data. It is a gap if a table grows to hundreds of thousands of rows. Recommended, not implemented: an index on `updated_at` for the synced tables.
- Cursor pagination returns no totals, so no `count(*)` runs for v2 lists.

The v1 lists use page-number pagination (`paginate`), which runs a `count(*)` per request.

## Gaps and notes

| Finding | Detail |
| --- | --- |
| No index on `program_applications.user_id` | The unique `(program_id, user_id)` leads with `program_id`. "My applications" (`where user_id = ?`) and the cascade when a user is deleted have no matching index on PostgreSQL. SQLite has the same gap. |
| No index on `progress_reports.user_id`, `achievements.program_id`, `publications.project_id`, `project_organizations.organization_id`, `application_messages.user_id` | PostgreSQL does not create indexes for foreign keys. The organisation filter (`whereHas('organizations')`) and the project-to-publications eager load rely on scans or on the composite indexes above. |
| Unique `(user_id, program_id, type)` on `achievements` | Two rows with `program_id = NULL` and the same `user_id` and `type` are both allowed, because `NULL` values are distinct in unique indexes on both engines. `EngagementService::award` checks first, but two parallel requests could still insert twice. |
| Redundant indexes | `grant_applications_status_index` is covered by `(status, created_at)`. The two-column `notifications` index is covered by the three-column one. |
| Low-selectivity index | `projects_is_public_index` on a boolean. |
| `users.role` default | Database default `officer`, model default `viewer`. |
| Identifier length | PostgreSQL truncates identifiers over 63 bytes. The longest index name today is 57 characters (`notifications_notifiable_type_notifiable_id_read_at_index`). Give long new indexes an explicit name. |
| CHECK constraints on three enums | Adding a value to `ProjectStatus`, `PostType` or `GrantStatus` also needs a migration that rewrites the constraint, or inserts fail. |
| Case sensitivity | Unique indexes on `email`, `slug` and `name` are case-sensitive on PostgreSQL and (for `varchar`) case-sensitive on SQLite. The application lower-cases e-mail addresses before saving. |
