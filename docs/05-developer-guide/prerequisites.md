# Prerequisites

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [installation-guide](installation-guide.md), [development-environment-setup](development-environment-setup.md), [dependency-management](dependency-management.md)

This page lists the software you need before you clone the repository, the PHP extensions the code relies on, and how to check each one.

## Software versions

| Tool | Required | What the project actually uses | Source |
| --- | --- | --- | --- |
| PHP | `^8.2` | 8.4 in CI and in the Docker image | `composer.json` `require.php`; `.github/workflows/ci.yml` (`PHP_VERSION: '8.4'`); `Dockerfile` (`php:8.4-apache`) |
| Composer | 2.x | `composer:2` in the Docker build | `Dockerfile` |
| Node.js | 22 recommended | 22 in CI and in the Docker image | `ci.yml` (`NODE_VERSION: '22'`); `Dockerfile` (`node:22-alpine`) |
| npm | Comes with Node | `npm ci` in CI, lockfile `package-lock.json` | `ci.yml` |
| Git | Any recent version | | |
| Laravel | 12 (12.73.0 locally) | | `composer.json`; `php artisan --version` |

`package.json` has no `engines` field. Vite 7 (`"vite": "^7.0.7"`) needs a recent Node release. Use the Node 22 line to match CI.

The installed Vite (7.3.8, `node_modules/vite/package.json`) declares `"engines": {"node": "^20.19.0 || >=22.12.0"}`. Use Node 22.12 or newer (or 20.19 or newer). Node 22.11 and older 22.x releases are below that line and can fail with a Vite error or an `EBADENGINE` warning from npm. Check with `node -v`.

Optional tools:

| Tool | When you need it |
| --- | --- |
| Docker | To build the production image locally (`Dockerfile`) |
| PostgreSQL 16 | To run the test suite against the production database type, as CI does (`postgres:16`) |
| Playwright browsers | To run the browser tests (installed with `npx playwright install`) |
| Microsoft Edge | Alternative to a downloaded Chromium when you set `E2E_CHANNEL=msedge` |

## PHP extensions

SQLite is enough for local development and for the default test run. PostgreSQL is used in production.

| Extension | Needed for | Evidence |
| --- | --- | --- |
| `pdo_sqlite` | Local database, test suite, Playwright database | `.env.example` (`DB_CONNECTION=sqlite`), `phpunit.xml`, `playwright.config.ts` |
| `pdo_pgsql` | Production, and the `pgsql` CI test run | `Dockerfile`, `ci.yml` |
| `pdo_mysql` | Optional MySQL support | `Dockerfile` installs it; `.env.example` mentions MySQL as an option |
| `gd` | Gallery photo processing: decode, rotate, resize and re-encode as JPEG | `app/Services/GalleryService.php` uses `getimagesize`, `imagecreatefromstring`, `imagecreatetruecolor`, `imagejpeg`. Also used by `barryvdh/laravel-dompdf` for images in PDFs |
| `mbstring` | `mb_strtolower`, `mb_substr` in services and form requests | `app/Services/UserService.php`, `app/Http/Requests/Auth/RegisterRequest.php` |
| `zip` | Composer package extraction | `ci.yml`, `Dockerfile` |
| `intl` | Installed in CI and Docker. The app does not require it: `app/Support/Format.php` avoids it on purpose | `ci.yml`, `Dockerfile`, `app/Support/Format.php` |
| `bcmath`, `opcache` | Installed in the Docker image | `Dockerfile` |
| `fileinfo`, `openssl`, `ctype`, `curl`, `xml`, `dom`, `tokenizer`, `json` | Laravel framework requirements, file MIME checks, SMTP and S3 | Laravel 12 requirements |

Check what you have:

```powershell
# PowerShell
php -v
php -m | Select-String -Pattern 'gd|mbstring|pdo_sqlite|pdo_pgsql|zip|intl|fileinfo|openssl'
composer -V
node -v
npm -v
```

```bash
# bash
php -v
php -m | grep -Ei 'gd|mbstring|pdo_sqlite|pdo_pgsql|zip|intl|fileinfo|openssl'
composer -V && node -v && npm -v
```

On Windows PHP builds, extensions are enabled in `php.ini` by removing the `;` before lines such as `extension=gd`, `extension=pdo_sqlite`, `extension=pdo_pgsql`, `extension=mbstring`, `extension=zip`, `extension=fileinfo` and `extension=openssl`. Run `php --ini` to find the file that PHP loads.

If `gd` is missing, most of the app still works. Gallery upload tests (`tests/Feature/GalleryTest.php`) and photo uploads fail.

## PHP settings

`.user.ini` in the repository root sets `upload_max_filesize = 100M`, `post_max_size = 110M`, `max_execution_time = 300`, `max_input_time = 300` and `memory_limit = 256M`. PHP's built-in server (`php artisan serve`) does not read `.user.ini` files (they apply to CGI/FastCGI SAPIs). The production values are in `docker/php.ini`. For local work, the PHP defaults are enough unless you test large uploads. The application caps uploads itself (`MAX_UPLOAD_KB=20480` for documents; `AlbumController::MAX_FILES = 10` files of `MAX_FILE_KB = 10240` KB per gallery request).

The production image uses `docker/php.ini` instead: `upload_max_filesize = 25M`, `post_max_size = 30M`, `max_file_uploads = 5`, `memory_limit = 256M`. These are lower than what the gallery form allows (10 files, up to 10 MB each), so a large gallery upload can be cut off by PHP before Laravel validates it. This is recorded as a known issue in the final report for this section.

> **Requires confirmation:** whether `.user.ini` has any effect on Render. The image runs Apache with `php:8.4-apache`, which normally uses mod_php, and mod_php ignores `.user.ini`.

## Accounts and access

- A GitHub account with access to `https://github.com/mhiskall282/uew-innovation-hub` to push branches and see CI.
- No third-party API key is needed to develop locally. Mail defaults to `MAIL_MAILER=log`, which writes e-mails to `storage/logs/laravel.log`.
