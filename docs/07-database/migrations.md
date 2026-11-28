# Migrations

> **Audience:** Developers, operators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Database overview](database-overview.md), [Tables and columns](tables-and-columns.md), [Indexes and constraints](indexes-and-constraints.md), [Seed data](seed-data.md), [Database development](../05-developer-guide/database-development.md), [CI/CD pipeline](../09-deployment-and-operations/ci-cd-pipeline.md)

The schema is built by 17 migration files in [`database/migrations/`](../../database/migrations). This page lists them in run order, explains the additive-only rule the project follows, describes the CI check that runs migrations up, down and up, and gives a checklist for writing a new migration that works on both SQLite and PostgreSQL.

## Commands

All of these are Laravel built-ins. Run them from the project root. Do not run them against a database you care about without a [backup](backup-and-restore.md).

| Task | Command |
| --- | --- |
| Show which migrations have run | `php artisan migrate:status` |
| Run pending migrations (local) | `php artisan migrate` |
| Run pending migrations (production, no prompt) | `php artisan migrate --force` |
| Print the SQL without running it | `php artisan migrate --pretend` |
| Undo the last migration file | `php artisan migrate:rollback --step=1` |
| Undo the last batch | `php artisan migrate:rollback` |
| Create a migration | `php artisan make:migration add_example_to_programs_table --table=programs` |
| Create a table migration | `php artisan make:migration create_examples_table --create=examples` |

`composer setup` runs `php artisan migrate --force` as part of first-time setup. The Docker entrypoint ([`docker/entrypoint.sh`](../../docker/entrypoint.sh)) runs `php artisan migrate --force` on every container start unless `RUN_MIGRATIONS=false`. That means a deploy applies new migrations automatically before the web server starts.

> **Warning:** `php artisan migrate:fresh`, `migrate:refresh` and `migrate:reset` drop tables. Never run them against a database with real data. `db:wipe` does the same.

## Migration list

The file names carry the date they were written, `2026_11_23` to `2026_11_30`. The first three are Laravel's stock framework migrations (`0001_01_01_*`).

| # | File | What it does | Reversible (`down`) |
| --- | --- | --- | --- |
| 1 | `0001_01_01_000000_create_users_table` | Creates `users` (with `role`, default `officer`), `password_reset_tokens` and `sessions`. | Drops the three tables. |
| 2 | `0001_01_01_000001_create_cache_table` | Creates `cache` and `cache_locks`. | Drops both. |
| 3 | `0001_01_01_000002_create_jobs_table` | Creates `jobs`, `job_batches` and `failed_jobs`. | Drops all three. |
| 4 | `2026_11_23_000001_create_organizations_table` | Creates `organizations` (unique `name`). | Drops it. |
| 5 | `2026_11_23_000002_create_projects_table` | Creates `projects` with soft deletes, a `status` CHECK constraint, `created_by` foreign key and the `(status, start_date)` index. | Drops it. |
| 6 | `2026_11_23_000003_create_project_organizations_table` | Creates the project to organisation pivot with cascade foreign keys and a unique pair. | Drops it. |
| 7 | `2026_11_23_000004_create_publications_table` | Creates `publications` with soft deletes and an optional `project_id`. | Drops it. |
| 8 | `2026_11_23_000005_create_activity_logs_table` | Creates `activity_logs` (audit log) with `user_id` set to null on delete. | Drops it. |
| 9 | `2026_11_23_000006_create_posts_table` | Creates `posts` with soft deletes and a `type` CHECK constraint. | Drops it. |
| 10 | `2026_11_23_000007_create_grant_applications_table` | Creates `grant_applications` (legacy intake) with soft deletes and a `status` CHECK constraint. | Drops it. |
| 11 | `2026_11_24_000001_add_scalability_indexes` | Adds composite indexes: `activity_logs(created_at)`, `activity_logs(user_id, created_at)`, `posts(type, published_at)`, `grant_applications(status, created_at)`, `publications(type, publish_date)`, `projects(category, status)`. | Drops those indexes. |
| 12 | `2026_11_25_000001_extend_platform_schema` | Adds `users.is_active`, `last_login_at`, `last_login_ip`; `projects.is_public`; `publications.abstract`; `grant_applications.reviewed_at`; `activity_logs.subject_type` and `subject_id`. | Drops the columns and the index on `is_public`. |
| 13 | `2026_11_26_000001_create_settings_and_contact_messages` | Creates `settings` and `contact_messages`. Adds `users.notify_new_grants` and `notify_contact_messages`. | Drops the columns and both tables. |
| 14 | `2026_11_27_000001_create_programmes_members_and_cms` | Adds member columns to `users` (`phone`, `affiliation`, `institution`, `bio`, `interests`, `marketing_opt_in`, `onboarded_at`). Marks every existing account verified. Creates `verification_codes`, `programs`, `program_applications`, `email_templates`, `broadcasts`, `pages`. | Drops the tables and columns. Does not undo the verification back-fill. |
| 15 | `2026_11_28_000001_add_member_demographics` | Adds `users.gender`, `date_of_birth`, `region`, `education_level`, `disability` and `program_applications.applicant_snapshot`. | Drops the columns. |
| 16 | `2026_11_29_000001_create_engagement_tables` | Creates `notifications` (only if missing), `achievements`, `application_messages`, `progress_reports`. Adds `users.xp`, `users.public_profile` and the `(role, public_profile, xp)` index. | Drops the three tables, the user columns and the index. Does not drop `notifications`. |
| 17 | `2026_11_30_000001_create_gallery_tables` | Creates `albums` and `album_photos`. | Drops both. |

