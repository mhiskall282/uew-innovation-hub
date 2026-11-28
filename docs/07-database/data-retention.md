# Data Retention

> **Audience:** Operators, developers, data protection contacts · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Tables and columns](tables-and-columns.md), [Entity relationships](entity-relationships.md), [Backup and restore](backup-and-restore.md), [Audit and monitoring](../03-administration/audit-and-monitoring.md), [Data protection](../08-security/data-protection.md), [Technical debt register](../12-maintenance/technical-debt-register.md)

This page records what the code keeps, what it deletes, and when. It describes behaviour that exists today. The Hub has **no written retention periods and no scheduled pruning**: `routes/console.php` contains only Laravel's default `inspire` command, and no model uses Laravel's `Prunable` trait. Where a policy is missing, this page says so and marks the gap. It does not invent periods.

## Summary

| Data | What happens today | Deleted automatically |
| --- | --- | --- |
| Projects, publications, posts, programmes | Soft deleted (`deleted_at`) and kept indefinitely | No |
| Legacy grant applications | Kept. The model supports soft delete, but no code deletes them. | No |
| Members and staff accounts | Staff can be hard deleted by the Hub Director. Members cannot be deleted from the interface. | No |
| Programme applications, messages, progress reports, achievements | Kept while the account exists. Removed by cascade when the account is hard deleted. | No |
| Verification codes | Expire after 10 minutes, then stay in the table | No |
| Sessions | Expire after `SESSION_LIFETIME` minutes (default 120), swept by Laravel | Yes, by Laravel's session garbage collection |
| Notifications | Kept, marked read | No |
| Audit log (`activity_logs`) | Append-only, kept indefinitely | No |
| Failed jobs | Kept until someone removes them | No |
| Gallery photo files | Deleted from the disk when the photo or album is deleted | On delete only |
| Uploaded documents | Deleted on replace or "remove". Kept when the record is soft deleted. | On replace only |
| Cache and rate-limit counters | Expire by time | Yes (expired entries are removed when read) |

## Soft deletes

Five models use `SoftDeletes`: `Project`, `Publication`, `Post`, `Program` and `GrantApplication`. "Delete" in the staff workspace for the first four sets `deleted_at`. The row, its relationships and its files stay in the database.

| Model | Delete action | Side effects | Restore in the interface |
| --- | --- | --- | --- |
| `Project` | `ProjectService::delete` writes `project.deleted` ("Archived project") | The report file is kept "so the record can be restored" (comment in the service). Partner links stay. | None |
| `Publication` | `PublicationService::delete` writes `publication.deleted` | The PDF stays on the documents disk. | None |
| `Post` | `PostService::delete` | Slug stays reserved. | None |
| `Program` | `ProgramService::delete` writes `program.deleted` | Applications, badges, albums and broadcasts keep pointing at it. Relations use `withTrashed()` so titles still show. | None |
| `GrantApplication` | No code path soft deletes it | Kept for review history. | None |

What this means in practice:

- **There is no restore function.** No controller, command or service calls `restore()`. A soft-deleted row can only be brought back with a database update (`UPDATE ... SET deleted_at = NULL`) or in `php artisan tinker`. > **Requires confirmation:** whether the Hub wants a restore screen. The `FlushesMetrics` trait already handles the `restored` event, so the models are ready for one.
- **Soft-deleted rows are never purged.** Nothing calls `forceDelete()`. The data stays until someone removes it by hand.
- **Slugs of soft-deleted rows stay reserved.** `HasUniqueSlug` includes trashed rows in its check, so a new item with the same title gets `-2`.
- **Foreign key cascades do not fire.** `ON DELETE CASCADE` on `program_applications.program_id` runs only when the programme row is physically deleted.
- **Hidden from the public.** Soft-deleted projects and posts disappear from the website, sitemap and API because Eloquent excludes them. Direct links to their documents return 404 for the same reason.

## Hard deletes

These actions remove rows permanently.

