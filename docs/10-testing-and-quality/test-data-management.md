# Test Data Management

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [testing-strategy](testing-strategy.md), [integration-testing](integration-testing.md), [end-to-end-testing](end-to-end-testing.md), [seed data](../07-database/seed-data.md), [testing guide (commands)](../05-developer-guide/testing-guide.md)

This page explains where test data comes from: factories and their states, the base test class helpers, seeders, how `RefreshDatabase` isolates tests, the isolated e2e SQLite database, and which fakes the tests use. All data is synthetic. No test uses a copy of live data.

## Sources of data

| Source | Used by | Persistence |
| --- | --- | --- |
| Factories (`../../database/factories`) | PHPUnit feature tests | Created per test, rolled back by `RefreshDatabase` |
| `Tests\TestCase` helpers (`../../tests/TestCase.php`) | PHPUnit feature tests | Same |
| Seeders (`../../database/seeders`) | `RouteSmokeTest`, Playwright, local development | `RouteSmokeTest`: per test. Playwright: `database/e2e.sqlite`, rebuilt each run |
| Inline fixtures in tests (generated JPEGs, minimal PDFs) | `GalleryTest`, `ApiV2Test`, upload tests | Temporary files, removed after the test |

## `RefreshDatabase`

Every feature test class uses `Illuminate\Foundation\Testing\RefreshDatabase`. With the SQLite in-memory database from `phpunit.xml`, migrations run once per process and each test runs inside a transaction that rolls back. Tests do not share rows. The `Unit` suite does not use the database.

> **Warning:** PHPUnit does not override environment variables that are already set. If `DB_DATABASE` or `DB_URL` is set in your shell, `RefreshDatabase` runs against that database and wipes it. Clear them first (`Remove-Item Env:DB_DATABASE, Env:DB_URL`). CI relies on this behaviour to run the same tests on a throwaway PostgreSQL 16 service. Details: [testing guide](../05-developer-guide/testing-guide.md).

Settings that make tests fast and deterministic (`../../phpunit.xml`): `BCRYPT_ROUNDS=4`, `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`.

## Factories

All factories sit in `database/factories`. There are eight.

| Factory | States and helpers | Notes |
| --- | --- | --- |
| `UserFactory` | `role(Role)`, `admin()` (Hub Director), `inactive()`, `member()`, `unonboarded()` | Default role is Viewer, active, e-mail verified, with a weak default password. `member()` fills the profile (phone, affiliation, institution, gender, date of birth, region, education level, interests), sets `marketing_opt_in` true and `onboarded_at`. `unonboarded()` clears `onboarded_at` |
| `ProgramFactory` | `published()`, `draft()`, `closed()`, `upcoming()`, `withFields(array)` | `configure()` sets defaults. `withFields` supplies the application form schema |
| `ProgramApplicationFactory` | `between(Program, User)`, `status(ApplicationStatus)` | With no arguments it creates its own programme and member. A `draft` with no `submitted_at` becomes `submitted`. `status()` sets `submitted_at` to yesterday except for drafts |
| `ProjectFactory` | `internal()`, `status(ProjectStatus)` | Default is a public project. Use `internal()` to prove that hidden records stay hidden |
| `PostFactory` | `draft()`, `scheduled()` | Default is published |
| `PublicationFactory` | none | |
| `OrganizationFactory` | none | Partners. Tests set `contact_email` to a recognisable address to prove it never leaks |
| `GrantApplicationFactory` | `status(GrantStatus)` | The legacy grant application table |

There are no factories for `Album`, `AlbumPhoto`, `Broadcast`, `ContactMessage`, `Page`, `ProgressReport` or `Achievement`. Tests create these in three ways:

- `new Album([...])` followed by `forceFill([...])->save()` (`GalleryTest::album()`, `ApiV2Test`).
- `forceCreate([...])` (`RouteSmokeTest::setUp()` for `ContactMessage` and `Broadcast`).
- Real services or HTTP: `GalleryService::addFromPath()`, posting the form, or the demo seeder.

Recommended, not implemented: add factories for `Album`, `AlbumPhoto`, `ProgressReport`, `Broadcast` and `ContactMessage` so tests stop repeating `forceFill`.

### Helpers on `Tests\TestCase`

| Helper | Purpose |
| --- | --- |
| `staff(Role $role = Role::Officer, array $attributes = []): User` | Creates a staff user through `UserFactory::role()` |
| `signInAs(Role $role, array $attributes = []): User` | `staff()` plus `actingAs()` |
| `pdf(string $name = 'document.pdf'): UploadedFile` | A tiny valid PDF made with `UploadedFile::fake()->createWithContent()` |

`GalleryTest` and `ApiV2Test` also define private helpers that build a real JPEG with GD (`imagecreatetruecolor`, `imagejpeg`). `GalleryTest::jpeg()` inserts an EXIF block with an inert marker string, to prove it is removed. The GD extension must be loaded (CI installs `gd`).

### Example

```php
$program = Program::factory()->published()->create(['capacity' => 1]);
$member  = User::factory()->member()->create();

ProgramApplication::factory()
    ->between($program, $member)
    ->status(ApplicationStatus::Submitted)
    ->create();
```

## Seeders

| Seeder | What it creates | Used by |
| --- | --- | --- |
| `DatabaseSeeder` | Always calls `PageSeeder`. Then calls `DemoContentSeeder` and `DemoProgrammesSeeder`, unless it is running in production without `SEED_DEMO_DATA`, or the seed password is weak in production, or the demo admin already exists | `php artisan db:seed`, `migrate:fresh --seed`, `RouteSmokeTest`, Playwright |
| `PageSeeder` | The CMS pages (about, FAQ, privacy, terms and others), with `firstOrCreate` on the slug, so it is safe to run twice | Every seed |
| `DemoContentSeeder` | Demo staff accounts, projects, partners (organisations), publications, news posts and legacy grant applications. The password comes from `SEED_PASSWORD` (`config('hub.seed_password')`) | Demo and e2e runs |
| `DemoProgrammesSeeder` | Demo programmes with forms, member accounts, applications, badges and XP, an application conversation and a progress report for the demo member | Demo and e2e runs |