Seventeen files create 30 tables (the `migrations` table is created by Laravel itself).

### What the migrations do not do

- No migration imports data from the legacy MySQL system. `projects.legacy_id` and `publications.legacy_id` exist so that an import can be matched. See [seed-data.md](seed-data.md) and the [database overview](database-overview.md).
- Only one migration changes data: number 14 runs `DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()])` so that staff accounts created before e-mail verification existed can still sign in. It is a one-way change.
- No migration seeds reference data. Default pages come from `PageSeeder`.

## The additive-only rule

The project rules in [`.ai/rules/database.md`](../../.ai/rules/database.md) and [`.ai/workflows/database-change.md`](../../.ai/workflows/database-change.md) say migrations must be non-destructive and backward compatible. In practice this means:

1. **Add, do not change.** Add new tables, or new columns that are nullable or have a default. The migrations from number 12 onward follow this and say so in their docblocks ("Additive only").
2. **Do not drop or rename columns or tables in a release that still reads them.** If a column is no longer needed, stop using it in one release and drop it in a later one, after a backup.
3. **Give every new column a safe default** so existing rows and old code keep working during a rolling deploy. Hosted platforms can run the old and new versions side by side for a short time during a deploy, so both must work against the new schema.
   > **Requires confirmation:** whether Render's free plan overlaps the two containers.
4. **Write a `down()`** that reverses the change. Keep it honest: if the data change cannot be reversed, say so in a comment.
5. **Make an explicit decision about foreign key behaviour** (`cascadeOnDelete()`, `nullOnDelete()`). Every foreign key in this schema has one. Index the new foreign key column as well; some existing ones are missing an index (see [indexes-and-constraints.md](indexes-and-constraints.md)).

Known exceptions to the rule that are already in production: the `status` and `type` CHECK constraints on three tables (adding an enum value needs a constraint rewrite), and the data update in number 14.

## CI up, down, up check

[`.github/workflows/ci.yml`](../../.github/workflows/ci.yml), job `tests`, runs these steps on the PostgreSQL leg of the matrix only (`if: matrix.database == 'pgsql'`):

```bash
php artisan migrate --force
php artisan migrate:rollback --step=1 --force
php artisan migrate --force
php artisan db:seed --force
```

What this proves, and what it does not:

| Point | Detail |
| --- | --- |
| It checks the schema builds on a fresh PostgreSQL 16 database. | The first `migrate` runs all 17 files. |
| It checks only the newest migration reverses. | `--step=1` rolls back one file, which is the gallery migration today. The `down()` methods of the older 16 files are not exercised in CI. |
| It checks the schema rebuilds after a rollback. | The second `migrate` re-runs that file. |
| It checks the seeders run on the migrated schema. | `db:seed` runs with `APP_ENV` from `.env.example`, so it takes the non-production path in [seed-data.md](seed-data.md). |
| The SQLite leg does not run this step. | Tests on SQLite build the schema through Laravel's `RefreshDatabase` trait in memory. That is an up-only check. |

For this page I ran a full `migrate` and `migrate:rollback` against a throwaway SQLite file outside the repository. All 17 migrations ran and all 17 reversed cleanly. That was a local check on one engine, not part of CI.

> **Requires confirmation:** a full rollback on PostgreSQL. It was not run for this page.

To repeat the full SQLite check without touching your development database:

PowerShell:

```powershell
$tmp = Join-Path $env:TEMP 'migration-check.sqlite'
New-Item $tmp -ItemType File -Force | Out-Null
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = $tmp
php artisan migrate --force
php artisan migrate:rollback --force
Remove-Item $tmp
Remove-Item Env:DB_DATABASE
```

bash:

```bash
tmp="$(mktemp)"
DB_CONNECTION=sqlite DB_DATABASE="$tmp" php artisan migrate --force
DB_CONNECTION=sqlite DB_DATABASE="$tmp" php artisan migrate:rollback --force
rm "$tmp"
```

The environment variable wins over `.env`, so `database/database.sqlite` is not touched. If `DB_URL` is set in your shell or `.env`, clear it for the check, because the `sqlite` connection also reads `DB_URL`.

## Writing a new migration safely

### Steps

