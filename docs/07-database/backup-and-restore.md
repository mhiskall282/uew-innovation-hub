# Backup and Restore

> **Audience:** Operators, developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Database overview](database-overview.md), [Data retention](data-retention.md), [Database troubleshooting](database-troubleshooting.md), [Backup and recovery](../09-deployment-and-operations/backup-and-recovery.md), [Rollback procedures](../09-deployment-and-operations/rollback-procedures.md), [Secrets management](../08-security/secrets-management.md)

This page gives working backup and restore commands for the two databases the Hub uses: the SQLite file for local development and PostgreSQL on Render. It also covers the files that live outside the database, the secret that must be backed up separately, and the 30-day expiry of Render's free PostgreSQL database. The application has no built-in backup command and no scheduler, so every backup described here is a manual action.

## What has to be backed up

A working copy of the Hub is made of four things. The database dump covers only the first.

| Item | Where it lives | Backed up by a database dump |
| --- | --- | --- |
| Database rows | SQLite file `database/database.sqlite`, or the PostgreSQL database named by `DB_URL` | Yes |
| Uploaded documents: project reports, publication PDFs, legacy proposals, files attached to applications | The documents disk, `DOCUMENTS_DISK` (default `private`, folder `storage/app/private`; or an S3-compatible bucket with `s3`). Subfolders `reports/`, `publications/`, `proposals/`, `applications/`. | No. The database stores only the path. |
| Gallery photos | The gallery disk, `GALLERY_DISK` (default `gallery`, folder `storage/app/gallery`; or `s3`). Files are under `albums/<album id>/`. | No. `album_photos` stores the disk name and two paths. |
| Secrets | `APP_KEY` and the other environment variables (Render dashboard, or your local `.env`) | No |

> **Warning:** `APP_KEY` encrypts the SMTP password saved in the `settings` table (`MailSettings`) and signs cookies and e-mail links. A database restored without the matching `APP_KEY` loses the saved SMTP password (it cannot be decrypted) and signs everyone out. Store `APP_KEY` in a password manager or secret vault, never in the dump or the repository. Do not paste it in tickets or chat.

Rows that point at missing files do not break the site. `AlbumPhoto::isAvailable()` and the API v2 gallery endpoints skip photos whose files are gone, and document downloads return 404. The data is still lost.

## Render free plan limits

[`render.yaml`](../../render.yaml) states the limits of the free setup:

- Free Render PostgreSQL databases **expire after 30 days**. For anything beyond a demo, upgrade the database plan or point `DB_URL` at another PostgreSQL service.
- The free web service has **no persistent disk**. Uploaded PDFs are lost on redeploy unless `DOCUMENTS_DISK=s3` with an S3-compatible bucket.
- The comment mentions documents only. The gallery disk (`GALLERY_DISK`, default `gallery`) is also a folder inside the container, so gallery photos are lost on redeploy in the same way unless `GALLERY_DISK=s3` is set. This follows from `config/filesystems.php`, where `gallery` is a local disk under `storage/app/gallery`.

> **Requires confirmation:** whether the free Render database includes automatic backups or point-in-time recovery. The repository does not say. Assume it does not and take your own dumps.

Take a dump well before day 30, and put a reminder in a calendar. If the database expires with no dump, the data cannot be recovered from this repository. `SEED_DEMO_DATA=true` only recreates the demo records ([seed-data.md](seed-data.md)).

## Recommended, not implemented

