# Backend Problems

> **Audience:** Developers, operators · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Backend architecture](../04-architecture/backend-architecture.md), [Logging and diagnostics](logging-and-diagnostics.md), [Deployment problems](deployment-problems.md), [System configuration (admin)](../03-administration/system-configuration.md)

Server-side faults: 500 errors, e-mail, the queue, uploads, image and PDF processing, and stale configuration.

## HTTP 500 with no detail

**Symptoms:** A generic "Server Error" page (`resources/views/errors/500.blade.php`).

**Possible causes:** Any uncaught exception. With `APP_DEBUG=false` the page hides it.

**Diagnostic steps:** Read the log first.

```powershell
Get-Content storage\logs\laravel.log -Tail 100
```

On Render read the service **Logs** tab (`LOG_CHANNEL=stderr`, `LOG_LEVEL=warning` in `render.yaml`, so only warnings and errors appear). The first line of each entry names the exception and the file and line.

**Resolution:** Fix the cause named in the log. To see the full page locally set `APP_DEBUG=true` in `.env` and run `php artisan optimize:clear`.

> **Warning:** Never set `APP_DEBUG=true` on a public site. The page then shows environment details.

**Prevention:** Keep `LOG_LEVEL=warning` or lower on production so errors are not hidden, and watch the log after each deploy.

## Changes in `.env` or config are ignored

**Symptoms:** You change an environment variable and nothing happens.

**Cause:** Config is cached. The container runs `php artisan optimize` on boot (`docker/entrypoint.sh`), which caches config, routes, views and events. Locally a previous `php artisan config:cache` or `optimize` keeps old values.

**Resolution:**

```powershell
php artisan optimize:clear
```

On Render, change the variable in the dashboard and let the service redeploy or restart.

**Prevention:** Read variables with `config()` and define them in `config/*.php`. The one exception in this repository is `env('TRUSTED_PROXIES')` in `bootstrap/app.php`. It runs before configuration loads, so it works on Render (real environment variables) but a value that exists only in a local `.env` file is not seen once config is cached. Run `php artisan optimize:clear` locally.

## E-mails only appear in the log

**Symptoms:** Verification codes, reset e-mails and notices never reach inboxes. The log contains the full message text.

**Possible causes:**

1. `MAIL_MAILER=log`. This is the default in `.env.example` and in `render.yaml`.
2. In **Site settings > E-mail delivery** the method is **Log only**.
3. The method is "Use server configuration (.env)" and `.env` says `log`.
4. In local development, `MAIL_ALWAYS_TO` sends every e-mail to one inbox (ignored when `APP_ENV=production`).

**Diagnostic steps:**

1. Open **Site settings > System** and read `Mail transport`.
2. Press **Send test e-mail** on the E-mail delivery tab. The page shows the SMTP error text if it fails.
3. Read the log:

```powershell
Get-Content storage\logs\laravel.log -Tail 60
```

**Resolution:** Choose **SMTP server** in the workspace, or set `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` and `MAIL_FROM_ADDRESS` in the environment and restart. Gmail needs an App Password (the example block in `.env.example`). `MailSettings::save` runs `queue:restart`, so workers pick up the change.

**Prevention:** Configure SMTP before inviting users. The log mailer writes one-time codes into the log, so treat the log as sensitive while it is active.

## SMTP test fails

| Error text contains | Likely cause |
| --- | --- |
| `Connection could not be established` / `timed out` | Wrong host or port, firewall blocks the port |
| `Expected response code "250" but got code "535"` | Wrong user name or password, or an app password is required |
| `certificate` / `SSL` | STARTTLS chosen for port 465, or the reverse. `ssl` sets scheme `smtps`, `tls` sets `smtp` |
| Decrypt-related error, or an empty password | `APP_KEY` changed after the password was saved. Enter it again |

## Queue worker not running

**Symptoms:** Application confirmation and status e-mails, broadcasts and staff notices never go out. Broadcast status stays `sending`. The `jobs` table keeps growing.

**Possible causes:**

1. No worker process. Locally `php artisan serve` does not start one.
2. `RUN_QUEUE_WORKER=false`, or `QUEUE_CONNECTION=sync` (then nothing is queued, mail is sent in the request).
3. The worker crashed. On Render the entrypoint loops the worker with `queue:work --sleep=3 --tries=3 --max-time=3600 --memory=128` and restarts it, but a boot failure stops everything.
4. A job fails every time and sits in `failed_jobs`.
5. Mail settings changed and the worker has old values (`queue:restart` fixes it).

**Diagnostic steps:**

```powershell
php artisan config:show queue.default
php artisan queue:failed
```

Count waiting jobs:

```powershell
php artisan tinker --execute="dump(DB::table('jobs')->count(), DB::table('failed_jobs')->count());"
```

**Commands / Resolution:**

```powershell
php artisan queue:work --tries=3          # run one in the foreground
php artisan queue:retry all               # retry failed jobs
php artisan queue:restart                 # make running workers reload
```

For local development `composer run dev` starts `php artisan queue:listen --tries=1 --timeout=0` with the web server, log viewer and Vite. For a quick local test set `QUEUE_CONNECTION=sync` in `.env`.

**Prevention:** For production, run the worker as its own service and set `RUN_QUEUE_WORKER=false` on web instances (see [deployment guide](../deployment.md)). `SendBroadcast` has `tries = 1`, so a failed broadcast is not retried automatically.

## Upload too large

**Symptoms:** The form returns "The file failed to upload", "The file may not be greater than N kilobytes", a 413, or the whole form comes back empty with no error.

**Limits that apply:**

