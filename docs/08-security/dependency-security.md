# Dependency Security

> **Audience:** developers, maintainers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Dependency management](../05-developer-guide/dependency-management.md), [Dependency updates](../12-maintenance/dependency-updates.md), [CI/CD pipeline](../09-deployment-and-operations/ci-cd-pipeline.md), [Secrets management](secrets-management.md)

This page describes what checks run on third-party packages, what does not, and the packages that deserve attention. Commands here exist in `.github/workflows/ci.yml` or `scripts/security-scan.ps1`.

## Direct dependencies

From `composer.json`:

| Group | Packages |
| --- | --- |
| `require` | `php`, `laravel/framework` (12.x), `laravel/tinker`, `barryvdh/laravel-dompdf`, `league/flysystem-aws-s3-v3` |
| `require-dev` | `fakerphp/faker`, `laravel/pail`, `laravel/pint`, `laravel/sail`, `mockery/mockery`, `nunomaduro/collision`, `phpunit/phpunit` |

From `package.json` (`devDependencies`, used at build time only): `vite`, `laravel-vite-plugin`, `tailwindcss`, `@tailwindcss/vite`, `axios`, `concurrently`, `@playwright/test`, `@axe-core/playwright`. The Docker build runs `npm ci` in a separate stage; only `public/build` reaches the final image. The production image installs Composer packages with `--no-dev`.

Points to know:

- `laravel/tinker` is in `require`, not `require-dev`, so it ships in the production image. It is a command-line REPL and is not reachable over HTTP, but it widens what a shell on the server can do. Move it to `require-dev` unless production use is intended. (Not changed; documentation only.)
- `barryvdh/laravel-dompdf` renders the workspace report PDF (`workspace.reports.pdf`). No `config/dompdf.php` is published, so the package defaults apply (remote resource loading off by default in current releases). The report views contain no user-controlled HTML or remote URLs. **Requires confirmation:** the installed dompdf version in `composer.lock` and its default for `isRemoteEnabled`.
- `league/flysystem-aws-s3-v3` is used only when a disk is configured with `s3`.

## Automated checks that exist

| Check | Where | Command | Fails the build? |
| --- | --- | --- | --- |
| PHP advisories | CI job `security` | `composer audit --locked --no-interaction` | Yes (non-zero exit on advisories) |
| npm advisories, production deps | CI job `security` | `npm audit --omit=dev --audit-level=high` | Yes, for high and critical |
| Lockfile install | CI | `composer install`, `npm ci` | Yes if the lock is out of sync |
| Docker image build | CI job `docker` | `docker/build-push-action` with `push: false` | Yes |
| Code style | CI job `lint` | `vendor/bin/pint --test` | Yes |
| Local security scan | `scripts/security-scan.ps1` | `powershell -File scripts/security-scan.ps1` | No, report only |

`scripts/security-scan.ps1` is read-only and local. It runs `composer audit --locked`, `npm audit`, `npm audit signatures`, `composer validate`, `composer check-platform-reqs --lock`, an abandoned-package report, a search for executable files under `public/` and `storage/`, a web-shell pattern grep, a review list of dangerous PHP functions in `app/`, raw SQL with interpolation, secrets in tracked files and in history, files that must never be tracked, `git fsck`, and production config checks.

### The 2026-11-28 scan and the Defender finding

The scan was added in commit `9f3bdee`. Microsoft Defender had flagged a web-shell signature on the test suite. The cause was a test fixture in `tests/Feature/GalleryTest.php` that wrote web-shell-like text into a fake "disguised" file to prove non-images are rejected. It was a false positive: the string was test data, not application code. The fixture now uses harmless text (`'This text file only pretends to be a photo.'`) and a comment explains why. The scan's web-shell grep covers `tests/` so a future fixture that looks like a shell is caught by the scan before Defender flags it.

## What is not automated

| Gap | Note |
| --- | --- |
| No `.github/dependabot.yml` or Renovate config | Updates are manual. See [dependency updates](../12-maintenance/dependency-updates.md) |
| No secret scanning step in CI | `scripts/security-scan.ps1` does this locally only |
| No SAST (CodeQL, Psalm taint, PHPStan) | Recommended, not implemented |
| No container image scan (Trivy, Grype) | Recommended, not implemented |
| `composer audit` and `npm audit` run on push and pull requests only | A new advisory published between pushes is not noticed. A scheduled workflow would fix this; `ci.yml` has no `schedule` trigger |
| `npm audit` ignores `devDependencies` (`--omit=dev`) | Build tools are not audited in CI. They are not in the runtime image |
| No DAST scan (OWASP ZAP) | Noted as not run in `docs/audit/TEST_RESULTS.md` |
| Base images `php:8.4-apache`, `node:22-alpine`, `composer:2` use floating tags | Rebuilds pick up upstream changes. Pinning by digest is Recommended, not implemented |
| GitHub Actions use major tags (`actions/checkout@v5`), not commit SHAs | Standard but not hardened |

## Routine

1. Before every release run `composer audit --locked` and `npm audit --omit=dev` (also run in CI).
2. Run `powershell -File scripts/security-scan.ps1` and read the output.
3. Update with `composer update` (review `composer.lock` diff) and `npm update`, then run the full test suite ([testing guide](../05-developer-guide/testing-guide.md)).
4. Laravel 13 and PHPUnit 13 were noted as available and deferred in the audit (F-13). Laravel 12.x is still supported.
