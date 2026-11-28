# Database Troubleshooting

> **Audience:** Developers, operators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Database overview](database-overview.md), [Migrations](migrations.md), [Backup and restore](backup-and-restore.md), [Indexes and constraints](indexes-and-constraints.md), [Database problems](../11-troubleshooting/database-problems.md), [Testing guide](../05-developer-guide/testing-guide.md)

This page covers database problems that can happen in this project, each with symptoms, causes, diagnostics, commands, a fix and how to prevent it. The causes come from reading the code and configuration. Two were also checked by running Laravel against throwaway SQLite files outside the repository, and are marked **reproduced**. The rest are derived from the code and are marked **from the code**. Nothing was tested on the live site or on PostgreSQL.

## First checks for any database error

Run these first. All are Laravel built-ins.

```bash
php artisan about                 # environment, database driver, cache and session drivers
php artisan db:show               # connection, driver, database name, size
php artisan db:show --counts      # row counts per table
php artisan migrate:status        # which migrations have run
php artisan config:clear          # drop a stale cached configuration
```

`php artisan db:show` prints connection details. Do not paste its output, or `.env`, into tickets without removing host names and credentials.

The default session driver, cache store and queue are all database tables (`SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`). If the database is unreachable or the tables are missing, almost every page fails, including the home page, and not only pages that show records.

## Quick index

