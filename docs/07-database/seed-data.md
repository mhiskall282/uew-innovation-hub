# Seed Data

> **Audience:** Developers, operators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Migrations](migrations.md), [Tables and columns](tables-and-columns.md), [Backup and restore](backup-and-restore.md), [Environment configuration](../09-deployment-and-operations/environment-configuration.md), [Secrets management](../08-security/secrets-management.md)

This page describes what each seeder and factory creates, how `DatabaseSeeder` decides what to run, how the Docker entrypoint uses seeding on every boot, and how the first administrator and the default CMS pages are created. Demo account e-mail addresses are in [`DemoContentSeeder.php`](../../database/seeders/DemoContentSeeder.php) and [`DemoProgrammesSeeder.php`](../../database/seeders/DemoProgrammesSeeder.php). Every demo account gets the same password, taken from `SEED_PASSWORD`. This page does not repeat the account list.

## Seeders at a glance

| Seeder | Creates | Safe in production | Idempotent |
| --- | --- | --- | --- |
| [`DatabaseSeeder`](../../database/seeders/DatabaseSeeder.php) | Calls the others. Decides what runs. | Yes, it guards itself. | Yes |
| [`PageSeeder`](../../database/seeders/PageSeeder.php) | Four CMS pages: `about`, `faq`, `privacy`, `terms`. | Yes | Yes, never overwrites edits |
| [`DemoContentSeeder`](../../database/seeders/DemoContentSeeder.php) | Five staff accounts (one per staff role), 7 organisations, 7 projects, 3 publications, 4 posts, 3 legacy grant applications. | Only on a demo site | Mostly: it overwrites records it owns (see below) |
| [`DemoProgrammesSeeder`](../../database/seeders/DemoProgrammesSeeder.php) | 5 programmes, 5 member accounts, 6 applications, 2 badges, XP, 2 messages, 1 progress report. | Only on a demo site | Mostly |

Not seeded by any seeder: gallery albums and photos, settings (defaults live in `SettingsService` and are used until an administrator saves), e-mail templates (defaults live in code), broadcasts, contact messages, notifications and audit log entries (the audit log fills as the seeders and commands run).

## How `DatabaseSeeder` decides

`php artisan db:seed` runs `DatabaseSeeder::run`. The checks, in order:

1. Run `PageSeeder`. This always happens.
2. If `APP_ENV=production` and `SEED_DEMO_DATA` is not true, print "Skipping demo data in production" and stop.
3. If `APP_ENV=production` and `SEED_PASSWORD` is not strong, print an error and stop. Strong means 12 or more characters with upper case, lower case and a number, and not one of the weak passwords in `SecureDemoAccounts::WEAK` (`password`, `secret`, `12345678`, `password123`, `admin`, `changeme`).
4. If `APP_ENV=production` and the demo Hub Director account already exists, print "Demo data already present" and stop. The check looks for the account `admin@uew.edu.gh`, so a re-run on a hosted demo site does not reset testers' passwords or decisions.
5. Run `DemoContentSeeder`, then `DemoProgrammesSeeder`.

| `APP_ENV` | `SEED_DEMO_DATA` | `SEED_PASSWORD` | Result |
| --- | --- | --- | --- |
| not production (local, testing) | ignored | any, default `password` | Pages and all demo data are created. No strength check. |
| `production` | not true (default) | any | Pages only. |
| `production` | `true` | weak or missing | Pages only, with an error message. |
| `production` | `true` | strong | Pages and demo data on the first run. Later runs print "already present" and change nothing. |

`SEED_DEMO_DATA` is read as `config('hub.seed_demo_data')` (`config/hub.php`) and defaults to `false`. `SEED_PASSWORD` is `config('hub.seed_password')` and defaults to `password`. `.env.example` sets `SEED_PASSWORD=password` and `SEED_DEMO_DATA=false` for local development.

> **Warning:** A hosted site with `SEED_DEMO_DATA=true` gets a demo Hub Director (`super_admin`) account in addition to the account made by `hub:create-admin`. It is protected only by `SEED_PASSWORD`. Use demo data on test sites only, and set `SEED_DEMO_DATA=false` on a fresh database for a real launch, as the comment in [`render.yaml`](../../render.yaml) says.

## What each seeder creates

### `PageSeeder`

Creates four pages with `Page::firstOrCreate(['slug' => ...])`: `about` (sort 10), `faq` (20), `privacy` (30) and `terms` (40). All are published and shown in the footer. The privacy page text interpolates `config('hub.contact_email')`.

Because the lookup is by slug and the rows are only created when missing:

- An administrator's edits to the title, summary or body are never overwritten.
- A page the administrator **deletes** is created again on the next run. The entrypoint runs this seeder on every container boot, so a deleted default page returns after the next deploy or restart. To hide a page, unpublish it instead of deleting it. This is a gap: there is no way to delete a default page permanently.

### `DemoContentSeeder`

| Data | Detail |
| --- | --- |
| Staff accounts | One each for Hub Director, Programme Officer, Reviewer, Communications Intern and Read-only Staff. Created with `forceFill` because `role` and `is_active` are not mass assignable. `email_verified_at` is set to now if empty. The password comes from `SEED_PASSWORD`. |
| Organisations | 7 (UNESCO, Ghana Education Service, Mastercard Foundation, British Council, UNICEF Ghana, MESTI, the University of Education, Winneba), created with `firstOrCreate`. |
| Projects | 7, matched by title with `updateOrCreate`. Six are public. One internal pilot has `is_public = false`. Each has partner organisations attached with `sync`, and `legacy_id` values (19, 23 to 26, 28) that mirror the old system. `report_file_path` points at PDFs under `storage/app/private/reports/`. |
| Publications | 3 (a newsletter, a research paper, a policy brief), matched by title. The newsletter points at a PDF in `storage/app/private/publications/`. |
| Posts | 4 (published news, blog and announcement posts, and one news draft with `published_at = NULL`), matched by title. |
| Legacy grant applications | 3 with fixed `reference_no` values, matched by reference, in statuses `approved`, `under_review` and `submitted`. |