The demo account list is in `../../database/seeders/DemoContentSeeder.php` and `DemoProgrammesSeeder.php`. The password for all of them is the value of `SEED_PASSWORD`. Do not use demo accounts on a site that holds real data. `php artisan hub:secure-demo-accounts` replaces default passwords on an existing database with a strong `SEED_PASSWORD`, and `SEED_DEMO_DATA` and `BLOCK_DEFAULT_PASSWORDS` control demo data and default-password sign-in. Full detail: [seed data](../07-database/seed-data.md).

`RouteSmokeTest` depends on the seeded records by name: it looks up the demo accounts for guest, Hub Director, Officer, Reviewer, Intern, Viewer, Member and a member awaiting onboarding. Renaming a demo account breaks that test.

## The e2e database

| Item | Value |
| --- | --- |
| File | `database/e2e.sqlite` (git-ignored via `/database/e2e.sqlite`) |
| Created by | `tests/e2e/serve.mjs`, when Playwright starts its web server |
| Guard | `serve.mjs` throws unless `DB_DATABASE` ends with `e2e.sqlite`, so it cannot be pointed at the development or live database |
| Steps | Delete the file, create an empty file, run `php artisan migrate:fresh --seed --force`, run `php artisan config:clear`, start `php -S 127.0.0.1:<port>` in `public/` |
| Environment | Set in `playwright.config.ts`: `APP_ENV=local`, `APP_DEBUG=false`, `SESSION_DRIVER=file`, `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, `SEARCH_INDEXING=false`, `SEED_PASSWORD` |
| Lifetime | Rebuilt on every `npx playwright test` run. State from one run never carries into the next |
| State inside a run | Shared by all specs. A test that writes data (the member journey saves a draft application) affects later tests in the same run. Keep writes idempotent |

The development database is `database/database.sqlite` (also git-ignored) and is not used by any test.

## Fakes used by the tests

Found by searching `tests/` for `::fake`, `assertSent`, `assertQueued` and `enableQueryLog`.

| Fake or tool | Where | Why |
| --- | --- | --- |
| `Mail::fake()` | `RouteSmokeTest`, `EmailLinksTest`, `MemberAccountTest`, `EngagementTest`, `AdministrationTest`, `GrantReviewTest`, `PrivilegesTest` | Capture mail. Assertions: `Mail::assertSent(TemplatedMail::class, ...)`, `Mail::assertQueued(TemplatedMail::class, ...)`, `Mail::assertQueued(GrantStatusUpdatedMail::class, ...)`, `Mail::assertNothingSent()` (`MemberAccountTest`) |
| `Storage::fake('gallery')` | `GalleryTest`, `ApiV2Test` | Gallery photos on a temporary disk. Tests check files with `Storage::disk('gallery')->get/delete/allFiles()` |
| `Storage::fake('private')` | `DocumentAccessTest`, `ProjectManagementTest`, `ContentPublishingTest` | Private PDFs (reports, publications, proposals) |
| `UploadedFile::fake()` | `TestCase::pdf()`, `GalleryTest`, `ContentPublishingTest` | Upload bodies |
| `DB::enableQueryLog()` | `ApiV2Test`, `OperationalFixesTest` | Query-count assertions |
| `config([...])` | `SecurityHardeningTest`, `ApiV2Test`, `SecureDemoAccountsTest` | Switch `hub.*` and `api.*` settings for one test |
| `withServerVariables(['REMOTE_ADDR' => ...])` | `SecurityHardeningTest` | Simulate different client IPs |
| `withoutMiddleware(ThrottleRequests::class)` | `PrivilegesTest` | Skip rate limits so permission checks run |

**Not used:** `Queue::fake()`, `Bus::fake()`, `Event::fake()`, `Notification::fake()` and `Http::fake()` appear nowhere in `tests/`. The queue runs synchronously (`QUEUE_CONNECTION=sync`), so jobs such as `SendBroadcast` run inside the request or test. In-app notifications are stored in the database and asserted by reading the database or the notifications page, not through a fake. There are no outbound HTTP calls to fake.

## Rules for test data

1. Create what you assert on. Do not rely on seeded demo data in feature tests, except `RouteSmokeTest`.
2. Use obviously fake values: `example.com`, `example.org`, invented names. Several tests use `secret@example.org` style addresses to prove they never leak.
3. Never copy live data, real e-mail addresses, real phone numbers or real passwords into a test or seeder. Tests that need a password use throwaway values that exist only in the test database.
4. Do not write real attack strings into fixtures ([security-testing](security-testing.md)).
5. A test that uses time should fix it (`Carbon::setTestNow`) or use relative values (`now()->subDays(10)`). Existing tests use relative values.
6. Clean up files you create outside fakes. `ApiV2Test::jpeg()` registers `beforeApplicationDestroyed(fn () => @unlink($path))`.

## Recommended, not implemented

- A documented list of demo accounts and what each demonstrates (names and roles only, no passwords), kept next to the seeders.
- Factories for the models listed above.
- A `--seed` free e2e fixture builder that creates only what the specs need, to make the e2e database independent of the demo seeder.
- `Queue::fake()` tests that assert `SendBroadcast` is dispatched with the right audience, separate from the sync-queue run that sends mail.