| Symptom | Section |
| --- | --- |
| `Database file at path [...] does not exist` or `unable to open database file` | [Missing SQLite file](#missing-sqlite-file) |
| `no such table: sessions` or `relation "sessions" does not exist` | [Tables missing](#tables-missing-because-migrations-did-not-run) |
| Tests touch the wrong database, or wipe development data | [DB_DATABASE overriding PHPUnit](#db_database-or-another-environment-variable-overriding-phpunit) |
| Search works locally but not on the live site, or only on the live site | [SQLite and PostgreSQL differences](#sqlite-and-postgresql-behave-differently) |
| `LazyLoadingViolationException` | [Lazy-loading exceptions](#lazy-loading-exceptions) |
| A deploy stops, or `migrate` throws | [Migration failures](#migration-failures) |
| `violates check constraint` or `CHECK constraint failed` | [Enum values rejected](#enum-values-rejected-by-a-check-constraint) |
| `UNIQUE constraint failed: program_applications...` | [Duplicate application](#duplicate-application-for-the-same-programme) |
| `database is locked` | [SQLite database is locked](#sqlite-database-is-locked) |
| Everything returns 500 about 30 days after launch | [Render database expired](#render-free-database-expired-or-unreachable) |

## Missing SQLite file

**Symptoms.** A command other than `migrate`, or a page request, fails with `Illuminate\Database\QueryException: Database file at path [...database.sqlite] does not exist. Ensure this is an absolute path to the database.` Older PHP and Laravel versions show `SQLSTATE[HY000] [14] unable to open database file`.

**Cause (reproduced).** `.env.example` sets `DB_CONNECTION=sqlite` and `config/database.php` defaults the path to `database/database.sqlite`. `database/.gitignore` ignores `*.sqlite*`, so a fresh clone has no database file. `php artisan migrate --force` (which `composer setup` runs) creates a missing SQLite file. `php artisan migrate:status`, `php artisan db:seed`, `php artisan tinker`, `php artisan serve` page requests and `php artisan test` against a file path do not create it, and fail with the error above. The error also appears when `DB_DATABASE` points at the wrong path, or a relative path is used from a different working directory.

**Diagnose.**

```powershell
Test-Path database\database.sqlite
```

```bash
ls -l database/database.sqlite
```

Also check that `DB_DATABASE` is not set to another path in your shell or `.env`.

**Fix.**

PowerShell:

```powershell
New-Item database\database.sqlite -ItemType File
php artisan migrate
```

bash:

```bash
touch database/database.sqlite
php artisan migrate
```

**Prevent.** Run `php artisan migrate` first on a new clone, which creates the file ([development-environment-setup](../05-developer-guide/development-environment-setup.md)). Do not commit the file (it is ignored on purpose).

## Tables missing because migrations did not run

**Symptoms.** `SQLSTATE[HY000]: General error: 1 no such table: sessions` (SQLite) or `SQLSTATE[42P01]: Undefined table: relation "sessions" does not exist` (PostgreSQL). The error appears on the very first request, before any page renders.

**Cause (from the code).** The session, cache and queue drivers are database tables created by the first three migrations. With an empty database, every request fails when it starts the session. The Docker image runs `php artisan migrate --force` on every start unless `RUN_MIGRATIONS=false`. If that variable is `false`, or migrations failed, the tables are missing.

**Diagnose.** `php artisan migrate:status`. For a hosted site, read the container log for the line from `php artisan migrate --force` and check whether `RUN_MIGRATIONS` is set.

**Fix.** `php artisan migrate --force` against the correct database. Take a [backup](backup-and-restore.md) first if the database holds data.

**Prevent.** Leave `RUN_MIGRATIONS` unset (it defaults to `true`) unless a separate release step runs migrations.

## DB_DATABASE (or another environment variable) overriding PHPUnit

**Symptoms.** Tests fail with errors about missing tables on a database you did not expect, run slowly against PostgreSQL, or change data in a development database. Test results differ between machines.

**Cause (from the code).** [`phpunit.xml`](../../phpunit.xml) sets `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` and `DB_URL=""` with `<env>` elements. PHPUnit does not replace a variable that already exists in the operating system environment unless the element has `force="true"`, and these do not. A variable exported in your shell (or set by a previous script in the same terminal) wins. Laravel reads the environment before `.env`, so the `.env` file is not the cause. The CI workflow relies on this order on purpose: it exports `DB_CONNECTION`, `DB_URL` and `DB_DATABASE` for the PostgreSQL leg.

A second cause is a cached configuration. After `php artisan config:cache` (the Docker entrypoint runs `php artisan optimize`), the application ignores environment variables entirely, including those in `phpunit.xml`. `composer test` runs `php artisan config:clear` first for this reason.

> **Warning:** Test classes use `RefreshDatabase`. If a test run is pointed at a real database, it drops and recreates every table. Never run tests with `DB_URL` or `DB_DATABASE` set to data you want to keep.

**Diagnose.**

PowerShell:

```powershell
Get-ChildItem Env: | Where-Object Name -Like 'DB_*'
Get-ChildItem Env: | Where-Object Name -Like 'APP_ENV'
```

bash:

```bash
env | grep -E '^(DB_|APP_ENV)'
```

**Fix.** Remove the variables from the session and clear the cache, then run the tests.

PowerShell:

```powershell
Remove-Item Env:DB_DATABASE, Env:DB_URL, Env:DB_CONNECTION -ErrorAction SilentlyContinue
php artisan config:clear
composer test
```

bash:

```bash
unset DB_DATABASE DB_URL DB_CONNECTION
php artisan config:clear
composer test
```

**Prevent.** Use a fresh terminal for tests. Use the throwaway-file approach in [migrations.md](migrations.md) when you need a real SQLite file. Recommended, not implemented: add `force="true"` to the `DB_*` entries in `phpunit.xml`. That is a code change and is not part of this documentation task.

## SQLite and PostgreSQL behave differently

Tests pass on SQLite and fail on PostgreSQL (CI runs both), or the live site behaves differently from your laptop. Known differences that matter here:

### Case sensitivity of `LIKE`

**Symptom.** A search for `robotics` finds "Robotics" locally but not on the live site.

**Cause.** `LIKE` is case-insensitive on SQLite and case-sensitive on PostgreSQL. [`Searchable::scopeSearch`](../../app/Models/Concerns/Searchable.php) handles this by using `ilike` on PostgreSQL and escaping `%`, `_` and `\` in the term. `Api\V2\PartnerController` does the same by hand. Raw `where('column', 'like', ...)` in new code is case-sensitive on PostgreSQL.

**Fix.** Use the `search` scope with the columns you want, or switch the operator on `getDriverName() === 'pgsql'`. The audit log area filter (`action like 'area.%'`) is lower case by convention and is unaffected.

### Type strictness

**Symptom.** A query works on SQLite and fails on PostgreSQL with `invalid input syntax for type bigint: "abc"` or `operator does not exist: boolean = integer`.

**Cause.** SQLite compares values of any type. PostgreSQL does not. For example a URL filter such as `?user=abc` would raise an error on PostgreSQL if passed straight to `where('user_id', ...)`. The code guards these inputs with `ctype_digit` (see `AuditLogController`, and the year and organisation filters in `Project::scopeFilter`). Boolean columns need PHP booleans, not the strings `'yes'` or the integer `1` inside raw SQL.

**Fix.** Validate or cast request input before it reaches a query. Use Eloquent with PHP booleans.

### GROUP BY and SELECT

**Symptom.** `column "x" must appear in the GROUP BY clause or be used in an aggregate function`, only on PostgreSQL.

**Cause.** SQLite accepts selecting columns that are not grouped. PostgreSQL rejects it. The reports in `MetricsService` and `InsightsService` group by one column and select only aggregates.

**Fix.** Select only grouped columns and aggregates, and run the PostgreSQL leg of the tests (`CI` runs it, or run PHPUnit against a local PostgreSQL).

### Dates and years

`Project::yearExpression()` switches between `strftime`, `EXTRACT(YEAR ...)` and `YEAR()`. Any new raw date expression needs the same switch.

### Collation and ordering

Sorting `name` columns can give a different order for accented or mixed-case values, because SQLite sorts by byte and PostgreSQL by the database collation. Tests that assert an order of mixed-case strings can pass on one engine and fail on the other.

### Unique indexes

Unique indexes are case-sensitive on both engines for `varchar`. The application lower-cases e-mail addresses before saving them. A slug or organisation name that differs only by case is accepted.

**Prevent (all of the above).** Write portable Eloquent. Run the suite on both engines before merging; CI does this automatically.

## Lazy-loading exceptions

**Symptoms.** `Illuminate\Database\LazyLoadingViolationException: Attempted to lazy load [author] on model [App\Models\Post] but lazy loading is disabled.` on a page or in a test, on a developer machine or in CI. The same page works on the live site.

**Cause (from the code).** [`AppServiceProvider`](../../app/Providers/AppServiceProvider.php) calls `Model::preventLazyLoading(! $this->app->isProduction())`. Outside production, using a relation that was not eager loaded throws, so N+1 queries are found during development. In production the same code silently runs one query per row. The live site therefore never shows this error, but it can be slow.

**Diagnose.** The exception names the relation and the model. The stack trace points at the Blade view or service that touched it.

**Fix.** Eager load where the data is fetched: `Post::with('author:id,name')->...`, or load on an existing model with `$model->load('relation')`. For counts use `withCount`. `Program::scopeWithActiveApplicationsCount` shows the pattern for capacity.

**Prevent.** Add `with(...)` in the controller or service when you add a relation to a view. Write a feature test that loads the page; it fails on the exception.

## Migration failures

**Symptoms.** `php artisan migrate` stops with an `SQLSTATE` error. On Render the deploy fails because the container exits during start-up (the entrypoint runs `php artisan migrate --force` under `set -e`).

| Error text | Cause | Fix |
| --- | --- | --- |
| `table "x" already exists` / `relation "x" already exists` (42P07) | The table was created by hand or by an earlier run that did not record the migration | Add a `Schema::hasTable` guard (migration 16 does this for `notifications`) or drop the stray table on a scratch database |
| `Cannot add a NOT NULL column with default value NULL` (SQLite) or `column "x" contains null values` (PostgreSQL) | A new `NOT NULL` column with no default on a table that has rows | Add `->default(...)` or `->nullable()`, as the additive-only rule requires |
| `violates foreign key constraint` / `FOREIGN KEY constraint failed` | A row refers to a parent that does not exist | Fix or remove the orphaned rows, then re-run |
| `index ... already exists` | Re-running a migration that was partly applied on SQLite | Restore the pre-migration backup and run again |
| `Unable to locate ... column` when dropping on SQLite | `down()` drops a column that is still in an index | Drop the index first (`dropIndex`), then the column |
| Identifier too long (PostgreSQL, 63 bytes) | A generated index name is too long | Pass an explicit short index name |

**Diagnose.**

```bash
php artisan migrate:status
php artisan migrate --pretend      # print the SQL, run nothing
```

PostgreSQL runs a migration in a transaction, so a failed migration leaves no partial change and is not recorded. On SQLite a failed migration can leave the file half changed. Restore from a copy ([backup-and-restore.md](backup-and-restore.md)).

**Fix forward.** Do not edit a migration that already ran in production. Add a new migration that corrects the schema. Edit a migration only if it has never run anywhere except your machine.

**Roll back.** `php artisan migrate:rollback --step=1` reverses the latest migration file. Only the newest migration's `down()` is exercised by CI ([migrations.md](migrations.md)), so rehearse older rollbacks on a scratch database first. Migration 14 does not reverse its data change. Rolling back after a data-affecting migration needs a backup restore.

**Prevent.** Follow the checklist in [migrations.md](migrations.md). CI runs `migrate`, `migrate:rollback --step=1`, `migrate` and `db:seed` on PostgreSQL.

## Enum values rejected by a CHECK constraint

**Symptoms.** `SQLSTATE[23514]: Check violation: new row for relation "projects" violates check constraint "projects_status_check"` (PostgreSQL) or `CHECK constraint failed: status` (SQLite), when saving a project, post or legacy grant application.

**Cause (constraints reproduced on SQLite, failing insert from the code).** `projects.status`, `posts.type` and `grant_applications.status` have CHECK constraints from `$table->enum()`. If a value is added to `ProjectStatus`, `PostType` or `GrantStatus` without a migration that rewrites the constraint, saving that value fails. The other status columns have no constraint and accept the new value.

**Diagnose.** Compare the enum cases in `app/Enums/` with the lists in [tables-and-columns.md](tables-and-columns.md#enum-columns-with-a-check-constraint).

**Fix.** Write a migration that drops and recreates the constraint, or converts the column to a plain `varchar`. SQLite needs a table rebuild for this. Test it on both engines.

**Prevent.** Create new status columns with `string(...)`, not `enum`.

## Duplicate application for the same programme

**Symptoms.** `SQLSTATE[23000]: Integrity constraint violation: UNIQUE constraint failed: program_applications.program_id, program_applications.user_id` (SQLite) or `duplicate key value violates unique constraint "program_applications_program_id_user_id_unique"` (PostgreSQL).

**Cause.** The table allows one row per member per programme. A withdrawn application keeps its row (`ApplicationService::withdraw` only changes the status), so the member cannot start a new one for the same programme.

**Diagnose.**

```sql
SELECT id, reference, status FROM program_applications WHERE program_id = <ID> AND user_id = <ID>;
```

**Fix.** The member can see the withdrawn application, but a new application needs either a database edit or a product change (reuse the withdrawn row). Take a backup first and ask the Programme Officer before changing a row by hand. > **Requires confirmation:** the intended behaviour for re-applying after withdrawal (it is listed as an open issue in the technical debt register).

## SQLite database is locked

**Symptoms.** `SQLSTATE[HY000]: General error: 5 database is locked` during a request, a test or `php artisan migrate`.

**Cause (from the code).** SQLite allows one writer at a time. `config/database.php` sets `busy_timeout` to `null`, so a writer does not wait and fails immediately if another connection holds the lock. Because sessions, cache and the queue are database tables, a normal page request writes to the database. A second process makes contention likely: `composer dev` runs `php artisan serve` and `php artisan queue:listen` together, and a GUI database browser with an open transaction can hold a lock.

**Diagnose.** Find processes that use the file.

PowerShell:

```powershell
Get-Process php, php-cgi -ErrorAction SilentlyContinue | Select-Object Id, ProcessName, StartTime
```

bash:

```bash
lsof database/database.sqlite
```

Look for an open `database/database.sqlite-journal` file. It exists while a write is in progress. A journal left after a crash can be a sign of an interrupted write.

**Fix.** Stop other PHP processes and close the GUI tool, then retry. Do not delete a `-journal` file while a process might still be writing. If the database is corrupt after a crash, restore your last copy ([backup-and-restore.md](backup-and-restore.md)).

**Prevent.** Use one process at a time for write-heavy work, or use PostgreSQL locally. Recommended, not implemented: set a `busy_timeout` in the `sqlite` block of `config/database.php` (the value is not read from an environment variable today).

## Render free database expired or unreachable

**Symptoms.** About 30 days after the database was created, every page returns a 500 error. The log shows `SQLSTATE[08006]` connection errors, `could not translate host name`, or `password authentication failed`. The Render health check can still pass, because `/up` (`bootstrap/app.php`, `health: '/up'`) is Laravel's built-in route and does not query the database.

**Cause.** [`render.yaml`](../../render.yaml) states that free Render PostgreSQL databases expire after 30 days. Sessions and cache are database tables, so no page can render once the connection is gone.

**Diagnose.**

- Check the database's status in the Render dashboard.
- From a machine with the PostgreSQL client, using the external connection string (scheme `postgresql://`): `psql "<DB_URL>" -c "select 1"`.
- Read the web service log for the first `SQLSTATE` error and its time.

**Fix.** If you have a dump, create a new database, restore it, point `DB_URL` at it and redeploy. The steps are in [backup-and-restore.md](backup-and-restore.md#move-to-a-new-database-after-the-30-day-expiry). If there is no dump, the data cannot be recovered from this repository.

**Prevent.** Take a dump before day 30, back up uploaded files, and move to a paid or external PostgreSQL service for real use. Recommended, not implemented: an external health check that opens a database connection, since `/up` does not.

## Other errors you may meet

| Symptom | Likely cause | What to do |
| --- | --- | --- |
| `SQLSTATE[08006]` or `connection refused` on a local PostgreSQL | Wrong `DB_URL`, server not running, `pgsql://` scheme given to `psql` | Use the `postgresql://` scheme for `psql` and `pg_dump`. Laravel accepts both. |
| Pages show stale counts after a restore or seeding | Metrics are cached for `METRICS_CACHE_TTL` seconds in the `cache` table | `php artisan cache:clear` |
| New `.env` values are ignored | `php artisan optimize` cached the configuration | `php artisan config:clear`, restart the server |
| `Class "PDO" not found` or `could not find driver` | PHP extension missing: `pdo_sqlite` or `pdo_pgsql` | Enable the extension in `php.ini`. CI installs `pdo_sqlite` and `pdo_pgsql`. |
| Seeding stops with "Skipping demo data in production" | `APP_ENV=production` and `SEED_DEMO_DATA` is not true | Expected. See [seed-data.md](seed-data.md). |
| Gallery or document files are missing but rows exist | Files live on the container disk, which is wiped on redeploy on the Render free plan | Use `s3` disks. See [backup-and-restore.md](backup-and-restore.md). |