| Record | Who and how | Conditions and side effects |
| --- | --- | --- |
| Staff user | `UserService::delete` (Hub Director, `UserPolicy::delete`) | Refuses to delete yourself or the last active Hub Director. Revokes the account's sessions first. Cascades to the user's verification codes, applications, progress reports and badges. Other links become `NULL`. See [entity-relationships.md](entity-relationships.md). Writes `user.deleted`. |
| Organisation | `OrganizationController::destroy` | Refused while the organisation is linked to any project. Writes `organization.deleted`. |
| Page | `PageController::destroy` | Removes the row. `PageSeeder` recreates the four default pages on the next container boot. See [seed-data.md](seed-data.md). |
| Contact message | `ContactMessageService::delete` | Removes the enquiry. Writes `message.deleted`. |
| Broadcast | `BroadcastController::destroy` | Only drafts. Sent and failed broadcasts are kept ("Sent broadcasts are kept for the record"). |
| E-mail template override | `EmailTemplateController` | Deletes the override row, which restores the default text. |
| Achievement | `EngagementService::revoke` | Subtracts its XP from the user and writes `achievement.revoked`. |
| Gallery photo | `GalleryService::deletePhoto` | Deletes both image files from the disk first, then the row. Writes `album.photo_deleted`. |
| Gallery album | `GalleryService::delete` | Deletes every photo file, then the folder `albums/<id>`, then the rows (photos cascade). Writes `album.deleted`. |

Members have no delete action. `UserPolicy::deactivate` and `delete` require that the target `isStaff()`. A Hub Director can deactivate staff accounts, but a member account can only be left as is, or unsubscribed from announcements (`MemberService::unsubscribe` sets `marketing_opt_in` to `0`).

## Short-lived records that are never cleaned up

### Verification codes

`verification_codes` rows are created by `VerificationService::issue`.

| Event | Effect on the row |
| --- | --- |
| Issued | `expires_at` is 10 minutes ahead (`EXPIRY_MINUTES`). The code is stored as a bcrypt hash. |
| A new code is requested | Earlier unused codes for the same user and purpose get `consumed_at = now()`. A request is throttled to one per 60 seconds. |
| Correct code or valid link | `consumed_at` is set. A link is checked inside a row lock and works once. |
| Five wrong guesses (`MAX_ATTEMPTS`) | `consumed_at` is set, the code is burned. |
| Expired | `expires_at` is in the past. The row is ignored but not removed. |

No job deletes expired or consumed codes. The table grows with every sign-up, e-mail verification and password reset. The rows hold only a hash, a purpose and timestamps, and they are removed when the user is deleted. **Gap:** no pruning of rows older than a few days. Recommended, not implemented: a scheduled `DELETE` of rows where `expires_at` is older than 7 days.

### Sessions

`SESSION_DRIVER=database` (the default in `config/session.php`, `.env.example` and `render.yaml`). Laravel removes expired rows with its session sweeping lottery, configured as `[2, 100]` (about 2 requests in 100 trigger a sweep). Sessions last `SESSION_LIFETIME` minutes, 120 by default. Sessions are also deleted on demand:

- `UserService::revokeOtherSessions` on password change, password reset, deactivation, "sign out of other devices" and account deletion.
- The profile page lists up to 10 sessions for the signed-in user, with IP address and user agent.

### Notifications

`notifications` rows are created for members (badges, application and report updates). They are marked read with `read_at` and never deleted. There is no foreign key, so deleting a user leaves their notifications behind. **Gap:** no pruning of read notifications and no cleanup for deleted users.

### Failed jobs

`failed_jobs` keeps every failed queue job with its serialised payload and exception text. `SendBroadcast` has `$tries = 1`, so a failed broadcast fails once, sets the broadcast to `failed` and writes `broadcast.failed` to the audit log. Queued e-mail jobs are retried up to 3 times by the worker started in `docker/entrypoint.sh` (`--tries=3`). Failed rows stay until someone removes them with Laravel's `php artisan queue:flush` or `php artisan queue:forget <uuid>`, or retries them with `php artisan queue:retry`. `php artisan queue:prune-failed --hours=<n>` also exists in Laravel but is not scheduled. The payload can contain recipient e-mail addresses and the e-mail text. **Gap:** no retention period for `failed_jobs`.

### Cache and rate limiting

`cache` rows carry an `expiration` and are removed when they are read after expiry. Entries that nobody reads again remain until the next `cache:clear`. The table holds metrics, settings, the footer links and rate-limiter counters, so it stays small.

