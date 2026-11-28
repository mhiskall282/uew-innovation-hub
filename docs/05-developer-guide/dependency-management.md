# Dependency Management

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [prerequisites](prerequisites.md), [development-workflow](development-workflow.md), `../08-security/dependency-security.md`, `../12-maintenance/dependency-updates.md`

How PHP and JavaScript dependencies are declared, locked, audited and updated.

## What is installed

PHP (`composer.json`):

| Package | Kind | Purpose |
| --- | --- | --- |
| `php` `^8.2` | platform | Minimum PHP. CI and Docker use 8.4 |
| `laravel/framework` `^12.0` | require | Framework (12.73.0 locally) |
| `laravel/tinker` `^2.10.1` | require | REPL |
| `barryvdh/laravel-dompdf` `^3.1` | require | PDF reports |
| `league/flysystem-aws-s3-v3` `^3.0` | require | S3-compatible document and photo storage |
| `fakerphp/faker`, `mockery/mockery`, `nunomaduro/collision`, `phpunit/phpunit` `^11.5.50` | require-dev | Testing |
| `laravel/pint` `^1.24` | require-dev | Code style |
| `laravel/pail` `^1.2.2` | require-dev | Log tail |
| `laravel/sail` `^1.41` | require-dev | Docker dev environment (not used by this project's docs) |

JavaScript (`package.json`):

| Package | Kind | Purpose |
| --- | --- | --- |
| `alpinejs` `^3.17.5` | dependencies | Client-side interactivity |
| `@fontsource-variable/inter`, `@fontsource-variable/jetbrains-mono` | dependencies | Self-hosted fonts |
| `vite` `^7.0.7`, `laravel-vite-plugin` `^2.0.0`, `tailwindcss` `^4.0.0`, `@tailwindcss/vite` `^4.0.0` | devDependencies | Build |
| `axios` `^1.11.0` | devDependencies | HTTP helper loaded in `resources/js/bootstrap.js` |
| `@playwright/test` `^1.65.1`, `@axe-core/playwright` `^4.13.0` | devDependencies | Browser and accessibility tests |
| `concurrently` `^9.0.1` | devDependencies | Runs processes for `composer dev` |

Both lock files, `composer.lock` and `package-lock.json`, are committed. Commit changes to them together with the manifest change.

## Rules

1. Keep the dependency list short. Use framework features before adding a package (`.ai/rules/core.md`, rule 5).
2. Add runtime packages with `composer require vendor/package`, and dev tools with `composer require --dev`. Add JavaScript packages with `npm install <name>` or `npm install --save-dev <name>`.
3. Use caret ranges as the files do. Commit the lock file.
4. Use `npm ci` and `composer install` to reproduce a build (CI does). Use `npm install` only when you mean to change the lock file.
5. The production image installs PHP packages with `--no-dev`, so anything the running app needs must be in `require`, not `require-dev`. Pint, PHPUnit and Pail are dev-only.
6. Check the licence and maintenance state before adding a package. `scripts/security-scan.ps1` reports abandoned Composer packages.

## Adding, updating, removing

```powershell
composer require vendor/package
composer update vendor/package --with-dependencies    # one package
composer update                                       # everything within constraints
composer outdated --direct                            # what could move
composer remove vendor/package

npm install some-package
npm update                                            # within ranges
npm outdated
npm uninstall some-package
```

```bash
composer require vendor/package
composer outdated --direct
npm outdated
```

After an update, run the full checks: `vendor/bin/pint --test`, `php artisan test`, `npm run build`, and (for front-end changes) `npm run test:e2e:chromium`. `composer update` also runs `post-update-cmd`, which publishes Laravel assets (`php artisan vendor:publish --tag=laravel-assets --ansi --force`).

Laravel major upgrades follow the official upgrade guide. Do them on a branch and watch the PostgreSQL job.

## Security audits

The same two commands run in CI ("Dependency audit"):

```powershell
composer audit --locked --no-interaction
npm audit --omit=dev --audit-level=high
```

A high-severity advisory fails the CI job. The repository history shows the pattern: commit `f41748b` updated Symfony components for several CVEs; commit `df331f4` changed the CI to audit `composer.lock` directly.

`scripts/security-scan.ps1` (PowerShell, read-only) runs a wider set: `composer audit --locked`, `npm audit`, `npm audit signatures`, `composer validate`, `composer check-platform-reqs --lock`, and an abandoned-package report.

```powershell
powershell -File scripts/security-scan.ps1
```

Fix by updating to the patched version (`composer update vendor/package`). If no fix exists, record the risk and the plan in `../12-maintenance/technical-debt-register.md`. Do not silence an advisory without a written reason.

## Platform versions

- `composer.json` requires `php ^8.2`, and the lock file is resolved for the PHP version it was generated on. Developing on 8.2 or 8.3 can work, but CI and production run 8.4. Prefer 8.4 locally.
- The Docker image installs Composer dependencies with `--ignore-platform-reqs` in the vendor stage and then runs on `php:8.4-apache`. A package that needs an extension the image lacks fails at runtime, not at build time. The image has `pdo_pgsql`, `pdo_mysql`, `zip`, `intl`, `bcmath`, `opcache`, `gd`.

## Recommended, not implemented

- Automated update pull requests (Dependabot or Renovate). No such configuration exists in `.github/`.
- Pinning Node with an `.nvmrc` or `engines` field so every machine uses the same major version (`package.json` has none).
- Scheduled dependency audits. CI runs the audit only on pushes and pull requests, so an advisory published between pushes goes unnoticed until the next one.
