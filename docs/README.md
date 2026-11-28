# UEW Innovation Hub Documentation

> **Audience:** Everyone · **Last verified:** 2026-11-30 against the `main` branch (API v2, health check and CORS changes included)
> **Related:** [Project README](../README.md), [Coverage matrix](DOCUMENTATION-COVERAGE.md), [Final report](DOCUMENTATION-FINAL-REPORT.md), [Changelog](DOCUMENTATION-CHANGELOG.md)

This is the entry point to the documentation for the UEW Innovation Hub platform, the project, programme and communications system of the Innovation Hub at the University of Education, Winneba (UEW), Ghana. Start with the reading path for your role below. Every section is a folder of short pages, and every technical claim points to the source file it was checked against.

## What the platform is

One Laravel 12 application serves four surfaces:

| Surface | URL | Who uses it |
| --- | --- | --- |
| Public website | `/` | Anyone: programmes, projects, publications, news, gallery, partners, community leaderboard |
| Member portal | `/portal` | Members: apply to programmes, message the Hub, submit progress reports, earn badges |
| Staff workspace | `/workspace` | Hub staff, by role: programmes, reviews, members, broadcasts, content, reports, administration |
| Open data API | `/api/v1`, `/api/v2` | Partners and app developers: read-only JSON of public data |

The live test site is https://uew-innovation-hub.onrender.com. It runs with demo data, so it is not the production launch. See [Production deployment](09-deployment-and-operations/production-deployment.md) for what changes at launch.

## Purpose of this documentation

- Let someone new understand, use, run, change and hand over the system without the original developers.
- Record what is implemented. Plans and recommendations are labelled as such.
- Keep one canonical page per topic and link to it instead of repeating it.

## Find documentation by role

| I am... | Start here |
| --- | --- |
| A member of the public or a new member | [Getting started](02-user-guide/getting-started.md) |
| New Hub staff | [Getting started](02-user-guide/getting-started.md), then [Dashboard guide](02-user-guide/dashboard-guide.md) |
| The Hub Director or a Programme Officer | [Administrator overview](03-administration/administrator-overview.md) |
| A developer joining the project | [Developer onboarding](05-developer-guide/developer-onboarding.md) |
| An app developer using the API | [API overview](06-api-reference/api-overview.md) |
| A DevOps or infrastructure engineer | [Deployment overview](09-deployment-and-operations/deployment-overview.md) |
| A security reviewer | [Security overview](08-security/security-overview.md) |
| A project manager, stakeholder or auditor | [Product overview](13-product-and-business/product-overview.md) |
| Taking over the project | [Project handover](12-maintenance/project-handover.md) |

## Recommended reading paths

**Ordinary users:** [Project overview](01-project-overview/project-overview.md) → [Getting started](02-user-guide/getting-started.md) → [User guide feature pages](02-user-guide/feature-guides/README.md) → [User FAQ](02-user-guide/user-faq.md) → [Common user issues](02-user-guide/common-user-issues.md)

**Administrators:** [Project overview](01-project-overview/project-overview.md) → [Administrator overview](03-administration/administrator-overview.md) → [System configuration](03-administration/system-configuration.md) → [Security overview](08-security/security-overview.md) → [Admin troubleshooting](03-administration/admin-troubleshooting.md)

**New developers:** [Project overview](01-project-overview/project-overview.md) → [System architecture](04-architecture/system-architecture.md) → [Developer onboarding](05-developer-guide/developer-onboarding.md) → [Installation guide](05-developer-guide/installation-guide.md) → [Project structure](05-developer-guide/project-structure.md) → [API overview](06-api-reference/api-overview.md) → [Testing guide](05-developer-guide/testing-guide.md)

**Senior engineers:** [Application architecture](04-architecture/application-architecture.md) → [Database overview](07-database/database-overview.md) → [API endpoints](06-api-reference/endpoints.md) → [Threat model](08-security/threat-model.md) → [Production deployment](09-deployment-and-operations/production-deployment.md) → [Maintenance strategy](12-maintenance/maintenance-strategy.md) → [Technical debt register](12-maintenance/technical-debt-register.md)

