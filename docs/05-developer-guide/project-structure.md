# Project Structure

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [backend-development](backend-development.md), [frontend-development](frontend-development.md), `../04-architecture/application-architecture.md`, [naming-conventions](naming-conventions.md)

An annotated map of the repository: every top-level directory, and each subdirectory of `app/`.

## Top level

| Path | Purpose |
| --- | --- |
| `app/` | Application code (see below) |
| `bootstrap/` | `app.php` registers routes, middleware aliases (`active`, `verified.email`, `onboarded`), the JSON error envelope for `/api/*`, and trusted proxies |
| `config/` | Laravel config plus `config/hub.php` (domain settings: contact e-mail, documents disk, gallery disk, upload limits, post categories, seed flags) |
| `database/` | `migrations/`, `factories/`, `seeders/`. `database/database.sqlite` (local) and `database/e2e.sqlite` (browser tests) are git-ignored |
| `docker/` | `apache-vhost.conf`, `php.ini` (production PHP settings), `entrypoint.sh` (container start-up: migrate, admin, seeders, queue worker) |
| `docs/` | This documentation. Older files: `api.md`, `architecture.md`, `deployment.md`, `security.md`, `guides/`, `audit/` |
| `public/` | Web root. `index.php`, images in `public/images/`, built assets in `public/build/` (git-ignored) |
| `resources/` | `views/` (Blade), `css/app.css` (Tailwind v4 theme tokens), `js/app.js` and `js/bootstrap.js` (Alpine, fonts, axios) |
| `routes/` | `web.php` (public, auth, portal, workspace), `api.php` (`/api/v1`), `console.php` (only the default `inspire` command; no scheduled tasks) |
| `scripts/` | Developer utilities: `security-scan.ps1` (read-only dependency and pattern checks), `signin-check.ps1` (sign-in check through the real forms with `curl.exe`), `email-flow-test.php` |
| `storage/` | Logs, framework cache, views, uploaded files. `storage/app/private` (documents), `storage/app/gallery` (photos) |
| `tests/` | `Unit/`, `Feature/` (PHPUnit), `e2e/` (Playwright), `TestCase.php` |
| `legacy/` | The old procedural PHP system (`project_mysql`). Kept for reference. Never served. Excluded from Pint |
| `api/` | `api/index.php`, the entry point for the alternative Vercel deployment (`vercel.json`). Excluded from Pint. Production runs on Render, not Vercel |
| `publications/`, `reports/` | Legacy PDFs from the old system. `php artisan hub:import-documents` copies them onto the documents disk so existing database rows keep working. They hold real institutional documents. Do not delete or rename them |
| `.ai/` | Guidance files for AI coding agents and project memory (`state.md`, `changelog.md`, rules, skills, workflows) |
| `.github/workflows/ci.yml` | CI pipeline. See [development-workflow](development-workflow.md) |
| `Dockerfile`, `.dockerignore`, `render.yaml`, `vercel.json` | Deployment. See `../09-deployment-and-operations/` |
| `composer.json`, `package.json`, `vite.config.js`, `phpunit.xml`, `pint.json`, `playwright.config.ts`, `.editorconfig` | Tooling configuration |
| `node_modules/`, `vendor/`, `test-results/`, `playwright-report/` | Generated. Git-ignored |

## app/

