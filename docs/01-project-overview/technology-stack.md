# Technology stack

> **Audience:** Developers, technical reviewers, IT staff · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [System architecture](../04-architecture/system-architecture.md), [Dependency management](../05-developer-guide/dependency-management.md), [Dependency map](../04-architecture/dependency-map.md), [Docker guide](../09-deployment-and-operations/docker-guide.md)

This page lists the languages, frameworks, libraries and services the platform uses, with the installed versions. Constraints come from `composer.json` and `package.json`. Installed versions come from `composer.lock` and `package-lock.json`. Refresh this table when you update dependencies.

## Back end

| Component | Constraint | Installed | Purpose |
| --- | --- | --- | --- |
| PHP | `^8.2` (`composer.json`) | 8.4 in the Docker image (`php:8.4-apache`) and in CI (`PHP_VERSION: '8.4'`) | Language runtime |
| Laravel | `^12.0` | v12.73.0 | Framework |
| barryvdh/laravel-dompdf | `^3.1` | v3.1.2 (dompdf/dompdf v3.1.6) | PDF report export |
| league/flysystem-aws-s3-v3 | `^3.0` | 3.35.3 (aws/aws-sdk-php 3.407.1) | S3-compatible document and photo storage |
| laravel/tinker | `^2.10.1` | v2.11.1 | Interactive console |
| league/commonmark | pulled in by Laravel | 2.10.7 | Markdown for staff-edited pages |

Local development on this machine runs PHP 8.4.27 (`php -v`).

## Back end development tools

| Tool | Constraint | Installed | Purpose |
| --- | --- | --- | --- |
| PHPUnit | `^11.5.50` | 11.5.58 | Feature and unit tests |
| Laravel Pint | `^1.24` | v1.32.3 | Code style (CI job "Code style") |
| laravel/pail, sail, collision, mockery, fakerphp/faker | see `composer.json` | see `composer.lock` | Log tail, Docker dev, test output, mocking, fake data |

## Front end

| Component | Constraint | Installed | Purpose |
| --- | --- | --- | --- |
| Tailwind CSS | `^4.0.0` | 4.3.5 | Styling (via `@tailwindcss/vite` 4.3.5) |
| Alpine.js | `^3.17.5` | 3.17.5 | Small interactive behaviours (menus, forms) |
| Vite | `^7.0.7` | 7.3.8 | Asset build (`npm run build`, `npm run dev`) |
| laravel-vite-plugin | `^2.0.0` | 2.1.0 | Laravel and Vite integration |
| axios | `^1.11.0` | 1.21.1 | HTTP client (listed dev dependency) |
| concurrently | `^9.0.1` | see lock | Runs dev processes together (`composer dev`) |
| Blade | part of Laravel | v12 | Server-rendered templates in `resources/views` |

**Fonts** are bundled through npm, not loaded from a font CDN:

| Font | Package | Version | Use |
| --- | --- | --- | --- |
| Inter (variable) | `@fontsource-variable/inter` | 5.3.0 | Body text (`--font-sans` in `resources/css/app.css`) |
| JetBrains Mono (variable) | `@fontsource-variable/jetbrains-mono` | 5.3.0 | Numbers and code (`--font-mono`) |

## Browser testing

| Tool | Installed | Notes |
| --- | --- | --- |
| Playwright (`@playwright/test`) | 1.65.1 | Chromium, Firefox, WebKit and mobile projects; scripts `npm run test:e2e`, `npm run test:e2e:chromium` |
| axe-core (`@axe-core/playwright`) | 4.13.0 | Accessibility checks |

## Data and storage

| Item | Detail |
| --- | --- |
| Hosted database | PostgreSQL (Render free plan; `DB_CONNECTION=pgsql`, `DB_URL` from `render.yaml`) |
| Local default | SQLite (`DB_CONNECTION=sqlite` in `.env.example`, `config/database.php`) |
| Extra drivers in image | `pdo_pgsql` and `pdo_mysql` |
| Sessions, cache, queue | Database driver for all three on Render (`render.yaml`) |
| Documents | Disk `private` (container) or `s3` (`DOCUMENTS_DISK`) |
| Gallery photos | Disk `gallery` (container) or `s3` (`GALLERY_DISK`) |

## Runtime image

`Dockerfile` has three stages:

1. `node:22-alpine` builds the assets with `npm ci` and `npm run build`.
2. `composer:2` installs PHP packages without dev dependencies.
3. `php:8.4-apache` runs the app. It installs `pdo_pgsql`, `pdo_mysql`, `zip`, `intl`, `bcmath`, `opcache` and `gd`, enables Apache `rewrite` and `headers`, and uses `docker/entrypoint.sh` to start.

## Hosting and automation

| Service | Role | File |
| --- | --- | --- |
| Render | Hosting, free Docker web service and free PostgreSQL, auto-deploy of `main`, health check `/up` | `render.yaml` |
| GitHub Actions | CI: Pint, front-end build, tests on SQLite and PostgreSQL, Playwright browser tests, dependency audit, Docker image build | `.github/workflows/ci.yml` |
| Vercel (alternative) | Entry file `api/index.php`, runtime `vercel-php@0.8.0` | `vercel.json`; completeness not verified |
| SMTP provider | Outgoing e-mail; Gmail SMTP was tested end to end (handover report) | Site settings page |

## Not used

No Redis (recommended once traffic grows, per `.env.example`), no search engine, no JavaScript framework beyond Alpine.js, no queue broker other than the database, no third-party analytics found in the repository.

> **Requires confirmation:** the exact version of `concurrently` and the dev-only packages in `composer.lock`; they are not pinned in this page.