**DevOps engineers:** [System architecture](04-architecture/system-architecture.md) → [Infrastructure overview](09-deployment-and-operations/infrastructure-overview.md) → [Deployment overview](09-deployment-and-operations/deployment-overview.md) → [CI/CD pipeline](09-deployment-and-operations/ci-cd-pipeline.md) → [Logging and monitoring](09-deployment-and-operations/logging-and-monitoring.md) → [Backup and recovery](09-deployment-and-operations/backup-and-recovery.md)

## All sections

| # | Section | What it covers | Key pages |
| --- | --- | --- | --- |
| 01 | [Project overview](01-project-overview/project-overview.md) | What the platform is, why it exists, scope, stack, glossary | [Features](01-project-overview/features-and-capabilities.md), [Scope](01-project-overview/system-scope.md), [Technology stack](01-project-overview/technology-stack.md), [Glossary](01-project-overview/glossary.md), [FAQ](01-project-overview/frequently-asked-questions.md) |
| 02 | [User guide](02-user-guide/getting-started.md) | Sign-up, sign-in, portal, applications, notifications, profile | [Registration](02-user-guide/account-registration.md), [Sign-in](02-user-guide/login-and-authentication.md), [Roles](02-user-guide/user-roles-and-permissions.md), [Feature guides](02-user-guide/feature-guides/README.md), [Screenshot plan](02-user-guide/screenshot-plan.md) |
| 03 | [Administration](03-administration/administrator-overview.md) | Staff accounts, roles, settings, content, data, audit log | [User management](03-administration/user-management.md), [Roles and permissions](03-administration/role-and-permission-management.md), [Configuration](03-administration/system-configuration.md), [Workflows](03-administration/administrative-workflows.md) |
| 04 | [Architecture](04-architecture/system-architecture.md) | How the system works inside, with diagrams | [Application](04-architecture/application-architecture.md), [Authentication](04-architecture/authentication-architecture.md), [Authorization](04-architecture/authorization-architecture.md), [Request lifecycle](04-architecture/request-response-lifecycle.md), [Decisions](04-architecture/architectural-decisions.md), [Dependency map](04-architecture/dependency-map.md) |
| 05 | [Developer guide](05-developer-guide/developer-onboarding.md) | Set-up, structure, standards, workflow, testing, debugging | [Installation](05-developer-guide/installation-guide.md), [Project structure](05-developer-guide/project-structure.md), [Testing guide](05-developer-guide/testing-guide.md), [Adding features](05-developer-guide/adding-new-features.md) |
| 06 | [API reference](06-api-reference/api-overview.md) | Open data API v1 (frozen) and v2 (current) | [Endpoints](06-api-reference/endpoints.md), [Examples](06-api-reference/request-response-examples.md), [Errors](06-api-reference/error-codes.md), [Rate limits](06-api-reference/rate-limiting.md) |
| 07 | [Database](07-database/database-overview.md) | Tables, relationships, migrations, seeds, backups | [Tables and columns](07-database/tables-and-columns.md), [ER diagrams](07-database/entity-relationships.md), [Migrations](07-database/migrations.md), [Backup and restore](07-database/backup-and-restore.md) |
| 08 | [Security](08-security/security-overview.md) | Controls, gaps, threat model, incident response | [Authentication](08-security/authentication-security.md), [Threat model](08-security/threat-model.md), [Secrets](08-security/secrets-management.md), [Incident response](08-security/security-incident-response.md) |
| 09 | [Deployment and operations](09-deployment-and-operations/deployment-overview.md) | Environments, Render, Docker, CI/CD, monitoring, rollback | [Environment variables](09-deployment-and-operations/environment-configuration.md), [Production](09-deployment-and-operations/production-deployment.md), [CI/CD](09-deployment-and-operations/ci-cd-pipeline.md), [Rollback](09-deployment-and-operations/rollback-procedures.md) |
| 10 | [Testing and quality](10-testing-and-quality/testing-strategy.md) | Existing tests, gaps, QA checklist | [API testing](10-testing-and-quality/api-testing.md), [Security testing](10-testing-and-quality/security-testing.md), [QA checklist](10-testing-and-quality/quality-assurance-checklist.md) |
| 11 | [Troubleshooting](11-troubleshooting/troubleshooting-overview.md) | Symptoms, causes, fixes by area | [Installation](11-troubleshooting/installation-problems.md), [Sign-in](11-troubleshooting/authentication-problems.md), [Deployment](11-troubleshooting/deployment-problems.md), [Logs](11-troubleshooting/logging-and-diagnostics.md) |
| 12 | [Maintenance](12-maintenance/maintenance-overview.md) | Long-term upkeep, debt, versions, releases, handover | [Technical debt](12-maintenance/technical-debt-register.md), [Dependency updates](12-maintenance/dependency-updates.md), [Deprecation policy](12-maintenance/deprecation-policy.md), [Handover](12-maintenance/project-handover.md) |
| 13 | [Product and business](13-product-and-business/product-overview.md) | Requirements, personas, stories, workflows, roadmap | [Feature inventory](13-product-and-business/feature-inventory.md), [Functional requirements](13-product-and-business/functional-requirements.md), [Workflows](13-product-and-business/business-workflows.md), [Roadmap](13-product-and-business/product-roadmap.md) |

