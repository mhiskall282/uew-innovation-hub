# Database Development

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [adding-new-features](adding-new-features.md), [testing-guide](testing-guide.md), `../07-database/migrations.md`, `../07-database/seed-data.md`, `../07-database/tables-and-columns.md`

How to change the schema, write models and queries, and seed data in this project. SQLite is used for local work and most tests. PostgreSQL is used in production and in the second CI test run, so code must work on both.

## Databases in use

| Where | Database | Configured by |
| --- | --- | --- |
| Local development | SQLite `database/database.sqlite` | `.env`: `DB_CONNECTION=sqlite` |
| PHPUnit | SQLite in memory | `phpunit.xml` |
| CI `Tests (pgsql)` job | PostgreSQL 16 | env set in `ci.yml` |
| Playwright | SQLite `database/e2e.sqlite` | `playwright.config.ts` |
| Production (Render) | PostgreSQL | `DB_URL` from `render.yaml` |

`pdo_mysql` is installed in the Docker image and MySQL is mentioned in `.env.example`. CI does not test it.

## Migrations

Location: `database/migrations/`. Names: `YYYY_MM_DD_NNNNNN_description.php`. Files return an anonymous class extending `Migration`.

```powershell
php artisan make:migration add_deadline_to_programs_table --table=programs
php artisan migrate                    # apply
php artisan migrate:status             # what has run
php artisan migrate:rollback --step=1  # undo the last batch step (local only)
php artisan migrate:fresh --seed       # drop everything, rebuild, seed (local only)
```

> **Warning:** `migrate:fresh` and `migrate:rollback` destroy data. Use them only on a local or scratch database. Never run them with `--force` against production.

Rules (from `.ai/rules/database.md` and the existing migrations):

1. Every schema change is a new migration. Do not edit a migration that has already run in production. Add a new one.
2. Prefer additive changes (new nullable column, new table, new index). Dropping or renaming a column needs a deprecation step first.
3. Always write `down()` so CI's rollback step passes.
4. Foreign keys state their behaviour: `->constrained()->nullOnDelete()`, `->cascadeOnDelete()`. Look at `create_gallery_tables`.
5. Index foreign keys, status columns, date columns, slugs and filter combinations (`$table->index(['is_published', 'event_date'])`). `2026_11_24_000001_add_scalability_indexes.php` is an example.
6. Money and counts: integer or `decimal(12, 2)`. No floats.
7. Enums are stored as plain string columns. The enum class (`app/Enums/`) is the source of truth. There is no database `ENUM` type, so adding a case needs no migration unless you need a constraint or backfill.
8. Do not use SQLite-only or PostgreSQL-only SQL in migrations. If you must, branch on `Schema::getConnection()->getDriverName()`.

### Production runs your migration automatically

`docker/entrypoint.sh` runs `php artisan migrate --force` on every container start unless `RUN_MIGRATIONS=false`. A merge to `main` therefore migrates the production database. Before pushing a migration:

- Run it on a copy of realistic data if the table is large or the change rewrites rows.
- Test up, down and up locally: `php artisan migrate; php artisan migrate:rollback --step=1; php artisan migrate`.
- Check that existing rows satisfy new `NOT NULL` or unique rules. Give new `NOT NULL` columns a default.
- Take a backup first for risky changes. See `../07-database/backup-and-restore.md`.

## Models

- Models live in `app/Models/` and are plain `class` declarations. Use `$fillable`, and a `casts()` method.
- Enum columns cast to enums: `'status' => ProjectStatus::class`.
- Soft deletes are used on `GrantApplication`, `Post`, `Program`, `Project` and `Publication`. Other models delete rows for real.
- Traits in `app/Models/Concerns/`:

| Trait | Use |
| --- | --- |
| `HasUniqueSlug` | Creates a unique slug from `title` on create and uses `slug` as the route key. Includes soft-deleted rows in the uniqueness check |
| `Searchable` | `scopeSearch($term, $columns)`: case-insensitive search, `ILIKE` on PostgreSQL and `LIKE` elsewhere, escapes `%` and `_` in user input |
| `FlushesMetrics` | Clears the cached dashboard and public metrics (`MetricsService`) on save, delete and restore |

- `Model::preventLazyLoading()` is on outside production. In every list or detail page, eager-load what the view reads.
- Wrap writes that touch several tables in `DB::transaction()` (see `ProjectService::create`).
- Do not run queries inside Blade views.

## PostgreSQL and SQLite differences to remember

| Topic | Rule |
| --- | --- |
| Case-insensitive search | Use the `Searchable` trait, not a hand-written `LIKE` |
| Boolean columns | Use Eloquent casts and `where('is_published', true)`; avoid comparing to `1` in raw SQL |
| Grouping and selecting | PostgreSQL rejects selecting non-grouped columns that SQLite allows. The pgsql CI job catches these |
| Date functions | Avoid `strftime` or `DATE_FORMAT` in raw SQL. Group in PHP or use portable query builder methods |
| Identifier case | Use lower-case `snake_case` names |

## Factories and seeders

| File | Purpose |
| --- | --- |
| `database/factories/*Factory.php` | Test data for `GrantApplication`, `Organization`, `Post`, `ProgramApplication`, `Program`, `Project`, `Publication`, `User` |
| `database/seeders/DatabaseSeeder.php` | Always runs `PageSeeder`. Runs demo seeders unless in production without `SEED_DEMO_DATA=true`. In production it also needs a strong `SEED_PASSWORD` and skips if demo data already exists |
| `database/seeders/PageSeeder.php` | Default About, FAQ, Privacy and Terms pages. Never overwrites edits. Run on every container boot |
| `database/seeders/DemoContentSeeder.php` | One demo account per role, projects, publications, news. Idempotent |
| `database/seeders/DemoProgrammesSeeder.php` | Demo programmes and applications |

Demo passwords come from `SEED_PASSWORD`. `php artisan hub:secure-demo-accounts` replaces well-known default passwords with that value, and the container runs it on every boot.

> **Warning:** `DatabaseSeeder` contains the same "Not seeding demo accounts: set SEED_PASSWORD" check twice in a row (`database/seeders/DatabaseSeeder.php`). The second copy is dead code. It is harmless. See the technical debt register in `../12-maintenance/technical-debt-register.md`.

## Inspecting data

```powershell
php artisan db:show
php artisan db:table programs
php artisan tinker
```

The full schema is documented in `../07-database/tables-and-columns.md`. A machine-generated dump exists at a schema dump of a fresh migration run (see [tables-and-columns](../07-database/tables-and-columns.md)) (not part of the application).

## Legacy data

Legacy MySQL projects, organisations and PDFs were imported earlier. The `legacy_id` column on `projects` links back to the old IDs. PDFs from `reports/` and `publications/` at the repository root are copied onto the documents disk by `php artisan hub:import-documents`. Do not rename or delete those folders. See `../07-database/data-retention.md`.
