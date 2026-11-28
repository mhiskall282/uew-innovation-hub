# Development Environment Setup

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [installation-guide](installation-guide.md), [prerequisites](prerequisites.md), [common-development-issues](common-development-issues.md), `../09-deployment-and-operations/environment-configuration.md`

How to run the application day to day: which processes to start, which `.env` values matter for development, how mail and queues behave locally, and editor settings. Install first with [installation-guide](installation-guide.md).

## Processes

| Process | Command | Purpose |
| --- | --- | --- |
| Web server | `php artisan serve` | Serves `public/` on `http://127.0.0.1:8000` |
| Vite dev server | `npm run dev` | Hot reload for `resources/css/app.css` and `resources/js/app.js` (writes `public/hot`) |
| Queue worker | `php artisan queue:listen --tries=1 --timeout=0` | Sends queued e-mail. `.env.example` sets `QUEUE_CONNECTION=database` |
| Log tail | `php artisan pail --timeout=0` | Live log view |

`composer dev` starts all four with `npx concurrently` and the `--kill-others` flag, so when one process exits, the rest stop.

```powershell
composer dev
```

> **Warning:** `php artisan pail` needs the `pcntl` PHP extension, which native Windows builds of PHP do not provide. If `composer dev` stops straight away on Windows, start the three useful processes in separate terminals instead (or use WSL):
>
> ```powershell
> php artisan serve
> php artisan queue:listen --tries=1 --timeout=0
> npm run dev
> ```
>
> **Requires confirmation:** this has not been run on every Windows setup. If `composer dev` works for you, ignore this note.

Without the queue worker, queued mail waits in the `jobs` table. In tests the queue is `sync`, so nothing waits.

If you do not run `npm run dev`, you must have built assets once (`npm run build`). The layouts use the Vite manifest in `public/build`. A missing manifest produces a "Vite manifest not found" error. See [common-development-issues](common-development-issues.md).

## Environment values that matter locally

Copy `.env.example` to `.env`. Never commit `.env`. The full list is in `../09-deployment-and-operations/environment-configuration.md`.

| Variable | Local value | Notes |
| --- | --- | --- |
| `APP_ENV` | `local` | `production` turns off lazy-loading checks, forces HTTPS when `APP_URL` is https and tightens password rules |
| `APP_DEBUG` | `true` | Shows error pages |
| `APP_URL` | `http://localhost:8000` | Used in e-mail links and signed URLs |
| `DB_CONNECTION` | `sqlite` | File `database/database.sqlite` |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | `database` | Tables `sessions`, `cache`, `jobs` are created by migrations |
| `MAIL_MAILER` | `log` | E-mails are written to `storage/logs/laravel.log` |
| `MAIL_ALWAYS_TO` | empty | Set to your own inbox to redirect every e-mail when you use real SMTP in development. Ignored in production |
| `DOCUMENTS_DISK` | `private` | Local disk `storage/app/private`. `s3` is for hosted sites |
| `SEED_PASSWORD` | `password` | Password given to demo accounts by the seeder |
| `BLOCK_DEFAULT_PASSWORDS` | `false` | Keep false locally, or the seeded password `password` is refused at sign-in |
| `SEARCH_INDEXING` | `false` | Controls whether the site invites search engines |

`GALLERY_DISK` is read in `config/hub.php` (default `gallery`) but is missing from `.env.example`. The default is fine locally. Photos are stored under `storage/app/gallery`.

## Local mail

With `MAIL_MAILER=log`, open `storage/logs/laravel.log` and search for the message subject. Verification codes, password reset codes and notifications appear there as raw message text. For a real inbox, use the Gmail SMTP block in `.env.example` with an App Password and set `MAIL_ALWAYS_TO` so demo members never receive test mail.

## Switching the database for a one-off check

```powershell
# PowerShell: use a throwaway SQLite file
$env:DB_DATABASE = (Resolve-Path database).Path + '\scratch.sqlite'
New-Item $env:DB_DATABASE -ItemType File -Force | Out-Null
php artisan migrate --seed
Remove-Item Env:DB_DATABASE
```

```bash
DB_DATABASE="$PWD/database/scratch.sqlite" bash -c 'touch "$DB_DATABASE" && php artisan migrate --seed'
```

> **Warning:** A `DB_DATABASE` value left in your shell session overrides `phpunit.xml`, and `RefreshDatabase` then wipes that database when you run tests. Always `Remove-Item Env:DB_DATABASE` (PowerShell) or `unset DB_DATABASE` (bash) afterwards. See [testing-guide](testing-guide.md).

## Useful commands

| Task | Command |
| --- | --- |
| Reset the local database and reseed | `php artisan migrate:fresh --seed` (destroys local data) |
| List routes | `php artisan route:list` (add `--name=workspace` to filter) |
| Open a REPL | `php artisan tinker` |
| Clear caches | `php artisan optimize:clear` |
| Show app facts | `php artisan about` |
| List Hub commands | `php artisan list hub` |

The three Hub commands are `hub:create-admin`, `hub:import-documents` and `hub:secure-demo-accounts`.

## Editor settings

`.editorconfig` applies to all files: UTF-8, LF line endings, 4-space indent, final newline, trailing whitespace trimmed (except in `*.md`). YAML files use 2 spaces. `.gitattributes` sets `* text=auto eol=lf`. Configure your editor to honour EditorConfig, and do not save files with a BOM or CRLF.

Format PHP with Pint (`pint.json`, preset `laravel`, excludes `legacy` and `api`):

```powershell
vendor\bin\pint            # fix files
vendor\bin\pint --test     # check only, as CI does
```

```bash
vendor/bin/pint
vendor/bin/pint --test
```

## Browser tests

Playwright browsers are not installed by `npm install`. See [testing-guide](testing-guide.md) for `npx playwright install` and the run commands.