### `DemoProgrammesSeeder`

| Data | Detail |
| --- | --- |
| Programmes | 5, matched by title, all set to `published`: a fellowship (capacity 200), a grant, a one-day event (capacity 60), a grant that opens in two weeks, and a competition that has already closed. Opening and closing dates are computed from `now()` at seeding time, so a database seeded months ago shows stale windows. |
| Members | 5: one fully onboarded demo member, one who still needs onboarding, and three extra applicants. Created with `forceFill`; the role is `member`. |
| Applications | 6, one per programme and member pair, in a mix of statuses (draft, under review, approved, submitted, shortlisted). Submitted ones get an `applicant_snapshot`. |
| Engagement | Two badges and their XP for the demo member (`firstOrNew` on user, programme and type), `public_profile` switched on for three members, two application messages and one progress report on the approved application. The messages and report are created only if the application has no messages yet. |

Re-running the demo seeders outside production resets the fields they manage on these records, including staff and member passwords back to `SEED_PASSWORD`. Do not run `php artisan db:seed` against a local database whose demo records you have edited and want to keep.

## Factories

[`database/factories/`](../../database/factories) is used by the PHPUnit tests and is not run by any seeder. Each factory builds one model with fake data and a few named states.

| Factory | States and notes |
| --- | --- |
| `UserFactory` | Default role `viewer`, active, verified. States: `role(Role)`, `admin()`, `inactive()`, `member()`, `unonboarded()`. The default password hash is for the word `password`, used only by tests. |
| `ProgramFactory` | `published()`, `draft()`, `closed()`, `upcoming()`, `withFields(array)`. |
| `ProgramApplicationFactory` | `between(Program, User)`, `status(ApplicationStatus)`. |
| `ProjectFactory` | `internal()`, `status(ProjectStatus)`. |
| `PublicationFactory`, `OrganizationFactory` | Defaults only. |
| `PostFactory` | `draft()`, `scheduled()`. |
| `GrantApplicationFactory` | `status(GrantStatus)`. |

There are no factories for albums, photos, broadcasts, contact messages, progress reports, achievements or pages. Tests create those directly.

## Artisan commands that create data

| Command | What it does | Run by the entrypoint |
| --- | --- | --- |
| `php artisan hub:create-admin` | Creates the first Hub Director if no active one exists. Reads `ADMIN_EMAIL`, `ADMIN_NAME`, `ADMIN_PASSWORD` or the `--email`, `--name`, `--password` options. Generates and prints a 20-character password once if none is given. Does nothing if an active Hub Director exists. | Yes, when `ADMIN_EMAIL` is set |
| `php artisan hub:secure-demo-accounts` | Finds accounts whose password matches a weak default and replaces it with `SEED_PASSWORD`. `--dry-run` only reports. Fails if `SEED_PASSWORD` is weak. Writes `user.password_rotated` audit entries. | Yes, on every boot |
| `php artisan hub:import-documents` | Copies PDFs from `reports/` and `publications/` in the project root (or `--source=`) onto the documents disk, skipping files already there. | Yes, on every boot |

`hub:secure-demo-accounts` checks every account, not only the demo ones. Any real account that uses one of the six weak passwords is reset to `SEED_PASSWORD` on the next boot.

## Container boot order

[`docker/entrypoint.sh`](../../docker/entrypoint.sh) runs these steps on every start, and each is safe to repeat:

1. `php artisan optimize`.
2. `php artisan migrate --force` (skipped when `RUN_MIGRATIONS=false`).
3. `php artisan hub:create-admin` (when `ADMIN_EMAIL` is set).
4. `php artisan db:seed --class=PageSeeder --force`.
5. `php artisan db:seed --force` (only when `SEED_DEMO_DATA=true`).
6. `php artisan hub:secure-demo-accounts`.
7. `php artisan hub:import-documents`.
8. The queue worker is started in the background, then Apache.

## Commands for developers

PowerShell and bash are the same for these.

```bash
# Seed a fresh local database: pages and demo data (APP_ENV=local)
php artisan migrate
php artisan db:seed

# Pages only
php artisan db:seed --class=PageSeeder

# Check which accounts use a weak password, without changing anything
php artisan hub:secure-demo-accounts --dry-run
```

> **Warning:** `php artisan migrate:fresh --seed` drops every table before seeding. It is fine for a throwaway local database and destroys data anywhere else. On the local SQLite file, copy `database/database.sqlite` first ([backup-and-restore.md](backup-and-restore.md)).

## Findings

| Finding | Where |
| --- | --- |
| A deleted default page is recreated on every boot. | `PageSeeder`, `docker/entrypoint.sh` |
| A demo site has a demo `super_admin` account separate from the account made by `hub:create-admin`. | `DemoContentSeeder` |
| `.env.example` ships `SEED_PASSWORD=password`, which is acceptable only for local development and is rejected by the production guard. | `.env.example`, `DatabaseSeeder` |
| Demo programme dates are relative to the seeding time. | `DemoProgrammesSeeder` |
| Re-seeding a non-production database overwrites edits to demo records and resets their passwords. | `DemoContentSeeder`, `DemoProgrammesSeeder` |
| The earlier duplicate guard in `DatabaseSeeder` has been removed (commit `d1296b1`). | `DatabaseSeeder` |
