# Maintenance Overview

> **Audience:** Maintainers, Hub Director, developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [maintenance-strategy.md](maintenance-strategy.md), [technical-debt-register.md](technical-debt-register.md), [project-handover.md](project-handover.md), [long-term-maintenance-roadmap.md](long-term-maintenance-roadmap.md)

This section explains how to keep the UEW Innovation Hub running and healthy after launch. The system is one Laravel 12 application (public website, member portal, staff workspace, open data API) deployed as a Docker image on Render with PostgreSQL.

## What needs maintaining

| Area | What changes over time | Page |
| --- | --- | --- |
| Dependencies | Laravel, PHP, Node and package releases and advisories | [dependency-updates.md](dependency-updates.md) |
| Database | Table growth (audit logs, notifications, sessions, jobs), backups | [database-maintenance.md](database-maintenance.md) |
| Performance | Query counts, cache, queue, file storage | [performance-maintenance.md](performance-maintenance.md) |
| Security | Passwords, secrets, access, audits | [security-maintenance.md](security-maintenance.md) |
| Code | Debt, refactoring, deprecations | [technical-debt-register.md](technical-debt-register.md), [refactoring-guidelines.md](refactoring-guidelines.md), [deprecation-policy.md](deprecation-policy.md) |
| Releases | Deploys from `main`, versions | [release-management.md](release-management.md), [versioning-strategy.md](versioning-strategy.md) |
| People | New developers, ownership handover | [onboarding-new-developers.md](onboarding-new-developers.md), [project-handover.md](project-handover.md) |

## Current state in short

- Deploys happen on every push to `main` (Render `autoDeploy: true`). No tags exist.
- CI covers style, build, tests on SQLite and PostgreSQL, browser tests, dependency audit and Docker builds.
- 194 tests pass (commit `d1296b1`).
- No scheduled tasks exist. Routine clean-up is manual (TD-005).
- The Render free plan has an ephemeral disk and a database that expires. This is the largest operational risk (TD-016).
- Health check: `/up`.

## Who does what

| Role | Responsibility |
| --- | --- |
| Hub Director (`super_admin`) | Owns the product, approves risk decisions, holds owner-only actions |
| Maintainer developer | Updates, deploys, backups, register review |
| Staff | Report faults through the Hub Director |

> **Requires confirmation:** names of the people holding each role are not recorded in the repository. Fill them in during [project-handover.md](project-handover.md).

## Where to start

1. Read the [technical debt register](technical-debt-register.md).
2. Follow the [maintenance strategy](maintenance-strategy.md) calendar.
3. For faults, use [../11-troubleshooting/troubleshooting-overview.md](../11-troubleshooting/troubleshooting-overview.md).
