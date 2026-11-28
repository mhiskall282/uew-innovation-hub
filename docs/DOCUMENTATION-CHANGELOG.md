# Documentation Changelog

> **Audience:** Maintainers · **Last verified:** 2026-11-30
> **Related:** [Documentation portal](README.md), [Coverage matrix](DOCUMENTATION-COVERAGE.md), [Final report](DOCUMENTATION-FINAL-REPORT.md)

This log records what was documented, updated and verified, newest first. Add an entry whenever a page changes because the code changed.

## 2026-11-30: Full documentation exercise

### Added

- `docs/README.md`: central portal with role-based reading paths, a section index, conventions and known limitations.
- 159 pages in 13 numbered sections:

| Section | Pages | Notes |
| --- | --- | --- |
| [01 Project overview](01-project-overview/project-overview.md) | 8 | Plain-language overview, scope, technology versions, glossary |
| [02 User guide](02-user-guide/getting-started.md) | 20 | 11 guides, 9 feature guides, a screenshot plan (38 screens) |
| [03 Administration](03-administration/administrator-overview.md) | 10 | Staff accounts, roles, settings, content, audit log, CLI tasks |
| [04 Architecture](04-architecture/system-architecture.md) | 14 | 15 ADRs, dependency map, lifecycle and flow diagrams |
| [05 Developer guide](05-developer-guide/developer-onboarding.md) | 19 | Set-up on Windows and Unix, standards, testing, debugging |
| [06 API reference](06-api-reference/api-overview.md) | 8 | v1 and v2, 28 routes, v1-to-v2 migration table |
| [07 Database](07-database/database-overview.md) | 10 | 30 tables, 3 ER diagrams, 29 foreign keys, backups |
| [08 Security](08-security/security-overview.md) | 12 | Controls, honest gaps, STRIDE threat model, incident response |
| [09 Deployment and operations](09-deployment-and-operations/deployment-overview.md) | 14 | Canonical environment variable reference, Render, Docker, CI/CD |
| [10 Testing and quality](10-testing-and-quality/testing-strategy.md) | 10 | Test inventory, coverage gaps, QA checklist |
| [11 Troubleshooting](11-troubleshooting/troubleshooting-overview.md) | 10 | Symptoms, causes, diagnostics, fixes |
| [12 Maintenance](12-maintenance/maintenance-overview.md) | 14 | Technical debt register, dependency status, handover |
| [13 Product and business](13-product-and-business/product-overview.md) | 10 | Requirements labelled Implemented, Inferred or Proposed |

- `DOCUMENTATION-COVERAGE.md`, `DOCUMENTATION-FINAL-REPORT.md` and this changelog.

### Updated

- Root `README.md`: links to the portal and the API versions.
- `docs/architecture.md`, `docs/api.md`, `docs/deployment.md`, `docs/security.md`: a note at the top points to the newer numbered sections. Their content is unchanged.
- `docs/05-developer-guide/testing-guide.md`: test count (197), commit reference, and literal test passwords replaced with references to where they are defined.
- Garbled characters (text saved in the wrong encoding) repaired in 4 pages.

### Code changes made during the exercise (and documented)

These came from the review and from the request for a versioned, scalable API.

| Commit | Change |
| --- | --- |
| `e68fca3` | API v2 added (cursor pagination, `updated_since`, ETag/304, machine-readable error codes, `/reference`, `/albums`). v1 frozen. Version index at `/api`, Deprecation and Sunset headers, `config/api.php`. |
| `d1296b1` | Failed broadcast state; CSV exports audited; audit log filter covers all 18 areas; container upload limits fit gallery uploads; announcement e-mails no longer pre-ticked; password hints match the rule; no capacity query per programme; Node version pinned; duplicate seeder check removed. |
| Docs commit (this exercise) | `/up` also checks the database; CORS exposes API headers to browser apps; v1 honours `API_CACHE_SECONDS`; one API docs URL default; `.env.example` lists the remaining API and container variables. |

### Verified

- Every one of the 163 planned files exists, and no unplanned file is in a numbered section.
- Internal links and anchors in 171 Markdown files resolve (checked by script).
- All 51 Mermaid diagrams parse with Mermaid 11 in a headless browser.
- Referenced source paths exist, apart from files that are generated at run time (`public/hot`, the config cache) or labelled as recommended (`.github/dependabot.yml`, the worked "Testimonial" example).
- No real `.env` secret value, `APP_KEY`, AWS key, private key, GitHub token or the historic tester password appears in any page.
- API pages checked against `php artisan route:list --path=api` (28 routes) and the API tests.
- Database pages checked against a schema dump of a fresh migration run.
- PHPUnit: 197 tests pass. Pint passes.
