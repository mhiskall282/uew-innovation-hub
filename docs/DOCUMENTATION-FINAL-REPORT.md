# Documentation Final Report

> **Audience:** Project owner, maintainers, auditors · **Last verified:** 2026-11-30 against the `main` branch
> **Related:** [Documentation portal](README.md), [Coverage matrix](DOCUMENTATION-COVERAGE.md), [Changelog](DOCUMENTATION-CHANGELOG.md), [Technical debt register](12-maintenance/technical-debt-register.md)

This report summarises the documentation exercise for the UEW Innovation Hub platform: what was audited and written, how it was checked, what was found, and what remains.

## Summary

The whole repository was audited before anything was written. The audit covered 169 application files under `app/`, 208 routes, 17 migrations (30 tables), 3 route files plus the API version files, configuration, the Docker and Render set-up, CI, and the PHPUnit and Playwright tests. The documentation now has 159 pages in 13 sections, plus a portal, a coverage matrix, a changelog and this report.

Each page was written from the source code. Facts that the repository cannot prove, such as Render dashboard settings or who owns the SMTP account, are labelled "Requires confirmation". Planned or advised work is labelled "Recommended, not implemented".

Two user requests during the exercise changed code, and the documentation describes the result:

- A versioned, scalable API (v1 frozen, v2 current), in commit `e68fca3`.
- Fixes for confirmed gaps found by the audit, in commit `d1296b1` and the documentation commit.

## Directories and files created

| Directory | Files |
| --- | --- |
| `docs/` (root files) | `README.md`, `DOCUMENTATION-COVERAGE.md`, `DOCUMENTATION-CHANGELOG.md`, `DOCUMENTATION-FINAL-REPORT.md` |
| `docs/01-project-overview/` | 8 |
| `docs/02-user-guide/` (including `feature-guides/`) | 20 |
| `docs/03-administration/` | 10 |
| `docs/04-architecture/` | 14 |
| `docs/05-developer-guide/` | 19 |
| `docs/06-api-reference/` | 8 |
| `docs/07-database/` | 10 |
| `docs/08-security/` | 12 |
| `docs/09-deployment-and-operations/` | 14 |
| `docs/10-testing-and-quality/` | 10 |
| `docs/11-troubleshooting/` | 10 |
| `docs/12-maintenance/` | 14 |
| `docs/13-product-and-business/` | 10 |

The pages total about 13,700 lines. The earlier documents (`docs/architecture.md`, `api.md`, `deployment.md`, `security.md`, `guides/`, `audit/`) were kept, with a note pointing to the newer sections.

## Coverage

| Area | Coverage |
| --- | --- |
| Features and workflows | Every public, portal, workspace and API feature, listed in the [feature inventory](13-product-and-business/feature-inventory.md), with user steps in [02](02-user-guide/getting-started.md) and staff procedures in [03](03-administration/administrator-overview.md) |
| Roles | All 6 roles and 20 permissions, from `app/Enums/Role.php`, in plain language and as a technical matrix with every policy |
| Architecture diagrams | 51 Mermaid diagrams: 34 flowcharts, 8 sequence diagrams, 5 state diagrams, 4 ER diagrams. They include high-level architecture, components, ER, authentication, authorisation, user workflows, API lifecycle, data flow, deployment and CI/CD |
| API | All 28 API routes (version index, 12 in v1, 15 in v2) with parameters, validation, fields, errors, rate limits, caching, CORS, deprecation and a v1-to-v2 migration table |
| Database | All 30 tables and their columns, indexes, 29 foreign keys, enum values, migrations, seeders, backup, retention and troubleshooting |
| Security | Authentication, authorisation, sessions, signed links, validation, uploads, headers, secrets, dependencies, monitoring, a STRIDE threat model and incident response, with gaps stated plainly |
| Testing | The inventory of 197 PHPUnit tests and the Playwright suites, the CI test flow, coverage gaps and a release QA checklist |
| Deployment | Local, Docker, Render (test site), production launch checklist, CI/CD, health checks, logging, backup, rollback and scaling |

## Validation performed

