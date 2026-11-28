# Database Maintenance

> **Audience:** Maintainers, developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [../07-database/backup-and-restore.md](../07-database/backup-and-restore.md), [../07-database/data-retention.md](../07-database/data-retention.md), [../07-database/migrations.md](../07-database/migrations.md), [technical-debt-register.md](technical-debt-register.md)

Production uses PostgreSQL on Render (`render.yaml`, database `uew_innovation_hub`, plan `free`). Local development and the SQLite CI job use SQLite. `.env.example` sets `SESSION_DRIVER`, `CACHE_STORE` and `QUEUE_CONNECTION` to `database`, so sessions, cache and queued jobs also live in the same database.

> **Warning:** The Render free PostgreSQL database expires (30 days according to `.ai/state.md`). Move to a paid plan or another host before launch (TD-016).

## Tables that grow

| Table or data | Why it grows | Current clean-up |
| --- | --- | --- |
| `activity_logs` (audit log) | Every audited action and export | None |
| `sessions` | One row per sign-in session | Laravel lottery cleanup only |
| `cache`, `cache_locks` | Database cache driver | Expired keys are removed when read |
| `jobs`, `failed_jobs` | Queued e-mail and broadcasts | Successful jobs are deleted. Failed jobs stay until removed |
| `notifications` | In-app notifications | None |
| `password_reset_tokens` | Reset requests | Expired rows stay until reused |

Check exact table names in [tables-and-columns](../07-database/tables-and-columns.md) or [../07-database/tables-and-columns.md](../07-database/tables-and-columns.md).

## Routine tasks

| Task | Command | Notes |
| --- | --- | --- |
| Show migration state | `php artisan migrate:status` | 17 migration files exist |
| Apply migrations | `php artisan migrate --force` | Runs automatically in `docker/entrypoint.sh` at each deploy |
| List failed jobs | `php artisan queue:failed` | Built-in Laravel command |
| Retry or clear failed jobs | `php artisan queue:retry all`, `php artisan queue:flush` | `queue:flush` deletes records |
| Prune expired reset tokens | `php artisan auth:clear-resets` | Built-in |
| Back up | Render database backup or `pg_dump` | See the backup page. Not automated by the app |

> **Warning:** `migrate:fresh`, `migrate:rollback` and `db:wipe` destroy data. Never run them on production. `hub:import-documents` and seeders change data: read [../07-database/seed-data.md](../07-database/seed-data.md) first.

## Schema change rules

1. Add a new migration. Never edit one that has run in production.
2. Provide a working `down()` method. CI runs migrate up, down, up on PostgreSQL.
3. Make destructive changes in two releases: stop using the column, then drop it.
4. Take a backup before deploying a migration, because deploy runs it with `--force`.
5. Keep legacy data paths intact (`AGENTS.md`: zero data loss).

## Restore drill

Once a month, restore the latest backup into a scratch database, run `php artisan migrate:status` against it, and open a few records. A backup that was never restored is not proven.

## Recommended, not implemented

- A scheduled prune job for `activity_logs` (for example older than the retention period the owner chooses), notifications and failed jobs (`queue:prune-failed`, `model:prune`).
- Move sessions, cache and queue to Redis when traffic grows (`.env.example` mentions it).
- Soft-delete restore screen (TD-007).
