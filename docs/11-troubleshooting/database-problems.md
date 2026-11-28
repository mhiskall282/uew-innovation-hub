# Database Problems

> **Audience:** Developers, operators · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Database overview](../07-database/database-overview.md), [Migrations](../07-database/migrations.md), [Database development](../05-developer-guide/database-development.md), [Backup and restore](../07-database/backup-and-restore.md)

The app runs on SQLite locally and in tests, and on PostgreSQL on Render (`render.yaml`). MySQL is supported by the code (`pdo_mysql` is in the Docker image). This page covers connection, migration and engine-difference problems.

## Which database am I using?

```powershell
php artisan about
php artisan config:show database.default
php artisan migrate:status
```

In the workspace, **Site settings > System** shows `Database`. `.env.example` defaults to `DB_CONNECTION=sqlite`; `render.yaml` sets `DB_CONNECTION=pgsql` and `DB_URL` from the managed database.

## SQLite database file does not exist

**Symptoms:** `Database file at path [...database.sqlite] does not exist` (a `QueryException`), or `could not find driver`.

**Possible causes:** `database/database.sqlite` was never created. `composer create-project` creates it. `composer setup` runs `php artisan migrate --force`, and Laravel's migrate command then offers to create a missing SQLite file with an interactive prompt ("Would you like to create it?"). In a non-interactive shell that prompt cannot be answered, so the command fails. `pdo_sqlite` is not enabled. `DB_DATABASE` points somewhere else.

**Diagnostic steps:**

```powershell
Test-Path database\database.sqlite
php -m | Select-String pdo_sqlite
Get-ChildItem Env:DB_*
```

**Resolution:**

```powershell
New-Item database\database.sqlite -ItemType File
php artisan migrate
```

```bash
touch database/database.sqlite
php artisan migrate
```

If `Env:DB_DATABASE` shows a value, remove it (`Remove-Item Env:DB_DATABASE`) and run `php artisan optimize:clear`.

**Prevention:** Keep `DB_DATABASE` unset in the shell. Laravel resolves the default SQLite path itself.

## "no such table" or "relation ... does not exist"

**Symptoms:** `SQLSTATE[HY000]: no such table: settings` (SQLite) or `relation "sessions" does not exist` (PostgreSQL). The home page may still load, because `SettingsService` falls back to defaults when the database fails, but sign-in fails if sessions are stored in the database.

**Possible causes:** Migrations were not run on the database the app is using, `RUN_MIGRATIONS=false`, or a stray `DB_DATABASE` points to another file.

**Diagnostic steps:**

```powershell
php artisan migrate:status
```

**Resolution:**

```powershell
php artisan migrate
```

On Render the entrypoint runs `php artisan migrate --force` on every boot unless `RUN_MIGRATIONS=false`. Check the deploy log for migration errors.

**Prevention:** Run migrations as part of every deploy and keep `SESSION_DRIVER=database`, `CACHE_STORE=database` and `QUEUE_CONNECTION=database` tables in step (they come from the framework migrations in `database/migrations`).

## Migration fails part way

**Symptoms:** The container restarts in a loop, or `php artisan migrate` stops with an SQL error.

**Possible causes:** A column already exists (manual change), a foreign key fails because of existing data, a type difference between engines (below), or a lost connection.

**Diagnostic steps:**

```powershell
php artisan migrate:status
php artisan migrate --pretend
```

`--pretend` prints SQL without running it.

**Resolution:** Fix the cause, then `php artisan migrate`. Do not edit a migration that has already run on a shared database. Add a new one. Never run `migrate:fresh` or `migrate:rollback` on a database that holds real data: it deletes tables. Restore from backup if that happens.

> **Warning:** `migrate:fresh` drops every table. Only use it on a throwaway local database.

**Prevention:** CI runs migrations up, down and up on PostgreSQL (job *Tests (pgsql)*). Run `php artisan test` before merging.

## PostgreSQL connection refused or SSL error

**Symptoms:** `SQLSTATE[08006] could not connect to server`, `SSL connection is required`, or timeouts on the first request after a deploy.

**Possible causes:** Wrong `DB_URL`, database not ready yet, missing `?sslmode=require` for a hosted database (Neon), IP restrictions, or the free database has expired.

**Diagnostic steps:** Check `DB_URL` shape (do not paste it anywhere):

```
postgresql://user:password@host:5432/dbname?sslmode=require
```

