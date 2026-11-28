# Developer Onboarding

> **Audience:** New developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [prerequisites](prerequisites.md), [installation-guide](installation-guide.md), [project-structure](project-structure.md), [testing-guide](testing-guide.md), [development-workflow](development-workflow.md)

This page is the starting point for a developer joining the UEW Innovation Hub project. It lists what the system is, the order in which to read and run things, and a checklist for your first day and first week. Each step links to the page with the detail.

## What you are working on

The UEW Innovation Hub is one Laravel 12 application (PHP, Blade, Tailwind CSS v4, Alpine.js, Vite 7) with four entry points:

| Entry point | Path | Route names | Who uses it |
| --- | --- | --- | --- |
| Public website | `/` | `site.*` | Anyone, no sign-in |
| Member portal | `/portal` | `portal.*` | People with the `member` role |
| Staff workspace | `/workspace` | `workspace.*` | Staff roles (everything except `member`) |
| Open data API | `/api/v1` | `api.v1.*` | Read-only JSON for anyone |

It replaces an older procedural PHP system. That system is still in the repository under `legacy/` for reference. It is never served. See [project-structure](project-structure.md).

Production is a Docker image on Render (free plan) with PostgreSQL. Render deploys every push to `main` automatically. Local development uses SQLite.

## Day one checklist

1. Install the tools in [prerequisites](prerequisites.md). Check `php -v` (8.2 or newer, the project runs on 8.4), `composer -V` (2.x), `node -v` (22 recommended) and `git --version`.
2. Clone the repository and run the setup in [installation-guide](installation-guide.md).
3. Seed demo data and start the app (`php artisan serve`, then `npm run dev`). Details in [development-environment-setup](development-environment-setup.md).
4. Sign in to the workspace with a demo account. The account list is in `database/seeders/DemoContentSeeder.php`. The password is whatever `SEED_PASSWORD` is set to in your `.env`.
5. Run the PHPUnit suite: `php artisan test`. It should pass (177 tests at the time of writing). See [testing-guide](testing-guide.md).
6. Run the style check: `vendor/bin/pint --test` (Linux/macOS) or `vendor\bin\pint --test` (Windows).

## First week reading list

| Order | Read | Why |
| --- | --- | --- |
| 1 | [project-structure](project-structure.md) | Where code lives |
| 2 | [coding-standards](coding-standards.md) and [naming-conventions](naming-conventions.md) | House style taken from the existing code |
| 3 | `../04-architecture/authorization-architecture.md` | Roles, permissions, gates and policies |
| 4 | [backend-development](backend-development.md) | Controller, FormRequest, service, model pattern |
| 5 | [frontend-development](frontend-development.md) | Blade components, Tailwind tokens, Alpine, Vite |
| 6 | [adding-new-features](adding-new-features.md) | A worked example from migration to test |
| 7 | [development-workflow](development-workflow.md) | Daily loop, pre-push checks, CI |

Read the code that implements the thing you are about to change. The existing documents in `docs/` and `.ai/` are helpful but can lag behind the code. When they disagree, the code wins.

## Key conventions to know early

- Every PHP file starts with `declare(strict_types=1);` (a few files in `database/` and `tests/` do not; follow the file you are editing).
- Controllers stay thin. Business logic lives in `app/Services/`.
- Roles and permissions are enums in `app/Enums/Role.php` and `app/Enums/Permission.php`. The role-to-permission matrix exists only in `Role::permissions()`.
- Every `Permission` case is registered as a gate in `app/Providers/AppServiceProvider.php`. Routes use `can:<permission>` middleware.
- Lazy loading is blocked outside production (`Model::preventLazyLoading`). Eager-load relationships or tests fail. See [common-development-issues](common-development-issues.md).
- Prose in the UI uses British spelling (programme, organisation, colour). Code identifiers use `program`.
- Never commit `.env`, real passwords or API keys. See `../08-security/secrets-management.md`.

## Where to get help

| Question | Look at |
| --- | --- |
| How do I run tests? | [testing-guide](testing-guide.md) |
| Something fails on my machine | [common-development-issues](common-development-issues.md), `../11-troubleshooting/troubleshooting-overview.md` |
| How is it deployed? | `../09-deployment-and-operations/deployment-overview.md` |
| Which tables exist? | `../07-database/tables-and-columns.md` |
| Which endpoints exist? | `../06-api-reference/endpoints.md` |

> **Requires confirmation:** `.ai/ai.md` and `AGENTS.md` describe an "AI agent operating system" for the repository, including a `<x-npontu.button>` component naming. The current Blade components live in `resources/views/components/ui/` (`<x-ui.button>` and so on) and `resources/views/components/npontu/` is empty. Treat the `.ai/` files as guidance, and check the code first.