## The audit log

`activity_logs` is the audit trail ([audit-and-monitoring.md](../03-administration/audit-and-monitoring.md)).

- It is **append-only by convention**. `AuditLogger::record` is the only writer. No code updates or deletes rows, and the database does not stop someone with direct access from doing so.
- It is **never pruned**. There is no retention period.
- It outlives its actors and subjects. `user_id` is set to `NULL` when an account is deleted, and `subject_type` and `subject_id` have no foreign key. The `description` text keeps the person's name or e-mail address, for example "Deleted staff account for Jane Doe" or "Incorrect reset_password code for jane@example.com". So deleting a user does not remove their name from the log.
- It stores IP addresses (`ip_address`) and, in `properties`, field-level changes. Passwords and remember tokens are recorded as `changed`, never as values.
- It is backed up with the database and is deleted only when the database is.

**Gap:** the privacy notice says records are kept "for the period required by university policy" but no period is defined in code or documentation, and there is no procedure for removing a person's name from audit entries.

## Files outside the database

| Files | Created by | Deleted by |
| --- | --- | --- |
| Project reports (`reports/`) and publication PDFs (`publications/`) | `ProjectService`, `PublicationService` through `DocumentStorage` | Replacing the file, ticking "remove", or `DocumentStorage::delete`. **Not** deleted on soft delete. |
| Files attached to applications (`applications/`) | `ApplicationService::save` | Replaced by a new upload to the same field. **Not** deleted when the application is withdrawn, or when the row is removed by a cascade. |
| Legacy proposals (`proposals/`) | The old system | The `proposals` folder resolves to `reports/` in `DocumentStorage::LEGACY_FOLDERS`. No current code deletes them. |
| Gallery photos (`albums/<id>/`) | `GalleryService::addFromPath` | `GalleryService::deletePhoto` and `GalleryService::delete`, which delete the files before the rows. |

A cascade delete (for example deleting a staff account that also owns applications) removes the rows but leaves application files orphaned on the disk. **Gap:** no job finds files that no row refers to.

## Personal data and requests from individuals

The privacy page created by `PageSeeder` says people can "ask us to correct or delete your data by e-mailing" the Hub contact address, and that records are kept "while your account is active and for the period required by university policy".

What the code provides today:

| Need | Support in the application |
| --- | --- |
| Member edits their profile | Yes (portal profile page). |
| Member unsubscribes from announcements | Yes (signed link and preference). |
| Member exports their own data | No. |
| Member deletes their own account | No. |
| Staff delete a member account | No. The policy allows deleting staff only. |
| Staff anonymise a member | No. |

Deleting a member at their request therefore needs a manual database change, and that change cascades to their applications, messages, reports and badges but not to the files, notifications or audit entries described above. > **Requires confirmation:** the Hub's procedure for erasure requests under the Data Protection Act, 2012 (Act 843), and who approves them.

## Gaps and recommendations

| Gap | Impact | Recommended, not implemented |
| --- | --- | --- |
| No scheduler, so nothing runs periodically | Every cleanup below needs one | Add scheduled commands in `routes/console.php` and run `php artisan schedule:work` or a cron entry for `php artisan schedule:run`. The container has no cron. |
| `verification_codes` never pruned | Slow growth | Delete rows expired for more than 7 days. |
| `failed_jobs` never pruned | Holds e-mail addresses and text indefinitely | Schedule `queue:prune-failed`. |
| `notifications` never pruned, none cascaded on user delete | Slow growth, orphan rows | Delete read notifications after a set period and when a user is deleted. |
| `activity_logs` kept forever | Names and IPs kept without a limit | Decide a period with the university and archive or purge older rows. |
| Soft-deleted rows kept forever, no restore screen | Data cannot be recovered without database access, and cannot be purged | Add a trash view with restore and purge for the five models. |
| Orphaned application files after cascades | Files outlive their records | A job to compare files with `program_applications.answers` and delete the rest. |
| No member erasure or export | Manual work for rights requests | Add self-service export and a staff-approved erasure workflow. |
| Free Render PostgreSQL expires after 30 days | Total data loss without a dump | See [backup-and-restore.md](backup-and-restore.md). |