1. Create the file: `php artisan make:migration add_example_to_programs_table --table=programs`.
2. Write `up()` and `down()`. Use the Laravel schema builder only. Avoid raw SQL; if you must use it, guard it by driver (see below).
3. Update the model: `$fillable` (only for form input), `casts()`, relations. Workflow columns are written with `forceFill` in a service and stay out of `$fillable`.
4. Update the factory in [`database/factories/`](../../database/factories) and any seeder that creates the record.
5. Update [tables-and-columns.md](tables-and-columns.md) and, for new indexes, [indexes-and-constraints.md](indexes-and-constraints.md).
6. Run the migration on a throwaway SQLite file (see above), then run the tests: `php artisan test` or `composer test`. See [testing-guide.md](../05-developer-guide/testing-guide.md).
7. Push. CI runs the up, down, up step on PostgreSQL.

### SQLite and PostgreSQL differences to respect

| Topic | What to do |
| --- | --- |
| Column order | `->after('column')` is ignored by PostgreSQL and SQLite, which cannot reorder columns. It is harmless, and existing migrations use it. Do not rely on column order. |
| Dropping columns | Laravel 12 supports `dropColumn` on SQLite. Dropping a column that is part of an index needs the index dropped first, as the existing `down()` methods do (`dropIndex([...])` before `dropColumn`). |
| Changing a column | `->change()` needs the full column definition and rewrites the table on SQLite. Prefer adding a new column. |
| Enum columns | Avoid `$table->enum()`. It creates a CHECK constraint that is hard to extend. Use `string('status', 20)` and a PHP enum, as the newer tables do. |
| Adding NOT NULL columns | Always give a default or make the column nullable. PostgreSQL and SQLite both refuse to add a `NOT NULL` column without one on a table that has rows. |
| Foreign keys | Define them in the `create` call with `constrained()` and an explicit `cascadeOnDelete()` or `nullOnDelete()`. SQLite cannot add a foreign key to an existing table with `ALTER`. Laravel rebuilds the table for you, but keep the change small. |
| Unique indexes and `NULL` | Both engines allow many `NULL` values in a unique index. Do not rely on a unique index to block duplicate rows when a column is nullable. |
| JSON | `->json()` is `json` on PostgreSQL and text on SQLite. Do not use JSON operators in queries. Cast with `'array'` in the model. |
| Booleans | `boolean` is `boolean` on PostgreSQL and `tinyint(1)` on SQLite. In raw SQL use `true` and `false` carefully; in Eloquent pass PHP booleans. |
| Case sensitivity | `LIKE` is case-insensitive on SQLite and case-sensitive on PostgreSQL. Use `Searchable::scopeSearch`, which switches to `ilike` on PostgreSQL. |
| Index names | PostgreSQL limits identifiers to 63 bytes. Pass an explicit short name for long composite indexes: `$table->index(['a', 'b', 'c'], 'short_name_idx')`. |
| Raw expressions | Branch on `DB::connection()->getDriverName()` as `Project::yearExpression()` does. Never put user input in them. |
| Transactions | PostgreSQL runs schema changes inside a transaction and rolls back a failed migration. SQLite also supports transactional DDL. Mixed schema and data changes should be kept in separate migrations. |
| Data changes | Keep data back-fills in their own migration so a rollback of the schema does not depend on them. Use `DB::table()`, never Eloquent models, which may change later. |
| Idempotency | Existing migration 16 wraps `notifications` in `Schema::hasTable`. Use the same guard when a table might already exist from a manual step. |

### Example

An additive column on `programs`:

```php
public function up(): void
{
    Schema::table('programs', function (Blueprint $table) {
        $table->string('contact_email')->nullable()->after('location');
    });
}

public function down(): void
{
    Schema::table('programs', fn (Blueprint $table) => $table->dropColumn('contact_email'));
}
```

## Deploying a migration

1. Take a [backup](backup-and-restore.md) if the change touches existing data.
2. Merge to `main`. CI runs; Render auto-deploys `main`.
3. The container entrypoint runs `php artisan migrate --force` before starting Apache. A failing migration stops the container start, because the entrypoint uses `set -e`. The health check on `/up` (`render.yaml`) then fails and Render does not switch traffic to the new container.
   > **Requires confirmation:** Render's exact behaviour on a failed start.
4. Check `php artisan migrate:status` in the Render shell if you have access.
   > **Requires confirmation:** shell access on the Render free plan.

If a migration fails in production, see [database-troubleshooting.md](database-troubleshooting.md) and [rollback procedures](../09-deployment-and-operations/rollback-procedures.md).

## Findings

| Finding | Where |
| --- | --- |
| CI exercises only the newest migration's `down()`. | `ci.yml`, step "Migrations run cleanly (up, down, up)" |
| The up, down, up check runs on PostgreSQL only. | `ci.yml` |
| Migration 14 changes data and is not reversible. | `2026_11_27_000001_create_programmes_members_and_cms.php` |
| Migration 16's `down()` does not drop `notifications`, although `up()` may have created it. | `2026_11_29_000001_create_engagement_tables.php` |
| The project rule "always index foreign keys" is not followed everywhere. | [indexes-and-constraints.md](indexes-and-constraints.md) |
| `composer.json` still has the template name `laravel/laravel`. Not a database issue, but it appears in `composer` output. | `composer.json` |
