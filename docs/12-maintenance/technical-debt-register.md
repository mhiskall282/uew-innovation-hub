# Technical Debt Register

> **Audience:** Developers, maintainers, Hub Director · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [maintenance-overview.md](maintenance-overview.md), [refactoring-guidelines.md](refactoring-guidelines.md), [security-maintenance.md](security-maintenance.md), [project-handover.md](project-handover.md)

This is the canonical list of known issues, gaps and debt in the UEW Innovation Hub. Other pages link here instead of repeating items. Each open item was checked against the code on the date above. Items fixed in commit `d1296b1` are kept at the bottom so the history is visible.

Priority: **High** (fix before launch or data/security risk), **Medium** (fix within a quarter), **Low** (tidy-up).

## Open items

| ID | Area | Description | Evidence | Impact | Suggested fix | Priority | Status |
| --- | --- | --- | --- | --- | --- | --- | --- |
| TD-001 | Config | `.user.ini` is committed but the image runs Apache with `mod_php` (`php:8.4-apache`), which ignores `.user.ini`. The effective PHP limits come from `docker/php.ini`. The two files disagree on `memory_limit`-style values and mislead readers. | [`.user.ini`](../../.user.ini), [`Dockerfile`](../../Dockerfile), `docker/php.ini` | Someone edits `.user.ini` and nothing changes. | Delete `.user.ini` or document it as unused. Keep `docker/php.ini` as the only source. | Low | Open |
| TD-002 | Views | Empty view folders: `resources/views/admin`, `dashboard`, `grants`, `organizations`, `posts`, `projects`, `publications` (0 files each). `reports` holds 1 file and `resources/views/components/npontu` is empty. | `Get-ChildItem resources/views` | Clutter, suggests unfinished or removed features. | Confirm no controller renders them, then remove the empty folders. Check the `reports` file separately. | Low | Open |
| TD-003 | Packaging | `composer.json` still has `"name": "laravel/laravel"` and the skeleton description. | [`composer.json`](../../composer.json) line 3 | Wrong identity in tooling and `composer show`. | Rename to a project vendor/name and update the description. | Low | Open |
| TD-004 | Config | `bootstrap/app.php` calls `env('TRUSTED_PROXIES')` directly. After `php artisan config:cache`, `env()` outside config files returns null, so the setting is ignored. | [`bootstrap/app.php`](../../bootstrap/app.php) lines 43-45 | Behind a load balancer, client IPs and HTTPS detection can be wrong when config is cached. | Move the value into a config file and read it with `config()`. | Medium | Open |
| TD-005 | Operations | No scheduled tasks. `routes/console.php` only defines `inspire`. Nothing prunes old audit logs, sessions, failed jobs, notifications or expired password-reset tokens. | [`routes/console.php`](../../routes/console.php) | Tables grow without limit. | Add a scheduler entry and a cron or Render cron job that runs `php artisan schedule:run`. See [data-retention](../07-database/data-retention.md). | Medium | Open |
| TD-006 | Operations | The queue worker runs inside the web container (`queue:work` started by `docker/entrypoint.sh`, restarted in a loop). | `docker/entrypoint.sh` | Worker and web share memory and CPU. A worker crash loop is hard to see. | Run a separate worker service and set `RUN_QUEUE_WORKER=false` on the web service. | Medium | Open |
| TD-007 | Data | Models using `SoftDeletes` (`GrantApplication`, `Post`, `Program`, `Project`, `Publication`) have no restore UI in the staff workspace. | `app/Models/`, `routes/web.php` (no restore route) | A mistaken delete needs a database edit to undo. | Add a "Deleted items" view with restore for staff with delete rights, and audit it. | Medium | Open |
| TD-008 | Security | No forced password change. Demo and reset accounts can keep a known password. `hub:secure-demo-accounts` exists but is manual. | `app/Console/Commands/SecureDemoAccounts.php` | A shared or default password stays valid. | Add a `must_change_password` flag, or set `BLOCK_DEFAULT_PASSWORDS=true` and run `hub:secure-demo-accounts`. | High | Open |
| TD-009 | Members | Members cannot change their e-mail address, delete their account or export their data. | `routes/web.php` portal routes | Gap against common data-protection expectations. | Add profile e-mail change with re-verification, account deletion and a data export. Needs legal input. | Medium | Open |
| TD-010 | Applications | Application status transitions are unrestricted. Staff can move any status to any other. | `app/Enums/ApplicationStatus.php`, `app/Services/ApplicationService.php` | Accidental or inconsistent state changes (for example Accepted back to Submitted). | Define an allowed-transition map in the enum and enforce it in the service. | Medium | Open |
| TD-011 | Applications | A withdrawn application cannot be re-submitted by the member. | `app/Services/ApplicationService.php` | Members must contact staff to re-apply. | Allow re-apply while the programme is open, or document it as intended. | Low | Open |
| TD-012 | Tooling | `brianium/paratest` is not installed. | `composer.json` | Test suite (194 tests) runs serially. | Add as a dev dependency and run `php artisan test --parallel`. | Low | Open |
| TD-013 | Tooling | No Dependabot, no static analysis (PHPStan or Larastan) and no coverage report in CI. No `.github/dependabot.yml`. | `.github/workflows/ci.yml` | Updates and type errors are found late. | Add `dependabot.yml`, Larastan and a coverage job. See [dependency-updates.md](dependency-updates.md). | Medium | Open |
| TD-014 | Process | Commits go straight to `main`, which auto-deploys to Render. No required review or branch protection is documented. | `render.yaml`, `git log` | A bad commit can reach production after CI starts. | Enable branch protection with required CI and pull requests. See [release-management.md](release-management.md). | High | Open |
| TD-015 | Security | A tester password was committed in git history (commit `87df882`). | `git show 87df882` (do not copy the value) | The credential is public in the repository history. | Make sure the account password was changed (`hub:secure-demo-accounts`). Optionally rewrite history, which needs the owner's approval. | High | Open |
| TD-016 | Hosting | Render free plan: the disk is ephemeral and the free PostgreSQL database expires. Uploads are lost on redeploy unless `DOCUMENTS_DISK=s3` and `GALLERY_DISK=s3`. | `render.yaml` lines 7, 17, 102 | Data loss and downtime. | Move to a paid database, configure S3-compatible storage, and set backups. | High | Open |
| TD-017 | API | v2 has no tombstones. `updated_since` sync cannot detect deleted or unpublished records. | `app/Http/Controllers/Api/V2/` | API clients keep stale records. | Add a deletions feed or a `deleted_at` marker, and document it. | Medium | Open |
| TD-018 | Notifications | Staff messages to applicants are in-app only. No e-mail is sent. | `app/Services/` message service, [application-messages](../02-user-guide/feature-guides/application-messages.md) | Applicants may not see replies. | Queue a notification e-mail with an opt-out. | Medium | Open |
| TD-019 | Repository | `.user.ini` is the only file in the "ignored by runtime" class that is tracked. `.phpunit.result.cache` and `test-results/` are listed in `.gitignore` and are not tracked (checked with `git ls-files`). | `.gitignore` | None. Recorded so nobody re-checks. | None. | Low | Verified, no action |
| TD-020 | Code | No `TODO` or `FIXME` comments exist in `app/`, `resources/`, `routes/`, `config/` or `tests/`. The only grep matches were the word "Climate" in a test fixture title. | `Select-String` over those folders | Debt is tracked here, not in comments. | Keep it that way: add an ID here instead of a TODO. | Low | Verified, no action |