| Path | Purpose | Example |
| --- | --- | --- |
| `app/Console/Commands/` | Artisan commands | `CreateAdministrator` (`hub:create-admin`), `ImportLegacyDocuments` (`hub:import-documents`), `SecureDemoAccounts` (`hub:secure-demo-accounts`) |
| `app/Enums/` | Backed enums for statuses, types, roles and permissions (13 files) | `Role`, `Permission`, `ProjectStatus`, `ApplicationStatus` |
| `app/Http/Controllers/Site/` | Public website controllers | |
| `app/Http/Controllers/Auth/` | Sign-in, sign-up, e-mail verification, password reset | |
| `app/Http/Controllers/Portal/` | Member portal controllers | |
| `app/Http/Controllers/Workspace/` | Staff workspace controllers (24 files) | `AlbumController`, `ProjectController` |
| `app/Http/Controllers/Api/V1/` | Read-only JSON API controllers | |
| `app/Http/Controllers/NotificationController.php` | In-app notifications, shared by members and staff | |
| `app/Http/Middleware/` | `EnsureAccountIsActive`, `EnsureEmailIsVerified`, `EnsureOnboarded`, `SecurityHeaders` | |
| `app/Http/Requests/{Auth,Portal,Site,Workspace}/` | FormRequest validation classes | `Workspace/PostRequest.php` |
| `app/Http/Resources/Api/V1/` | JSON resources for the API | |
| `app/Jobs/` | Queued jobs (1 file) | |
| `app/Mail/` | Mailables (4 files) | `TemplatedMail` |
| `app/Models/` | Eloquent models (20) | `User`, `Program`, `ProgramApplication`, `Album` |
| `app/Models/Concerns/` | Model traits | `HasUniqueSlug`, `Searchable`, `FlushesMetrics` |
| `app/Notifications/` | Notification classes (1 file) | |
| `app/Policies/` | Authorization policies (9) | `AlbumPolicy`, `ProjectPolicy` |
| `app/Providers/` | `AppServiceProvider` only. Registers permission gates, rate limiters, password rules, `preventLazyLoading` | |
| `app/Services/` | Business logic (24 classes) | `GalleryService`, `ProjectService`, `AuditLogger`, `DocumentStorage` |
| `app/Support/` | Small helpers and reference data | `Format`, `Photos`, `FormSchema`, `DateRange`, `Demographics`, `EmailTemplates` |

## resources/views/

| Folder | Content |
| --- | --- |
| `site/` | Public website pages |
| `auth/` | Sign-in, sign-up, verification, reset |
| `portal/` | Member portal |
| `workspace/` | Staff workspace, one folder per feature (`albums`, `applications`, `audit`, `broadcasts`, `grants`, `members`, `messages`, `organizations`, `pages`, `posts`, `profile`, `programs`, `progress-reports`, `projects`, `publications`, `reports`, `roles`, `settings`, `templates`, `users`) |
| `components/ui/` | Blade components such as `<x-ui.button>`, `<x-ui.card>`, `<x-ui.field>`, `<x-ui.badge>` |
| `components/layouts/` | Layout components: `site`, `portal`, `workspace`, `auth` |
| `components/emails/` | E-mail building blocks |
| `components/npontu/` | Empty directory at the time of writing |
| `emails/`, `errors/`, `notifications/`, `pagination/`, `partials/` | E-mail templates, error pages, notification views, pagination views, shared partials |
| `reports/_details.blade.php` | The only file in the root-level `reports/` folder. It is included by `portal/reports/show.blade.php` and `workspace/progress-reports/show.blade.php`, so it is in use |
| `admin/`, `dashboard/`, `grants/`, `organizations/`, `posts/`, `projects/`, `publications/` | Empty folders in the working tree. No controller renders `admin.*`, `dashboard.*`, `grants.*`, `organizations.*`, `posts.*`, `projects.*` or `publications.*` views (checked with a search of `app/`). Git does not track empty folders, so they exist only on some machines |

## Where to look for a feature

For a feature such as "gallery albums", follow the chain:

1. Route: `routes/web.php` (search `albums`)
2. Controller: `app/Http/Controllers/Workspace/AlbumController.php`
3. Policy: `app/Policies/AlbumPolicy.php`
4. Service: `app/Services/GalleryService.php`
5. Model: `app/Models/Album.php`, `AlbumPhoto.php`
6. Views: `resources/views/workspace/albums/`
7. Migration: `database/migrations/2026_11_30_000001_create_gallery_tables.php`
8. Tests: `tests/Feature/GalleryTest.php`

Migration files are named `YYYY_MM_DD_NNNNNN_description.php`. The most recent at the time of writing is `2026_11_30_000001_create_gallery_tables.php`.
