# Dependency Updates

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [security-maintenance.md](security-maintenance.md), [technical-debt-register.md](technical-debt-register.md), [../05-developer-guide/dependency-management.md](../05-developer-guide/dependency-management.md), [../09-deployment-and-operations/ci-cd-pipeline.md](../09-deployment-and-operations/ci-cd-pipeline.md)

This page lists the direct dependencies that have newer releases and describes how to update them. Automated updates are not set up (see TD-013 in the [register](technical-debt-register.md)), so updates are manual.

## Current state (2026-11-30)

`composer outdated --direct` reports only major-version upgrades, so all minor and patch updates within the allowed ranges are already installed.

| Package | Manager | Installed | Latest | Type | Notes |
| --- | --- | --- | --- | --- | --- |
| `laravel/framework` | Composer | 12.73.0 | 13.44.1 | Major | Constraint is `^12.0`. Read the Laravel 13 upgrade guide first. |
| `laravel/tinker` | Composer | 2.11.1 | 3.2.0 | Major | Dev tool only. |
| `phpunit/phpunit` | Composer | 11.5.58 | 13.4.8 | Major | Skips 12. Update the test suite attributes if needed. |
| `concurrently` | npm | 9.2.5 | 10.0.7 | Major | Used by `composer dev`. |
| `laravel-vite-plugin` | npm | 2.1.0 | 1.3.0 | Reported lower | `npm outdated` printed a "latest" older than the installed version. |
| `vite` | npm | 7.3.8 | 6.4.6 | Reported lower | Same oddity. |

> **Requires confirmation:** the npm "latest" values for `vite` and `laravel-vite-plugin` are lower than the installed versions. This looks like a registry or dist-tag quirk. Check `npm view vite dist-tags` before acting. Do not downgrade.

The PHP runtime is `php:8.4-apache` and the asset build uses `node:22-alpine` (see [`Dockerfile`](../../Dockerfile)). `composer.json` allows PHP `^8.2`; `package.json` requires Node `^20.19.0 || >=22.12.0`.

## Update procedure

1. Create a branch (or work on `main` only if you accept auto-deploy; see [release-management.md](release-management.md)).
2. Check the current state:

   ```powershell
   composer outdated --direct
   npm outdated
   ```

3. Apply compatible updates:

   ```powershell
   composer update            # within composer.json constraints
   npm update                 # within package.json ranges
   ```

   For a major update, change the constraint deliberately, for example `composer require laravel/framework:^13.0 -W`, and read the upgrade guide. Update one major at a time.
4. Run the audits (the same ones CI runs):

   ```powershell
   composer audit --locked --no-interaction
   npm audit --omit=dev --audit-level=high
   ```

5. Run the checks CI runs. Commands are in [../05-developer-guide/testing-guide.md](../05-developer-guide/testing-guide.md); the short form is:

   ```powershell
   vendor/bin/pint --test
   npm run build
   php artisan test
   ```

6. Commit `composer.lock` and `package-lock.json` together with the manifest change. Push and wait for all CI jobs: Code style, Front-end build, Tests (sqlite), Tests (pgsql), Browser tests, Dependency audit, Docker image builds.
7. After deploy, check `/up` and one page per area (public, portal, workspace, `/api`).

## Cadence

| Task | Frequency |
| --- | --- |
| `composer audit`, `npm audit` | Every CI run, plus a manual look weekly |
| Patch and minor updates | Monthly |
| Major updates | Plan per release, at least once a year for Laravel |
| PHP and Node base images | When a new minor is released, and before a version loses support |

## Recommended, not implemented

- A `.github/dependabot.yml` for Composer, npm, GitHub Actions and Docker.
- Static analysis (Larastan) so major upgrades show type breaks early.