## Resolved in commit d1296b1

| ID | Area | What was fixed | Evidence |
| --- | --- | --- | --- |
| TD-R01 | Broadcasts | New `BroadcastStatus::Failed`. `SendBroadcast::failed()` marks a stuck `sending` broadcast as failed and writes audit action `broadcast.failed`. `tries` is still 1. | `app/Enums/BroadcastStatus.php`, `app/Jobs/SendBroadcast.php` |
| TD-R02 | Audit | CSV exports are audited: `member.exported`, `report.projects_exported`, `application.exported`. | `MemberController::export`, `ReportExportService::projectsCsv`, `ApplicationService::exportCsv` |
| TD-R03 | Audit | `AuditLogController::AREAS` lists all 18 prefixes. | `app/Http/Controllers/Workspace/AuditLogController.php` |
| TD-R04 | Uploads | `docker/php.ini` sets `post_max_size` 110M and `max_file_uploads` 12, so 10 photos of 10 MB fit. | `docker/php.ini` |
| TD-R05 | Seeding | Duplicate `SEED_PASSWORD` check removed from `DatabaseSeeder`. | `database/seeders/DatabaseSeeder.php` |
| TD-R06 | UX | Register and reset-password hints state 12+ characters in production, 8+ elsewhere. | `resources/views/auth/` |
| TD-R07 | Privacy | Onboarding no longer pre-ticks the announcement e-mail checkbox (`marketing_opt_in` is opt-in). | `resources/views/portal/` |
| TD-R08 | Performance | `Program::scopeWithActiveApplicationsCount()` and `Program::inactiveStatuses()` added. `isFull()` uses the preloaded count. API v1 and v2 lists use it, so there is no per-row `COUNT`. | `app/Models/Program.php` |
| TD-R09 | Config | `config/hub.php` `search_indexing` comment moved to the right key. The currency symbol is valid UTF-8; the garbled text was a console display issue. | `config/hub.php` |
| TD-R10 | Tooling | `package.json` has `engines.node` `^20.19.0 \|\| >=22.12.0`. | `package.json` |
| TD-R11 | Config | `.env.example` now has `GALLERY_DISK` and the API variables. | `.env.example` |
| TD-R12 | Tests | `tests/Feature/Workspace/OperationalFixesTest.php` added (6 tests). Suite: 194 tests, 1461 assertions, Pint clean. | `tests/Feature/Workspace/OperationalFixesTest.php` |

## How to use this register

1. Add a row with the next free ID when you find a problem. Do not reuse IDs.
2. Change **Status** to `Resolved in <commit>` and move the row to the resolved table when fixed.
3. Review the register at the start of each quarter. See [maintenance-strategy.md](maintenance-strategy.md).

> **Requires confirmation:** whether `resources/views/reports/` (1 file) is still rendered by a controller, and the exact contents of `docker/php.ini` limits versus `.user.ini` (`.user.ini` sets `upload_max_filesize = 100M`, `post_max_size = 110M`; `docker/php.ini` keeps `upload_max_filesize` at 25M).