```powershell
php artisan tinker --execute="dump(DB::connection()->getPdo() ? 'connected' : 'no');"
```

**Resolution:** Correct `DB_URL`. On Render use the **internal** connection string from the database page (`fromDatabase` in `render.yaml`). Re-deploy.

**Prevention:** Free Render PostgreSQL databases **expire after 30 days** (comment in `render.yaml`). Upgrade the plan, or use a free Neon database (see [deployment guide](../deployment.md)). Take backups.

## Differences between SQLite, PostgreSQL and MySQL

The test suite runs on SQLite and PostgreSQL in CI. Known differences that affect this code:

| Area | Behaviour |
| --- | --- |
| Month grouping in reports | `InsightsService::growth()` uses `strftime` (SQLite), `to_char` (pgsql) or `DATE_FORMAT` (other). A new driver needs a new branch |
| JSON columns | Member `interests` are filtered with `whereJsonContains`. Works on all three, with different SQL. Queries on PostgreSQL treat the column as JSON, so malformed data fails there first |
| Enums | Stored as strings, with no database enum type |
| Foreign keys | Enforced on PostgreSQL. SQLite enforces them only when `foreign_key_constraints` is on (Laravel turns it on by default) |
| Case sensitivity | Plain `LIKE` is case-sensitive on PostgreSQL. The `Searchable` trait (`app/Models/Concerns/Searchable.php`) uses `ilike` on pgsql and `like` elsewhere, so workspace search behaves the same. Hand-written `where('col', 'like', ...)` queries do not get this |
| Strict typing | PostgreSQL rejects a string where an integer is expected. SQLite accepts it |
| Boolean values | PostgreSQL needs real booleans. Raw SQL with `1`/`0` fails |

**Possible symptom:** A test passes on SQLite and fails on PostgreSQL in CI. **Resolution:** Reproduce with PostgreSQL locally (set `DB_CONNECTION=pgsql` and `DB_URL` in a throwaway shell variable, never in a tracked file) and fix the query. Remove `DB_URL` afterwards.

**Prevention:** Use Eloquent or the query builder instead of raw SQL. Where raw SQL is needed, branch on `DB::connection()->getDriverName()` as `InsightsService` does.

## Lazy loading exceptions

**Symptoms:** `Illuminate\Database\LazyLoadingViolationException: Attempted to lazy load [author] on model [App\Models\Post] but lazy loading is disabled.` Appears in local development and tests, never in production.

**Cause:** `AppServiceProvider::boot()` calls `Model::preventLazyLoading(! $this->app->isProduction())`. Any code or view that reads a relation that was not eager-loaded fails outside production. This catches N+1 queries early.

**Diagnostic steps:** The stack trace names the relation and the model. Find the query in the controller.

**Resolution:** Add `->with('relation')` (or `->with('relation:id,name')`) to the query, or `$model->load('relation')`. Do not turn the guard off.

**Prevention:** Eager load in the controller, as `ProjectController::index` does with `with('organizations:id,name')`. Run `php artisan test` because CI fails on these.

## Database is locked (SQLite)

**Symptoms:** `SQLSTATE[HY000]: General error: 5 database is locked`.

**Possible causes:** Two processes write at once, for example `php artisan serve`, `queue:listen` and a test run using the same file.

**Resolution:** Stop the extra process. Tests use `:memory:`, so make sure no `DB_DATABASE` is set. For heavier concurrency use PostgreSQL.

## Data is missing after a deploy

**Cause options:** (1) The database host replaced the database (free plan expiry). (2) `SEED_DEMO_DATA=true` on a fresh database re-created only demo content. (3) Files were lost but rows remain. See [deployment problems](deployment-problems.md#uploaded-files-disappear-after-a-deploy).

**Diagnostic steps:** Compare row counts and check **Audit log** for the earliest entry.

```powershell
php artisan tinker --execute="dump(App\Models\Project::count(), App\Models\User::count());"
```

**Prevention:** Backups. See [backup and restore](../07-database/backup-and-restore.md).

## Archived records "disappeared"

Projects, programmes, news posts, publications and legacy grant applications use soft deletes. They vanish from every list but stay in the table with `deleted_at` set. The workspace has no restore screen. To restore one on a copy first:

```powershell
php artisan tinker --execute="App\Models\Project::withTrashed()->find(12)->restore();"
```

Replace `12` with the real ID. Test on a copy of the database before production.
