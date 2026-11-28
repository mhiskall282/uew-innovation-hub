# Common Development Issues

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [installation-guide](installation-guide.md), [debugging-guide](debugging-guide.md), [testing-guide](testing-guide.md), `../11-troubleshooting/installation-problems.md`, `../11-troubleshooting/frontend-problems.md`

Problems that new developers on Windows (PowerShell 5.1) and other platforms hit in this project, each with the cause and the fix. Problems on the hosted site are in `../11-troubleshooting/`.

## Windows and PowerShell 5.1

Windows PowerShell 5.1 is the default shell on Windows 10 and 11 (`$PSVersionTable.PSVersion` shows 5.1). PowerShell 7 does not have the first four problems.

| Problem | Cause | Fix |
| --- | --- | --- |
| `&&` or `||` gives "The token '&&' is not a valid statement separator" | 5.1 has no pipeline chain operators | Use `;` to run commands in order (it continues after a failure), or `if ($?) { ... }`, or run one command per line |
| `$x = $a ? 'b' : 'c'` is a syntax error | 5.1 has no ternary operator (and no `??`) | `if ($a) { 'b' } else { 'c' }` |
| `Set-Content -Encoding utf8` or `Out-File -Encoding utf8` adds a BOM | 5.1 writes UTF-8 with a byte order mark | Write files without BOM: `[IO.File]::WriteAllText($path, $text, (New-Object Text.UTF8Encoding $false))`. A BOM in a `.php` file is sent before `<?php` and breaks headers and sessions; in `.env` it can corrupt the first variable name; in JSON it breaks parsers |
| `echo KEY=value >> .env` produces garbage | `>` and `>>` write UTF-16 in 5.1 | `Add-Content -Path .env -Value 'KEY=value' -Encoding ascii` or edit with an editor |
| `curl -I http://...` prints a security warning or odd output | `curl` is an alias for `Invoke-WebRequest` in 5.1 | Use `curl.exe` (as `scripts/signin-check.ps1` does) or `Invoke-WebRequest` |
| `npm : File ...npm.ps1 cannot be loaded because running scripts is disabled` | Default execution policy blocks `.ps1` shims | `Set-ExecutionPolicy -Scope CurrentUser RemoteSigned`, or call `npm.cmd` and `npx.cmd` |
| Environment variable set for one test sticks around | `$env:NAME = 'x'` lasts for the whole session | `Remove-Item Env:NAME` when done (next section) |
| `php artisan tinker --execute="..."` quoting breaks | PowerShell treats `$` and quotes in double-quoted strings | Use single quotes outside and double quotes inside, or run `php artisan tinker` interactively |
| Git warns "LF will be replaced by CRLF" | `core.autocrlf` is `true` on many Windows installs | Harmless with `.gitattributes` (`eol=lf`). Keep your editor on LF (`.editorconfig`) |

To check what encoding a file has:

```powershell
$bytes = [IO.File]::ReadAllBytes('app\Models\Album.php')[0..2]; ($bytes | ForEach-Object { '{0:X2}' -f $_ }) -join ' '   # EF BB BF means BOM
```

## Tests use the wrong database (or erase mine)

`phpunit.xml` sets `DB_DATABASE=:memory:`, `DB_CONNECTION=sqlite` and `DB_URL=`. PHPUnit does not overwrite an environment variable that already exists in the process. If `DB_DATABASE`, `DB_CONNECTION` or `DB_URL` is set in your shell, the tests use that value, and `RefreshDatabase` drops the tables there. CI depends on this to run against PostgreSQL.

Check and clear:

```powershell
Get-ChildItem Env: | Where-Object Name -Match '^(DB_|APP_ENV|CACHE_STORE|SESSION_DRIVER|QUEUE_CONNECTION|MAIL_)'
Remove-Item Env:DB_DATABASE, Env:DB_URL, Env:DB_CONNECTION -ErrorAction SilentlyContinue
```

```bash
env | grep -E '^(DB_|APP_ENV|CACHE_STORE|SESSION_DRIVER|QUEUE_CONNECTION|MAIL_)'
unset DB_DATABASE DB_URL DB_CONNECTION
```

Your `.env` file does not cause this, because PHPUnit's values win over `.env`. Note that `php artisan test` may print a different environment than `composer test`, which runs `config:clear` first. If config is cached (`bootstrap/cache/config.php`), clear it with `php artisan config:clear`.

## `LazyLoadingViolationException`

`AppServiceProvider::boot()` calls `Model::preventLazyLoading(! $this->app->isProduction())`. Reading a relationship that was not loaded throws "Attempted to lazy load [x] on model [y]" in local and test environments. Fix the query, not the guard:

```php
$albums = Album::query()->with('cover')->withCount('photos')->paginate(18);   // good
$album->load('photos');                                                       // when you already have the model
```

A view that calls `$album->program->title` needs `with('program')` in the controller. Factory-created models in tests are fine until they are re-fetched.

## "Vite manifest not found" or an unstyled page

- No `public/build/manifest.json`: run `npm run build` (or start `npm run dev`).
- Page unstyled and `public/hot` exists but `npm run dev` is not running: delete `public/hot`.
- New Tailwind class does not apply: it must appear in a scanned file (`resources/**/*.blade.php`, `resources/**/*.js`, `app/Enums/*.php`). Rebuild after adding it in a PHP file elsewhere.
- Playwright pages blank: build assets first; the e2e server needs `public/build`.
- Node too old: Vite 7 needs Node 20.19+ or 22.12+ (see [prerequisites](prerequisites.md)).

