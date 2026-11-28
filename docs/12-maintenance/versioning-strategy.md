# Versioning Strategy

> **Audience:** Developers, maintainers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [release-management.md](release-management.md), [deprecation-policy.md](deprecation-policy.md), [../06-api-reference/api-overview.md](../06-api-reference/api-overview.md)

## What exists today

| Item | Current practice |
| --- | --- |
| Git tags | None. `git tag` prints nothing. |
| App version | No version number. The deployed state is the latest commit on `main` (currently `d1296b1`). |
| `composer.json` version | Not set. |
| `package.json` version | Not used for releases. |
| API | URL versioning: `/api/v1` (frozen) and `/api/v2` (current). Each response has an `API-Version` header. `GET /api` lists versions. |
| Database | Migrations in `database/migrations/`, ordered by timestamp. |

## API versioning (implemented)

- The version is in the URL. `v1` keeps its contract for its whole life, except additive changes (for example the ETag and 304 support added to v1).
- A breaking change means a new version (`v3`) with its own routes, controllers and resources.
- Retirement follows [deprecation-policy.md](deprecation-policy.md).

## Recommended, not implemented: app versioning

Use semantic versioning (`MAJOR.MINOR.PATCH`) for the application.

| Change | Bump |
| --- | --- |
| Breaking change for operators (required env var, removed route, manual migration step) | MAJOR |
| New feature, backward compatible | MINOR |
| Bug fix, copy change, dependency patch | PATCH |

Suggested steps: create an annotated tag on `main` after CI is green (`git tag -a v1.0.0 -m "First production release"`; `git push origin v1.0.0`), and write release notes from `.ai/changelog.md`. Start at `v1.0.0` when the owner actions in [project-handover.md](project-handover.md) are done. Render still deploys from `main`, so tags are labels, not deploy triggers.

## Recommended, not implemented: database

Never edit a migration that has been deployed. Each schema change is a new migration. See [database-maintenance.md](database-maintenance.md).
