# Installation Problems

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Installation guide](../05-developer-guide/installation-guide.md), [Development environment setup](../05-developer-guide/development-environment-setup.md), [Prerequisites](../05-developer-guide/prerequisites.md), [Database problems](database-problems.md)

Problems that appear when you set up the project on a developer machine. The supported setup path is `composer setup`, which runs `composer install`, copies `.env.example` to `.env` when missing, `php artisan key:generate`, `php artisan migrate --force`, `npm install` and `npm run build`.

## Required environment

| Item | Value | Source |
| --- | --- | --- |
| PHP | `^8.2` in `composer.json`; Docker uses 8.4 | `composer.json`, `Dockerfile` |
| Extensions | `gd`, `pdo_sqlite` or `pdo_pgsql`, `mbstring`, `intl`, `zip`, `bcmath` | `Dockerfile`, `composer.json` |
| Node | 22 | `Dockerfile` |
| Composer | 2 | `Dockerfile` |

## "No application encryption key has been specified"

**Symptoms:** Every page returns a 500 with `MissingAppKeyException`, or `php artisan` commands fail with the same text.

**Possible causes:** `.env` is missing, or `APP_KEY=` is empty.

**Diagnostic steps:**

```powershell
Test-Path .env
php artisan about
```

**Commands / Resolution:**

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
php artisan optimize:clear
```

```bash
[ -f .env ] || cp .env.example .env
php artisan key:generate
php artisan optimize:clear
```

**Prevention:** Use `composer setup`. Never commit `.env`. Do not change `APP_KEY` on a site with data: stored SMTP passwords and encrypted values stop decrypting and sessions are invalidated.

## Container stops with "APP_KEY is not set"

**Symptoms:** Docker or Render deploy fails right after start. The log shows `ERROR: APP_KEY is not set. Generate one with: php artisan key:generate --show`.

**Possible causes:** `docker/entrypoint.sh` exits with status 1 when `APP_KEY` is empty.

**Diagnostic steps:** Check the service environment for `APP_KEY`. In `render.yaml` it is `generateValue: true`.

**Resolution:** Set `APP_KEY`. The entrypoint adds the `base64:` prefix when it is missing, because Render's generated value is raw base64. A key you create yourself can be either form:

```powershell
php artisan key:generate --show
```

**Prevention:** Keep the same key across deploys. Store it in the platform's secrets, not in the repository.

## `composer install` fails

**Symptoms:** "Your PHP version does not satisfy that requirement", "ext-gd is missing", or a ZIP error.

**Possible causes:** Old PHP, an extension disabled in `php.ini`, no internet or a proxy.

**Diagnostic steps:**

```powershell
php -v
php -m | Select-String -Pattern "gd|pdo_sqlite|pdo_pgsql|mbstring|intl|zip|bcmath"
composer check-platform-reqs
php --ini
```

**Resolution:** Enable the missing extension in the `php.ini` that `php --ini` reports (remove the `;` before `extension=gd`, `extension=pdo_sqlite` and so on), open a new terminal and retry. Do not use `--ignore-platform-reqs` on a real install, because the app will then fail at run time (the Docker vendor stage uses it only to build, then the runtime image has the extensions).

**Prevention:** Match the PHP version used in CI (`.github/workflows/ci.yml`, `PHP_VERSION`).

## `npm install` or `npm run build` fails

**Symptoms:** Errors about unsupported Node version, missing `@tailwindcss/vite`, or `ENOENT`.

**Possible causes:** Node older than 22, partial `node_modules`, antivirus locking files.

**Diagnostic steps and resolution:**

```powershell
node -v
Remove-Item -Recurse -Force node_modules
npm ci
npm run build
```

`npm ci` installs exactly what `package-lock.json` says, the same as the Docker build.

**Prevention:** Use `npm ci` after pulling. Commit `package-lock.json` changes with `package.json` changes.

## Pages show "Vite manifest not found"

See [frontend problems](frontend-problems.md#vite-manifest-not-found). In short: run `npm run build`, or `npm run dev` while developing.

## SQLite file is missing

See [database problems](database-problems.md#sqlite-database-file-does-not-exist). In short: `New-Item database\database.sqlite -ItemType File`, then `php artisan migrate`.

## Port 8000 is already in use

**Symptoms:** `php artisan serve` prints "Failed to listen on 127.0.0.1:8000".

**Resolution:** Use another port, and keep `APP_URL` in `.env` in step so signed e-mail links point to the right address:

```powershell
php artisan serve --port=8001
```

Find the process that holds the port with `Get-NetTCPConnection -LocalPort 8000`.

## Signed links and images point to the wrong address

**Symptoms:** Verification links from the local log open another host or port. E-mail images are broken.

**Cause:** `APP_URL` does not match the address you browse. E-mail images load from `MAIL_ASSET_URL`, which defaults to `APP_URL`.

**Resolution:** Set `APP_URL=http://localhost:8000` (or your port), run `php artisan optimize:clear`. When sending real e-mail from a local machine, set `MAIL_ASSET_URL` to the public site address.

## Tests wipe or fail on the wrong database

**Symptoms:** After running tests your development data is gone, or tests fail with "no such table".

**Cause:** A `DB_DATABASE` environment variable in your shell overrides `phpunit.xml` (`:memory:`).

**Resolution:**

```powershell
Remove-Item Env:DB_DATABASE -ErrorAction SilentlyContinue
php artisan test
```

**Prevention:** Never set `DB_DATABASE` globally in your session. See [testing guide](../05-developer-guide/testing-guide.md).

## Antivirus alerts on source files

**Symptoms:** Windows Defender quarantines or alerts on a file in `tests/` or a temporary PHP file.

**Cause found in history:** `UploadedFile::fake()->createWithContent()` writes the file into the system temp folder while a test runs. A test in `tests/Feature/GalleryTest.php` wrote a one-line PHP web-shell string there to check that a fake `.jpg` is rejected, and Defender quarantined it. Commit `9f3bdee` replaced it with a harmless text file named `not-a-photo.jpg`. The test still proves that non-images are rejected.

**Resolution:** Do not write strings that look like web shells or malware into test fixtures, even for negative tests. Use harmless content. `scripts/security-scan.ps1` includes a pattern check for such strings in first-party code.

```powershell
powershell -ExecutionPolicy Bypass -File scripts\security-scan.ps1
```

> **Requires confirmation:** `docs/security.md` has no section about this scan. The details above come from commit `9f3bdee` and `scripts/security-scan.ps1`. Other Defender alerts were not reproduced.

Do not add blanket Defender exclusions for the whole project folder without understanding the risk. If you must, exclude only `node_modules` and `vendor`.

## PowerShell script execution is blocked

**Symptoms:** "running scripts is disabled on this system" when you run a `.ps1` file such as `scripts\security-scan.ps1`.

**Resolution:** Run it for one session only: `powershell -ExecutionPolicy Bypass -File scripts\security-scan.ps1`. This does not change the machine policy.

## `&&` is not a valid statement separator

**Symptoms:** `The token '&&' is not a valid statement separator in this version.`

**Cause:** Windows PowerShell 5.1.

**Resolution:** Run commands on separate lines, or `composer install; if ($?) { npm ci }`. Or install PowerShell 7.