## Database problems

| Symptom | Fix |
| --- | --- |
| `Database file at path [...] does not exist` | Create it: `New-Item database/database.sqlite -ItemType File` (PowerShell) or `touch database/database.sqlite` |
| `could not find driver` | Enable `pdo_sqlite` (or `pdo_pgsql`) in `php.ini`; check with `php -m` |
| `SQLSTATE ... no such table: sessions` (or `cache`, `jobs`) | Run `php artisan migrate`. `.env.example` uses database-backed sessions, cache and queue |
| `database is locked` on SQLite | Close DB browsers and stop extra workers (`queue:listen`) and try again |
| Passes on SQLite, fails in CI on PostgreSQL | Run the suite against PostgreSQL ([testing-guide](testing-guide.md)). Common causes: non-grouped columns in aggregate queries, `LIKE` case sensitivity (use `Searchable`), type strictness |

## Sessions, CSRF and URLs

- 419 after sign-in or form post: cookies are tied to the host. Use one host consistently (`localhost` or `127.0.0.1`). `.env.example` sets `APP_URL=http://localhost:8000`, so use `http://localhost:8000`.
- Links in e-mails go to the wrong place: `APP_URL` (or `MAIL_ASSET_URL` for images) is wrong.
- Signed links (unsubscribe, verification) fail after you change `APP_URL` or `APP_KEY`: they are signed with the old values. Request a new one.
- Sign-in refused for demo accounts although the password is right: with `BLOCK_DEFAULT_PASSWORDS=true` the well-known default password `password` is refused. Keep it `false` locally, or set a stronger `SEED_PASSWORD` and re-seed.

## Commands that change accounts

`php artisan hub:secure-demo-accounts` replaces default passwords with `SEED_PASSWORD` for every account that matches (add `--dry-run` to only report). `php artisan hub:create-admin` creates a Hub Director if none exists. Both write to the database you are connected to. Check `DB_CONNECTION` and `DB_URL` before running either, in particular if you ever pasted a production `DB_URL` into your `.env`.

> **Warning:** Do not keep production credentials in your local `.env`. Running `migrate:fresh` or `db:seed` against a production URL destroys real data.

## Windows Defender flags PHP temp files

Symptom: Defender reports "Trojan:PHP/WebShell" (or similar) and quarantines files in `%TEMP%` while running tests, and tests then fail with missing files.

Cause: `UploadedFile::fake()` writes its content into a temporary file. A fixture containing PHP code that resembles a web shell (calls to `system`, `eval` or `exec` on request input) is detected as malware. This happened in `tests/Feature/GalleryTest.php`, and commit `9f3bdee` replaced the string with harmless text that still tests rejection of non-image uploads.

Fix and prevention:

- Do not put malicious-looking strings in fixtures. Test rejection with neutral invalid content (plain text with an `.jpg` name, truncated image bytes).
- Do not add Defender exclusions for the repository as a workaround for such fixtures.
- Run `powershell -File scripts/security-scan.ps1`. It searches first-party code and tests for web-shell patterns and lists unexpected executable files under `public/` and `storage/`.

## GD and uploads

- Gallery tests or photo upload fail with `Call to undefined function imagecreatetruecolor()`: enable `extension=gd` in `php.ini`.
- A photo larger than 30 megapixels is refused on purpose (`GalleryService::MAX_MEGAPIXELS`).
- Uploads fail with a PHP error before Laravel validation: raise `upload_max_filesize` and `post_max_size` in your local `php.ini`. The production file `docker/php.ini` sets `max_file_uploads = 5`, while the gallery form allows 10 files per request.

## composer dev stops immediately

The `composer dev` script runs `php artisan pail`, which needs `pcntl`, and `--kill-others` stops the other processes when it exits. Run the processes separately ([development-environment-setup](development-environment-setup.md)).

## Playwright

| Symptom | Fix |
| --- | --- |
| `Executable doesn't exist ... playwright install` | `npx playwright install chromium` (or the browser you run) |
| Port 8010 already in use | Stop the other process, or set `E2E_PORT` to a free port. The config uses `reuseExistingServer: false` |
| `Refusing to run: DB_DATABASE must point at the isolated e2e.sqlite database.` | You started `tests/e2e/serve.mjs` by hand. Run it through `npx playwright test` |
| Browser download blocked | Install Microsoft Edge and use `E2E_CHANNEL=msedge` for the Chromium projects |
| Tests time out waiting for `/up` | Run `php -v`, `php artisan migrate:fresh --seed` against a scratch SQLite file to see the error. The server startup limit is 180 s |
| Everything is slow | PHP's built-in server serves one request at a time, and tests run with one worker by design |
| Firefox and WebKit fail locally on Windows | Run `npm run test:e2e:chromium`. CI runs all engines on Linux |

## Still stuck

1. Read `storage/logs/laravel.log` ([debugging-guide](debugging-guide.md)).
2. Run `php artisan about` and compare PHP and extension versions with [prerequisites](prerequisites.md).
3. Search `docs/audit/` and `../11-troubleshooting/` for the error text.
4. Ask with the exact command, the full error and your PHP, Node and OS versions.
