# Long-Term Maintenance Roadmap

> **Audience:** Hub Director, maintainers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [technical-debt-register.md](technical-debt-register.md), [maintenance-strategy.md](maintenance-strategy.md), [dependency-updates.md](dependency-updates.md), [../13-product-and-business/product-roadmap.md](../13-product-and-business/product-roadmap.md)

This is a proposed order of maintenance work. It is a recommendation, not a commitment. Item IDs refer to the [register](technical-debt-register.md).

## Phase 1: before launch (now)

| Work | IDs |
| --- | --- |
| Owner actions: strong `SEED_PASSWORD`, `BLOCK_DEFAULT_PASSWORDS=true`, SMTP, S3 storage, paid database | TD-008, TD-016, [project-handover.md](project-handover.md) |
| Confirm the tester password from commit `87df882` is changed | TD-015 |
| Branch protection on `main` with required CI | TD-014 |
| Legal review of Privacy and Terms | handover |

## Phase 2: first three months

| Work | IDs |
| --- | --- |
| Scheduler and pruning (audit log, notifications, failed jobs) | TD-005 |
| Separate queue worker | TD-006 |
| Fix `TRUSTED_PROXIES` config read | TD-004 |
| Dependabot, Larastan, coverage in CI | TD-013 |
| Restore UI for soft deletes | TD-007 |
| First tagged release `v1.0.0` | [versioning-strategy.md](versioning-strategy.md) |
| Uptime monitoring on `/up` | [maintenance-strategy.md](maintenance-strategy.md) |

## Phase 3: three to twelve months

| Work | IDs |
| --- | --- |
| Application status transition rules; re-apply after withdrawal | TD-010, TD-011 |
| E-mail notification for staff messages to applicants | TD-018 |
| Member self-service: change e-mail, delete account, export data (with legal input) | TD-009 |
| API v2 tombstones | TD-017 |
| Paratest for faster CI | TD-012 |
| Housekeeping: `.user.ini`, empty views, Composer name | TD-001, TD-002, TD-003 |

## Phase 4: yearly

- Laravel 13 upgrade (framework 12.73.0 installed, 13.44.1 available at verification). Plan it as its own release.
- PHPUnit major upgrade, `laravel/tinker` 3.
- Decide a date to deprecate API v1 once v2 adoption is known ([deprecation-policy.md](deprecation-policy.md)).
- Review PHP (`php:8.4-apache`) and Node (22) support dates.
- Review hosting: staging environment, Redis, CDN ([../09-deployment-and-operations/scaling-considerations.md](../09-deployment-and-operations/scaling-considerations.md)).

## Review

Revisit this roadmap each quarter alongside the register. Move finished items out and record them in `.ai/changelog.md`.
