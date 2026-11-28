# Installation Guide

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [prerequisites](prerequisites.md), [development-environment-setup](development-environment-setup.md), [common-development-issues](common-development-issues.md), `../09-deployment-and-operations/environment-configuration.md`

Step-by-step instructions to clone the repository and get a working local copy with a SQLite database, demo data and compiled assets. For daily development after this, see [development-environment-setup](development-environment-setup.md).

## 1. Clone

```powershell
git clone https://github.com/mhiskall282/uew-innovation-hub.git
cd uew-innovation-hub
```

```bash
git clone https://github.com/mhiskall282/uew-innovation-hub.git
cd uew-innovation-hub
```

## 2. Create the SQLite file

`.env.example` sets `DB_CONNECTION=sqlite` and does not set `DB_DATABASE`, so Laravel uses `database/database.sqlite`. That file is git-ignored and does not exist after a clone. Create it before migrating:

```powershell
New-Item database/database.sqlite -ItemType File -Force | Out-Null
```

```bash
touch database/database.sqlite
```

> **Requires confirmation:** `composer setup` runs `php artisan migrate --force` but does not create the SQLite file. Laravel can offer to create a missing file interactively, but that is not reliable in a non-interactive script. Creating the file first avoids the question.

## 3. Install and configure

Option A, the Composer script. It runs `composer install`, copies `.env.example` to `.env` if `.env` is missing, runs `php artisan key:generate`, runs `php artisan migrate --force`, then `npm install` and `npm run build`:

```powershell
composer setup
```

```bash
composer setup
```

Option B, the same steps by hand:

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
php artisan migrate
npm install
npm run build
```

```bash
composer install
[ -f .env ] || cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

CI uses `npm ci` (exact lockfile versions) instead of `npm install`. Use `npm ci` if you want to match CI.

## 4. Seed demo data (optional, local only)

`.env.example` has `SEED_PASSWORD=password` and `SEED_DEMO_DATA=false`. In the `local` environment the seeder runs demo data regardless of `SEED_DEMO_DATA` (that flag only matters when `APP_ENV=production`; see `database/seeders/DatabaseSeeder.php`).

```powershell
php artisan db:seed
```

`db:seed` runs `PageSeeder`, then `DemoContentSeeder` (one demo account per role, sample projects, publications, news) and `DemoProgrammesSeeder`. The account list is in `database/seeders/DemoContentSeeder.php`. The password for those accounts is the value of `SEED_PASSWORD` in your `.env`.

> **Warning:** Never run `db:seed` against a database that holds real data, and never set `SEED_DEMO_DATA=true` on a real site.

To create a Hub Director without demo data:

```powershell
php artisan hub:create-admin --email=you@example.com --name="Your Name"
```

If you omit `--password`, a strong password is generated and printed once. The command does nothing if an active Hub Director already exists.

## 5. Optional: import the legacy PDFs

The repository root has `reports/` and `publications/` folders with PDFs from the old system. Demo and legacy rows refer to them. Copy them onto the documents disk (`storage/app/private` by default):

```powershell
php artisan hub:import-documents
```

The command is idempotent and skips files that already exist.

## 6. Run the app

```powershell
php artisan serve        # http://127.0.0.1:8000
npm run dev              # Vite dev server with hot reload (second terminal)
```

Or use `composer dev`, which starts the server, queue listener, log tail (`php artisan pail`) and Vite together. See [development-environment-setup](development-environment-setup.md) for a caveat about `pail` on Windows.

Open `http://127.0.0.1:8000`. The health endpoint is `/up`. Staff sign-in is at `/sign-in`.

> `.env.example` sets `APP_URL=http://localhost:8000`. If you open the site at `127.0.0.1`, links in e-mails and signed URLs use `localhost`. Use `http://localhost:8000` in the browser, or change `APP_URL` in your `.env`.

## 7. Verify

```powershell
php artisan --version     # Laravel Framework 12.x
php artisan route:list    # 192 routes at the time of writing
php artisan test          # full PHPUnit suite
```

If a step fails, see [common-development-issues](common-development-issues.md).

## Using PostgreSQL locally (optional)

Set these in `.env` (see the commented examples in `.env.example`):

```dotenv
DB_CONNECTION=pgsql
DB_URL=postgresql://user:password@127.0.0.1:5432/uew_innovation_hub
```

Then run `php artisan migrate`. Do this only if you need to reproduce a PostgreSQL-only problem. CI covers PostgreSQL for you.