| Check | Result |
| --- | --- |
| Every planned file exists, no stray files | 163 of 163 present |
| Internal links and anchors (script over 171 Markdown files) | All resolve |
| Referenced source paths | All exist, apart from run-time generated files and files labelled as recommended |
| Mermaid syntax (real Mermaid 11 parser in a headless browser) | 51 of 51 parse |
| Commands | Checked against `composer.json`, `package.json`, `artisan` commands, `scripts/`, the `Dockerfile` and `ci.yml`. `php artisan list hub` and `route:list` were run |
| API docs against routes | 28 routes match `php artisan route:list --path=api`, and the API tests pass |
| Database docs against schema | Matched to a schema dump from a fresh migration run |
| Secrets | No real `.env` value, key, token or the historic tester password in any page |
| Test suite after code changes | 197 PHPUnit tests pass, and Pint passes |
| Root README links to the portal | Yes |

## Issues discovered

**Fixed during the exercise** (with tests in `tests/Feature/Workspace/OperationalFixesTest.php` and `tests/Feature/Api/ApiV2Test.php`):

- A failed broadcast stayed at "sending" forever. It is now marked failed and audited.
- Member, project and application CSV exports were not audited. They are now.
- The audit log filter offered 7 of the 18 recorded areas. It now offers all 18.
- The container allowed 5 files and 30 MB per request, but the gallery accepts 10 photos of 10 MB. The limits are now 12 files and 110 MB.
- Onboarding pre-ticked the announcement e-mail box, which is consent by default. It is now opt-in.
- Password hints said 12 characters where the rule outside production is 8. They now match the rule.
- Programme lists ran one count query per programme. They now use one subquery.
- `/up` did not check the database. It does now.
- Browser apps on other sites could not read the API's caching and rate-limit headers. They can now.
- v1 ignored `API_CACHE_SECONDS`. It now honours it.
- Smaller fixes: the Node version is pinned, a duplicate seeder check is removed, a misplaced configuration comment is moved, and `.env.example` gaps are filled.

**Still open** (canonical list with priorities in the [technical debt register](12-maintenance/technical-debt-register.md)):

- **Hosting:**
  - Render's free plan wipes uploaded files on every deploy and its database expires after 30 days.
  - The test site still uses demo data, the log mailer and the default demo password.
- **Missing features:**
  - Nothing prunes notifications, verification codes, sessions or the audit log. No scheduler exists.
  - There is no screen to restore archived records, no forced password change for new staff, and no member e-mail change, account deletion or data export.
  - Application and programme status changes are not restricted to a fixed order.
- **API:** v2 has no tombstones, so apps syncing with `updated_since` cannot see deleted or unpublished records.
- **Process:** work goes straight to `main`, which deploys automatically. There is no Dependabot, scheduled audit, static analysis or coverage report.
- **Repository:** a tester password remains in git history (commit `87df882`). Removing it needs a history rewrite approved by the owner. Changing that password removes the risk.
- **Clean-up:** leftover empty view folders, the `composer.json` name `laravel/laravel`, and the unused `.user.ini`.

## Known assumptions

- The live Render site is a test/demo site, not the production launch. This is based on `SEED_DEMO_DATA=true` in `render.yaml`.
- PostgreSQL column types follow Laravel's schema grammar. The schema dump was taken on SQLite.
- The personas, business requirements and roadmap were inferred from the code and the project notes in `.ai/`. The Hub has not reviewed them.
- The API response examples are illustrative, built from the resource classes and local data.
- The Playwright test counts are estimates read from the spec files.

## Recommended next steps

1. **Owner, before launch:** set `SEED_PASSWORD` and `BLOCK_DEFAULT_PASSWORDS=true`, configure SMTP, set `DOCUMENTS_DISK=s3` and `GALLERY_DISK=s3`, move to a paid database, set `SEARCH_INDEXING=true`, and get the Privacy and Terms pages reviewed. See [project handover](12-maintenance/project-handover.md).
2. **Owner:** turn on branch protection and Render's "wait for CI", so a red build cannot deploy.
3. **Hub:** agree a data-retention policy. Then add scheduled pruning and a member erasure procedure.
4. **Developers:** work through the High-priority items in the [technical debt register](12-maintenance/technical-debt-register.md), and add the missing tests listed in [testing-strategy](10-testing-and-quality/testing-strategy.md).
5. **Docs:** capture the screenshots in the [screenshot plan](02-user-guide/screenshot-plan.md), and answer the "Requires confirmation" items in the [coverage matrix](DOCUMENTATION-COVERAGE.md).