| Gap | Suggestion |
| --- | --- |
| No scheduled database backup | Run `pg_dump` from a scheduled job outside the app (a GitHub Actions schedule, a cron job on another host, or the hosting provider's backup feature) and upload the dump to storage you control. |
| No backup of uploaded files | Use S3 versioning, or copy the bucket on a schedule. |
| No restore rehearsal | Restore the latest dump into a scratch database once a quarter and run the checks below. |

## SQLite (local development)

`config/database.php` uses `database/database.sqlite` unless `DB_DATABASE` says otherwise. The default journal mode is the SQLite default (a rollback journal file `database.sqlite-journal` during writes), not WAL, so a backup is a copy of one file.

### Back up

Stop anything that writes first: `php artisan serve`, `composer dev`, `php artisan queue:work`, `php artisan queue:listen`. Copying while a write is in progress can produce a corrupt copy.

PowerShell:

```powershell
New-Item -ItemType Directory -Force backups | Out-Null
Copy-Item database\database.sqlite ("backups\database-{0}.sqlite" -f (Get-Date -Format 'yyyyMMdd-HHmmss'))
```

bash:

```bash
mkdir -p backups
cp database/database.sqlite "backups/database-$(date +%Y%m%d-%H%M%S).sqlite"
```

`database/.gitignore` ignores `*.sqlite*`, so these files are not committed. Keep the `backups/` folder out of the repository as well, and store copies elsewhere if they matter.

### Restore

> **Warning:** Restoring replaces the current database. Copy the current file aside first so you can undo the restore.

PowerShell:

```powershell
Copy-Item database\database.sqlite database\database.before-restore.sqlite
Copy-Item backups\database-YYYYMMDD-HHMMSS.sqlite database\database.sqlite -Force
Remove-Item database\database.sqlite-journal -ErrorAction SilentlyContinue
php artisan migrate:status
```

bash:

```bash
cp database/database.sqlite database/database.before-restore.sqlite
cp backups/database-YYYYMMDD-HHMMSS.sqlite database/database.sqlite
rm -f database/database.sqlite-journal
php artisan migrate:status
```

If the backup is older than the current code, run `php artisan migrate` to bring it up to date. See [migrations.md](migrations.md).

## PostgreSQL (Render and CI)

Production uses `DB_CONNECTION=pgsql` with `DB_URL` set from the Render database `uew-innovation-hub-db`. The tools below (`pg_dump`, `pg_restore`, `psql`) are the standard PostgreSQL client tools. Install a client of the same major version as the server or newer. Render's server version is not stated in this repository (CI uses PostgreSQL 16). > **Requires confirmation:** the Render server version.

Laravel accepts `pgsql://` URLs, but `pg_dump`, `pg_restore` and `psql` expect `postgresql://` or `postgres://`. Replace the scheme when you reuse `DB_URL`. Use the external connection string from the Render dashboard when connecting from your machine. Do not paste real connection strings into scripts that are committed, shared or logged.

Placeholders used below:

| Placeholder | Meaning |
| --- | --- |
| `<SOURCE_DB_URL>` | `postgresql://USER:PASSWORD@HOST:5432/DBNAME` for the database you dump from |
| `<TARGET_DB_URL>` | The same format for the database you restore into |

### Back up

Custom format is compressed, and `pg_restore` can restore parts of it:

PowerShell:

```powershell
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
pg_dump --format=custom --no-owner --no-privileges --file "backups\uew-hub-$stamp.dump" "<SOURCE_DB_URL>"
```

bash:

```bash
pg_dump --format=custom --no-owner --no-privileges --file "backups/uew-hub-$(date +%Y%m%d-%H%M%S).dump" "<SOURCE_DB_URL>"
```

A plain SQL dump is readable and restores with `psql`:

```bash
pg_dump --format=plain --no-owner --no-privileges --file backups/uew-hub.sql "<SOURCE_DB_URL>"
```

`pg_dump` does not block the running site. A dump taken while users are writing is still consistent, because it runs in one transaction.

The dump contains personal data (names, e-mail addresses, dates of birth, regions, disability status) and password hashes. Treat the file as confidential: encrypt it, restrict who can read it, and delete old copies. See [data-protection.md](../08-security/data-protection.md).

### Restore

> **Warning:** Restoring into a database that already holds data can overwrite or duplicate rows. Restore into an **empty** database whenever you can, and rehearse on a scratch database first. `--clean` drops objects before it recreates them, so never point it at the wrong database. Check the host and database name in `<TARGET_DB_URL>` twice.

Restore a custom-format dump into an empty database:

```bash
pg_restore --no-owner --no-privileges --exit-on-error --dbname "<TARGET_DB_URL>" backups/uew-hub-YYYYMMDD-HHMMSS.dump
```

Restore over an existing database (drops and recreates the objects in the dump):

```bash
pg_restore --clean --if-exists --no-owner --no-privileges --dbname "<TARGET_DB_URL>" backups/uew-hub-YYYYMMDD-HHMMSS.dump
```

Restore a plain SQL dump:

```bash
psql --single-transaction --set ON_ERROR_STOP=1 --dbname "<TARGET_DB_URL>" --file backups/uew-hub.sql
```

The PowerShell forms are the same. Only the quoting of the connection string differs, so keep it in double quotes.

### Move to a new database after the 30-day expiry

1. Take a dump of the old database before it expires (above).
2. Create the new PostgreSQL database (a new Render database, or another provider).
3. Restore the dump into the empty database.
4. Point `DB_URL` at the new database. In `render.yaml` the value comes from `fromDatabase: uew-innovation-hub-db`. If the new database is outside Render, set `DB_URL` in the Render dashboard instead.
5. Redeploy. The entrypoint runs `php artisan migrate --force`, which applies any migration newer than the dump.
6. Restore the uploaded files ([below](#restore-uploaded-files)) and check the results.

## Back up and restore uploaded files

The local folders are plain directories. Stop the app, or accept that a file uploaded during the copy may be missing.

PowerShell:

```powershell
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
Compress-Archive -Path storage\app\private, storage\app\gallery -DestinationPath "backups\uew-hub-files-$stamp.zip"
```

bash:

```bash
tar czf "backups/uew-hub-files-$(date +%Y%m%d-%H%M%S).tar.gz" storage/app/private storage/app/gallery
```

Restore by extracting the archive back over `storage/app/` and making sure the web server user can read it. In the Docker image the entrypoint runs `chown -R www-data:www-data storage`.

For S3-compatible storage (`DOCUMENTS_DISK=s3` or `GALLERY_DISK=s3`), back up the bucket with your provider's tools or versioning. The repository does not configure it.

`php artisan hub:import-documents` is **not** a restore tool for uploads. It only copies the legacy PDFs from `reports/` and `publications/` in the project root onto the documents disk.

### Restore uploaded files

Database rows and files must come from the same moment. If they do not match, run the checks below and expect broken downloads and missing photos for records created in between.

## Checks after a restore

```bash
php artisan migrate:status        # every migration should show Ran
php artisan db:show --counts      # row counts per table, compare with the source
php artisan optimize:clear        # clear cached config, routes, views and the cache store
```

Then open `/up` (the health check) and sign in as a Hub Director. Check the project list, a document download, a gallery album and the audit log (`activity_logs` should end near the backup time). Send a test e-mail from the site settings page to confirm the SMTP password can be decrypted.

`php artisan db:show` and `optimize:clear` are Laravel built-ins. `optimize:clear` also empties the cache store, which is the `cache` table in this project. The `cache` table is part of a dump, so without clearing it, cached metrics from the backup time can show for up to `METRICS_CACHE_TTL` seconds (default 300).

## Quick reference

| Task | SQLite | PostgreSQL |
| --- | --- | --- |
| Back up | Stop the app, copy `database/database.sqlite` | `pg_dump --format=custom ...` |
| Restore | Stop the app, copy the file back, remove `database.sqlite-journal` | `pg_restore --dbname ... file.dump` into an empty database |
| Verify | `php artisan migrate:status` | `php artisan db:show --counts` |
| Files | Archive `storage/app/private` and `storage/app/gallery` | Same, or the S3 bucket |