Earlier documents kept for reference: [architecture.md](architecture.md), [api.md](api.md) (API v1 summary), [deployment.md](deployment.md), [security.md](security.md) (includes the 2026-11-28 security scan), [guides/](guides/member-guide.md) (member, staff and stakeholder guides) and [audit/](audit/SYSTEM_AUDIT_REPORT.md). Where they disagree with the numbered sections, the numbered sections are newer and were checked against the code.

## Documentation conventions

- **Header.** Each page starts with its audience, the date it was last verified and related pages.
- **Labels.** "Requires confirmation" marks facts that could not be checked from the repository. "Recommended, not implemented" marks advice. "Inferred" marks requirements or decisions read from code rather than recorded by the Hub.
- **Two levels.** Complicated topics give a "Simple explanation" for non-technical readers, then a "Technical explanation".
- **Warnings.** `> **Warning:**` blocks mark destructive or hard-to-reverse operations.
- **Source links.** File paths are relative to the repository root, such as `app/Services/GalleryService.php`. Links from pages use relative paths, so they work on GitHub.
- **Spelling.** Prose uses British spelling to match the interface (programme, organisation). Code names stay as written (`Program`, `Organization`).
- **Commands.** PowerShell and bash variants are given where they differ. Every command was checked against `composer.json`, `package.json`, `artisan` commands, `scripts/`, the `Dockerfile` or CI.
- **No secrets.** No page contains a real password, key or token. Demo account passwords come from the `SEED_PASSWORD` environment variable.

## Keeping these docs current

Update the relevant page in the same commit as the code change, and add a line to [DOCUMENTATION-CHANGELOG.md](DOCUMENTATION-CHANGELOG.md). Canonical pages: roles in [authorization-architecture.md](04-architecture/authorization-architecture.md), environment variables in [environment-configuration.md](09-deployment-and-operations/environment-configuration.md), tables in [tables-and-columns.md](07-database/tables-and-columns.md), endpoints in [endpoints.md](06-api-reference/endpoints.md), known issues in [technical-debt-register.md](12-maintenance/technical-debt-register.md) and test commands in [testing-guide.md](05-developer-guide/testing-guide.md).

Before committing documentation changes, run the checkers:

```bash
php scripts/validate-docs.php     # links, anchors, source paths, encoding, secrets, Mermaid basics
node scripts/mermaid-check.mjs    # parses every diagram with Mermaid (needs network; PowerShell: $env:E2E_CHANNEL='msedge' first)
```

## Known documentation limitations

- No screenshots yet. [screenshot-plan.md](02-user-guide/screenshot-plan.md) lists the screens to capture.
- Facts about the hosting account (Render plan settings, backups, "wait for CI"), DNS, SMTP and S3 ownership could not be checked from the repository and are marked "Requires confirmation".
- PostgreSQL-specific column types were read from Laravel's schema grammar. The schema dump was generated on SQLite.
- Personas, business requirements and the roadmap are inferred or proposed. No user research or Hub requirements document was available.
- Browser test counts are estimates read from the Playwright specs.
