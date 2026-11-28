# Backup and Recovery

> **Audience:** Operators, Hub Director · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Rollback procedures](rollback-procedures.md), [Production deployment](production-deployment.md), [Database backup and restore](../07-database/backup-and-restore.md), [Data retention](../07-database/data-retention.md)

What needs backing up, what the repository provides (nothing automated), and how to recover. **The repository contains no backup script, no scheduled backup job and no restore command.** Everything below is either a platform feature to switch on or a manual procedure. The database-level detail lives in [Database backup and restore](../07-database/backup-and-restore.md); this page covers the whole deployment.

## What holds state

| Data | Location | Backup method | Risk on the current test site |
| --- | --- | --- | --- |
| Application data (users, programmes, applications, audit log, settings, CMS pages) | PostgreSQL | Database dump or platform backup | **High.** Render's free database expires after 30 days. |
| Documents (PDF reports, publications, proposals) | `DOCUMENTS_DISK`: container disk or S3 bucket | Bucket versioning, or bucket copy | Lost on every redeploy with `private` |
| Gallery photos | `GALLERY_DISK`: container disk or S3 bucket | Same as above | Lost on every redeploy with `gallery` |
| Encrypted SMTP password | `settings` table, encrypted with `APP_KEY` | Database backup **plus** a copy of `APP_KEY` | Unreadable if the key is lost |
| `APP_KEY` and other secrets | Render environment | A password manager or vault entry | A lost key invalidates sessions, signed links and the stored SMTP password |
| Code | GitHub | Git history | Safe. Never rewrite `main`. |
| Legacy PDFs | `reports/`, `publications/` in the repository | Git | Safe. `hub:import-documents` can copy them again. |
| Logs | Render log stream | None | Short retention |

## Recommended backup plan (not implemented)

| Item | Frequency | How |
| --- | --- | --- |
| PostgreSQL | Daily, retain 14 to 30 days | Paid Render Postgres backups (check the plan's retention in the Render dashboard), or a scheduled `pg_dump` from a machine you control |
| Bucket | Continuous | Enable object versioning on the S3 or R2 bucket, and optionally replicate to a second bucket |
| `APP_KEY` | Once, and again on rotation | Store in the institution's password manager, separate from the database backups |
| Restore test | Quarterly | Restore the latest dump into a scratch database and open the site against it |

> **Requires confirmation:** the exact backup features and retention of the Render plan that will be used for production. The repository only states that the free database expires after 30 days.

## Taking a manual database backup

Get the external connection string from the Render database page (it contains the password, so do not paste it into tickets or commit it).

bash:

```bash
pg_dump --format=custom --no-owner --file="uew-hub-$(date +%F).dump" "$DB_URL"
```

PowerShell:

```powershell
pg_dump --format=custom --no-owner --file="uew-hub-$(Get-Date -Format yyyy-MM-dd).dump" $env:DB_URL
```

Store the file somewhere encrypted. It contains personal data (member names, e-mail addresses, application content). See [Data protection](../08-security/data-protection.md).

For local SQLite, copy the file `database/database.sqlite` while the app is not writing to it.

## Restoring

> **Warning:** A restore overwrites data. Do it first on a new, empty database and check it. Only then repoint production. Never restore over a live database without a fresh dump of its current state.

1. Create an empty PostgreSQL database (a new Render database, or a local one).
2. Restore:

```bash
pg_restore --no-owner --dbname="$NEW_DB_URL" uew-hub-2026-11-30.dump
```

3. Point the service at the new database by changing `DB_URL`, then redeploy. On boot the entrypoint runs `php artisan migrate --force`, which brings an older dump up to the current schema. Migrations only add what is missing.
4. Keep the same `APP_KEY` as when the dump was taken. Otherwise re-enter the SMTP password under **Site settings > E-mail delivery**, and expect all sessions and signed links to be invalid.
5. Check that the bucket still holds the files the database references (documents under `reports/` and `publications/`, photos for `album_photos`). Missing gallery files are hidden by `AlbumPhoto::isAvailable()` rather than shown broken.
6. Run the post-deploy checks in [Health checks](health-checks.md).

## Recovery scenarios

| Scenario | Steps |
| --- | --- |
| Render free database expired | Restore the latest dump into a new database, set `DB_URL`, redeploy. Without a dump, the data is gone. For an empty database the entrypoint recreates the Hub Director from `ADMIN_*` and the default CMS pages from `PageSeeder`. |
| Bad deploy | Roll back the code, see [Rollback procedures](rollback-procedures.md). |
| Migration damaged data | Restore the pre-deploy dump. A `migrate:rollback` is not a data backup. See [Rollback procedures](rollback-procedures.md). |
| Documents or photos lost on redeploy (disk `private` or `gallery`) | Legacy PDFs return via `hub:import-documents` (runs at boot). Member uploads and new photos cannot be recovered. Public pages hide photos whose files are missing. |
| Wrong rows deleted in the workspace | Several models use soft deletes, but there is **no restore UI**. Recovery is by database (set `deleted_at` to null) after reading the schema in [Tables and columns](../07-database/tables-and-columns.md). |
| Lost Hub Director password | `php artisan hub:create-admin` does nothing while an active Hub Director exists. Use the password reset flow, or have another Hub Director reset the account. |
| Lost `APP_KEY` | Set a new key, re-enter the SMTP password, accept that sessions end. Other data is not encrypted with it, apart from items Laravel encrypts by design. |
| Account or secret leaked | Follow [Security incident response](../08-security/security-incident-response.md). |

## Gaps

| Gap | Impact |
| --- | --- |
| No automated database backup | Everything depends on someone remembering |
| No automatic backup before migrations at deploy | See [Build and release process](build-and-release-process.md) |
| No restore UI for soft deletes | Database access needed |
| No documented retention or purge policy | See [Data retention](../07-database/data-retention.md) |
| Backups of the bucket are not configured | A deleted object is gone unless versioning is on |
| No recovery time or recovery point objective | Define them with the Hub Director before launch |