| Setting | Value | Where |
| --- | --- | --- |
| PDF limit in the app | 20480 KB (`MAX_UPLOAD_KB`) | `config/hub.php`, `.env.example` |
| Gallery photo | 10 MB each, 10 files per request | `AlbumController` |
| `upload_max_filesize` | 25M | `docker/php.ini` |
| `post_max_size` | 30M | `docker/php.ini` |
| `max_file_uploads` | 5 | `docker/php.ini` |
| `.user.ini` | 100M / 110M, `max_execution_time = 300` | `.user.ini` |

`.user.ini` is read by PHP when it runs as CGI or FPM. The Docker image uses Apache with PHP, which uses `docker/php.ini`. **Requires confirmation:** which hosting environment `.user.ini` is intended for.

**Diagnostic steps:** See what PHP actually uses:

```powershell
php -i | Select-String -Pattern "upload_max_filesize|post_max_size|max_file_uploads|memory_limit"
```

`php artisan serve` uses the CLI `php.ini`. Its default `upload_max_filesize` is often 2M, which rejects normal PDFs locally.

**Resolution:** Raise `upload_max_filesize` and `post_max_size` in the `php.ini` that `php --ini` shows (local), or in `docker/php.ini` (deployed, needs a rebuild). Keep `post_max_size` larger than `upload_max_filesize`, and `MAX_UPLOAD_KB` no larger than either.

**Prevention:** Keep these values consistent when changing `MAX_UPLOAD_KB`.

## Photo upload drops files

**Symptoms:** The album form accepts 10 files but only 5 photos are added, or a batch of large phone photos fails outright.

**Cause:** Mismatch found while writing these docs. `AlbumController` accepts up to 10 files of 10 MB, but `docker/php.ini` sets `max_file_uploads = 5` and `post_max_size = 30M`. PHP silently discards files beyond 5 and rejects a request over 30 MB before Laravel runs.

**Workaround:** Upload 3 to 5 photos per batch.

**Fix (code/config change, not done in this documentation task):** Set `max_file_uploads` to at least 10 and `post_max_size` above the largest batch (10 files of 10 MB is about 100 MB), or lower `AlbumController::MAX_FILES` and `MAX_FILE_KB`.

## Gallery upload error "could not be read" or GD errors

**Symptoms:** "One of the photos could not be read. It may be damaged.", or `Call to undefined function imagecreatefromstring()`, or a 500 on upload.

**Possible causes:** The `gd` extension is not enabled (local Windows PHP). The file is damaged. The image is over 30 megapixels (separate message). A WebP or PNG is unsupported by the local GD build.

**Diagnostic steps:**

```powershell
php -m | Select-String -Pattern "^gd$"
php -r "print_r(gd_info());"
```

**Resolution:** Enable `extension=gd` in `php.ini`, restart the terminal. The Docker image builds GD with JPEG and FreeType (`docker-php-ext-configure gd --with-jpeg --with-freetype`). WebP and PNG support must be present in the build. Check `gd_info()` for `WebP Support` and `PNG Support`.

**Prevention:** CI exercises the gallery in `tests/Feature/GalleryTest.php`. Run it locally after a PHP upgrade: `php artisan test --filter=GalleryTest`.

## Photos or documents "missing"

**Symptoms:** Downloads give 404 "The requested document is not available." or gallery photos disappear.

**Cause:** On a host without a persistent disk the files are gone while rows remain. `DocumentStorage::download` aborts with 404, and `AlbumPhoto::isAvailable()` hides the photo.

**Diagnostic steps:**

```powershell
php artisan config:show hub.documents_disk
php artisan config:show hub.gallery_disk
```

**Resolution and prevention:** See [deployment problems](deployment-problems.md#uploaded-files-disappear-after-a-deploy).

## PDF report fails or times out

**Symptoms:** The workspace PDF button gives a 500, a blank download, or "Allowed memory size exhausted". The PDF is built by DomPDF.

**Possible causes:** `memory_limit` (256M in `docker/php.ini`) or `max_execution_time` (60 seconds) is too low for a large "All time" range. A missing font. A 429 if pressed more than 10 times a minute.

**Diagnostic steps:** Read the log for `Allowed memory size`. Try a shorter range or fewer sections.

**Resolution:** Choose a smaller period or fewer sections. For a permanent fix raise `memory_limit` in `docker/php.ini`. The template uses `DejaVu Sans`, which ships with DomPDF.

## Storage permission errors

**Symptoms:** `file_put_contents(...storage/logs/laravel.log): Failed to open stream: Permission denied`, or view compile errors.

**Cause:** The web server user cannot write to `storage` or `bootstrap/cache`. In the container the entrypoint runs `chown -R www-data:www-data storage bootstrap/cache`.

**Resolution (local Linux/macOS):** `chmod -R ug+rwX storage bootstrap/cache`. On Windows check that the folders are not read-only and that antivirus is not locking them. Create missing folders: `New-Item storage\framework\views -ItemType Directory -Force`.

## Sessions or cache fail

**Symptoms:** `Table 'sessions' doesn't exist` / `relation "cache" does not exist`.

**Cause:** `SESSION_DRIVER=database` and `CACHE_STORE=database` are the defaults, so the migrations must have run. See [database problems](database-problems.md#no-such-table-or-relation--does-not-exist).

**Resolution:** `php artisan migrate`. To start without tables locally, set `SESSION_DRIVER=file` and `CACHE_STORE=file` in `.env`.

## Staff notifications not received

**Cause:** Staff must hold the permission (`applications.review` for new applications, `messages.manage` for enquiries) and have the matching box ticked in **Account settings** (`notify_new_grants`, `notify_contact_messages`). The e-mails are queued, so the worker must run. Failures to queue are logged as warnings ("Staff notification could not be queued.").

## Unhandled edge: password-change notices

`UserService::notifyPasswordChange` catches and logs failures ("Password change notice could not be queued.") instead of failing the request. If users say they did not get one, search the log for that text.
