# Documentation Coverage Matrix

> **Audience:** Maintainers, auditors, project managers · **Last verified:** 2026-11-30 against the `main` branch
> **Related:** [Documentation portal](README.md), [Final report](DOCUMENTATION-FINAL-REPORT.md), [Technical debt register](12-maintenance/technical-debt-register.md)

This matrix maps every feature and system area to its implementation and its documentation, and records how far each was verified. Use it to find a page for a piece of code, and to see what still needs work.

## Status vocabulary

| Status | Meaning |
| --- | --- |
| Documented and verified | Written from the source code, and the behaviour was also confirmed by a test run, a command or a route listing |
| Documented but partially verified | Written from the source code; some facts (hosting, live data, UI wording) could not be confirmed |
| Partially documented | Covered, but some aspects are missing (listed in the row) |
| Not implemented | The capability does not exist. The documentation says so and does not describe it as working |
| Requires clarification | The owner or the Hub must answer a question before the documentation can be completed |

## Public website and member portal

| Feature or area | Implementation | Documentation | Status | Verification | Missing information | Outstanding work |
| --- | --- | --- | --- | --- | --- | --- |
| Public pages (home, projects, publications, news, partners, programmes, CMS pages) | `app/Http/Controllers/Site/`, `resources/views/site/` | [browsing-the-public-website](02-user-guide/feature-guides/browsing-the-public-website.md), [feature-inventory](13-product-and-business/feature-inventory.md) | Documented and verified | Route list; `PublicSiteTest`, `RouteSmokeTest` pass | None | Screenshots ([plan](02-user-guide/screenshot-plan.md)) |
| Event gallery (public) | `app/Http/Controllers/Site/GalleryController.php`, `app/Models/AlbumPhoto.php` | [gallery](02-user-guide/feature-guides/gallery.md) | Documented and verified | `GalleryTest` passes | None | None |
| Registration and e-mail verification (code and signed link) | `app/Http/Controllers/Auth/`, `app/Services/VerificationService.php` | [account-registration](02-user-guide/account-registration.md), [authentication-architecture](04-architecture/authentication-architecture.md) | Documented and verified | `MemberAccountTest`, `EmailLinksTest` pass | None | Members cannot change their e-mail address (TD register) |
| Sign-in, lockout, password reset | `app/Http/Requests/Auth/LoginRequest.php`, `app/Http/Controllers/Auth/PasswordResetController.php` | [login-and-authentication](02-user-guide/login-and-authentication.md), [authentication-security](08-security/authentication-security.md) | Documented and verified | `AuthenticationTest`, `SecurityHardeningTest` pass | Live `SESSION_LIFETIME` value | No 2FA (documented as a gap) |
| Onboarding and profile, demographics | `app/Http/Controllers/Portal/OnboardingController.php`, `app/Support/Demographics.php` | [profile-and-account-management](02-user-guide/profile-and-account-management.md) | Documented and verified | Tests pass; opt-in fix covered by `OperationalFixesTest` | None | Account deletion and data export do not exist |
| Programme applications (drafts, uploads, withdraw) | `app/Http/Controllers/Portal/ApplicationController.php`, `app/Services/ApplicationService.php`, `app/Support/FormSchema.php` | [programme-applications](02-user-guide/feature-guides/programme-applications.md) | Documented but partially verified | `EngagementTest` passes | No automated test for withdraw, capacity and closing date | Add tests ([testing gaps](10-testing-and-quality/testing-strategy.md)) |
| Application messages | `app/Services/ConversationService.php` | [application-messages](02-user-guide/feature-guides/application-messages.md) | Documented and verified | `EngagementTest` passes | None | Staff replies notify in-app only, never by e-mail |
| Progress and budget reports | `app/Http/Controllers/Portal/ProgressReportController.php`, `app/Services/ProgressReportService.php` | [progress-reports](02-user-guide/feature-guides/progress-reports.md) | Documented and verified | Tests pass | None | No due dates or reminders (not implemented) |
| Notifications bell | `app/Http/Controllers/NotificationController.php`, `app/Services/NotificationCenter.php` | [notifications-guide](02-user-guide/notifications-guide.md) | Documented and verified | `EngagementTest` passes | None | No pruning of old notifications |
| Badges, XP and leaderboard | `app/Services/EngagementService.php`, `app/Enums/AchievementType.php` | [badges-and-leaderboard](02-user-guide/feature-guides/badges-and-leaderboard.md) | Documented and verified | Tests pass | None | None |
| Contact form | `app/Http/Controllers/Site/ContactController.php`, `app/Services/ContactMessageService.php` | [contact-and-enquiries](02-user-guide/feature-guides/contact-and-enquiries.md) | Documented and verified | Tests pass | None | None |
| One-click unsubscribe | `app/Http/Controllers/Site/UnsubscribeController.php` | [email-preferences-and-unsubscribe](02-user-guide/feature-guides/email-preferences-and-unsubscribe.md), [webhooks](06-api-reference/webhooks.md) | Documented and verified | `EmailLinksTest` passes | None | None |

## Staff workspace and administration

| Feature or area | Implementation | Documentation | Status | Verification | Missing information | Outstanding work |
| --- | --- | --- | --- | --- | --- | --- |
| Workspace dashboard and reports (PDF, date ranges) | `app/Http/Controllers/Workspace/DashboardController.php`, `app/Http/Controllers/Workspace/ReportController.php`, `app/Services/InsightsService.php` | [admin-dashboard](03-administration/admin-dashboard.md) | Documented and verified | `ReportExportTest` passes | None | Dashboard figures have no direct test |
| CSV exports (members, projects, applications) | `app/Http/Controllers/Workspace/MemberController.php`, `app/Services/ReportExportService.php`, `app/Services/ApplicationService.php` | [data-management](03-administration/data-management.md) | Documented and verified | `OperationalFixesTest` (exports audited, viewers denied) | None | None |
| Staff accounts and roles | `app/Http/Controllers/Workspace/UserController.php`, `app/Services/UserService.php` | [user-management](03-administration/user-management.md) | Documented and verified | `UserManagementTest`, `AdministrationTest` pass | None | No forced password change at first sign-in |
| Roles and permissions page, permission matrix | `app/Enums/Role.php`, `app/Enums/Permission.php`, `app/Http/Controllers/Workspace/RoleController.php` | [authorization-architecture](04-architecture/authorization-architecture.md), [role-and-permission-management](03-administration/role-and-permission-management.md), [user-roles-and-permissions](02-user-guide/user-roles-and-permissions.md) | Documented and verified | `AccessControlTest`, `PrivilegesTest` (144 role/route pairs) pass | None | `PrivilegesTest` asserts at least 120 pairs, not the exact 144 |
| Programmes and form builder | `app/Http/Controllers/Workspace/ProgramController.php`, `app/Services/ProgramService.php` | [content-management](03-administration/content-management.md), [administrative-workflows](03-administration/administrative-workflows.md) | Documented and verified | Tests pass | None | Programme status transitions are not enforced (TD register) |
| Application review and bulk decisions | `app/Http/Controllers/Workspace/ApplicationController.php` | [administrative-workflows](03-administration/administrative-workflows.md) | Documented but partially verified | Review path tested | No test for bulk decisions | Add test |
| Legacy grant applications | `app/Http/Controllers/Workspace/GrantApplicationController.php`, `app/Services/GrantApplicationService.php` | [administrative-workflows](03-administration/administrative-workflows.md) | Documented and verified | `GrantReviewTest` passes | None | Nothing creates new rows (review only) |
| Members and badge awards | `app/Http/Controllers/Workspace/MemberController.php`, `app/Http/Controllers/Workspace/AchievementController.php` | [user-management](03-administration/user-management.md) | Documented and verified | Tests pass | None | Member erasure needs manual SQL |
| Broadcasts | `app/Services/BroadcastService.php`, `app/Jobs/SendBroadcast.php` | [content-management](03-administration/content-management.md), [business-workflows](13-product-and-business/business-workflows.md) | Documented and verified | `OperationalFixesTest` covers the failed state | None | One try per job; failed broadcasts are not retried automatically |
| News and posts, website pages | `app/Services/PostService.php`, `app/Http/Controllers/Workspace/PageController.php` | [content-management](03-administration/content-management.md) | Documented and verified | `ContentPublishingTest` passes | None | None |
| Gallery albums (workspace) | `app/Http/Controllers/Workspace/AlbumController.php`, `app/Services/GalleryService.php`, `app/Policies/AlbumPolicy.php` | [content-management](03-administration/content-management.md) | Documented and verified | `GalleryTest` passes | None | Photos are lost on Render redeploy until `GALLERY_DISK=s3` |
| Publications and partners | `app/Services/PublicationService.php`, `app/Http/Controllers/Workspace/OrganizationController.php` | [content-management](03-administration/content-management.md) | Documented and verified | `ProjectManagementTest`, `DocumentAccessTest` pass | None | None |
| Site settings, SMTP and e-mail templates | `app/Http/Controllers/Workspace/SettingsController.php`, `app/Services/MailSettings.php`, `app/Support/EmailTemplates.php` | [system-configuration](03-administration/system-configuration.md) | Documented but partially verified | `AdministrationTest` passes | Live SMTP is not set up (log mailer) | No test for the template editor |
| Audit log | `app/Services/AuditLogger.php`, `app/Http/Controllers/Workspace/AuditLogController.php` | [audit-and-monitoring](03-administration/audit-and-monitoring.md), [security-monitoring](08-security/security-monitoring.md) | Documented and verified | `OperationalFixesTest` covers the area filter | None | No retention or pruning policy |
| Artisan commands (`hub:create-admin`, `hub:import-documents`, `hub:secure-demo-accounts`) | `app/Console/Commands/` | [administrator-overview](03-administration/administrator-overview.md), [seed-data](07-database/seed-data.md) | Documented and verified | `php artisan list hub`; `SecureDemoAccountsTest` passes | None | No test for `hub:import-documents` |

## Open data API

| Feature or area | Implementation | Documentation | Status | Verification | Missing information | Outstanding work |
| --- | --- | --- | --- | --- | --- | --- |
| Version index and versioning policy | `routes/api.php`, `config/api.php`, `app/Http/Middleware/ApiVersion.php`, `app/Http/Controllers/Api/VersionsController.php` | [api-overview](06-api-reference/api-overview.md), [deprecation-policy](12-maintenance/deprecation-policy.md) | Documented and verified | `ApiV2Test` (deprecation and sunset headers) passes | None | None |
| API v1 (frozen) | `routes/api/v1.php`, `app/Http/Controllers/Api/V1/`, `app/Http/Resources/Api/V1/` | [endpoints](06-api-reference/endpoints.md) | Documented and verified | `PublicApiTest` passes; 12 routes listed | None | Contract snapshot tests (recommended) |
| API v2 (current) | `routes/api/v2.php`, `app/Http/Controllers/Api/V2/`, `app/Http/Resources/Api/V2/` | [endpoints](06-api-reference/endpoints.md), [request-response-examples](06-api-reference/request-response-examples.md) | Documented and verified | `ApiV2Test` (cursor paging, `updated_since`, ETag/304, error codes, query count) passes; 15 routes listed | Examples are illustrative, not captured from production | No tombstones for deleted records |
| Errors, rate limits, CORS, caching | `bootstrap/app.php`, `app/Providers/AppServiceProvider.php`, `config/cors.php` | [error-codes](06-api-reference/error-codes.md), [rate-limiting](06-api-reference/rate-limiting.md) | Documented and verified | `ApiV2Test` CORS and cache tests pass | None | None |
| API authentication | Not implemented (public, read-only API) | [authentication](06-api-reference/authentication.md) | Not implemented | Route middleware has no `auth` | Whether authenticated partner access is needed | Decide before building (Sanctum is recommended) |
| Webhooks | Not implemented | [webhooks](06-api-reference/webhooks.md) | Not implemented | No webhook code exists | Whether partners need push updates | None until requested |
| OpenAPI specification | Not implemented | [api-overview](06-api-reference/api-overview.md) | Not implemented | No spec file in the repository | None | Optional: generate from the v2 resources |

## Platform, data, security and operations

| Feature or area | Implementation | Documentation | Status | Verification | Missing information | Outstanding work |
| --- | --- | --- | --- | --- | --- | --- |
| Architecture and request lifecycle | `bootstrap/app.php`, `routes/web.php`, `app/` | [04-architecture](04-architecture/system-architecture.md) | Documented and verified | 51 Mermaid diagrams parsed with Mermaid 11 | Rejected alternatives for most decisions were never recorded | None |
| Database schema (30 tables) | `database/migrations/` | [tables-and-columns](07-database/tables-and-columns.md), [entity-relationships](07-database/entity-relationships.md) | Documented and verified | Schema dumped from a fresh SQLite migration | PostgreSQL column types not dumped | Indexes for some foreign keys and `updated_at` (TD register) |
| Seeders and demo data | `database/seeders/` | [seed-data](07-database/seed-data.md) | Documented and verified | Code read; `SecureDemoAccountsTest` passes | None | None |
| Backup and restore | Not automated in code | [backup-and-restore](07-database/backup-and-restore.md), [backup-and-recovery](09-deployment-and-operations/backup-and-recovery.md) | Documented but partially verified | Commands follow PostgreSQL and SQLite tools | Whether Render takes backups on the current plan | Owner to confirm the plan and schedule backups |
| Data retention | Soft deletes and expiries in models; no pruning | [data-retention](07-database/data-retention.md) | Partially documented | Code read; `routes/console.php` has no schedule | The Hub's retention policy | Hub to set a policy; then add scheduled pruning |
| Security controls | `app/Http/Middleware/SecurityHeaders.php`, `app/Policies/`, `app/Http/Requests/` | [08-security](08-security/security-overview.md) | Documented and verified | Security tests pass; `scripts/security-scan.ps1` documented | None | Gaps listed in [threat-model](08-security/threat-model.md) |
| Secrets management | `.env.example`, `render.yaml` | [secrets-management](08-security/secrets-management.md) | Documented and verified | Docs scanned for real `.env` values: none found | None | Tester password in git history (commit `87df882`), removal needs owner approval |
| Environment variables | `.env.example`, `config/`, `docker/entrypoint.sh` | [environment-configuration](09-deployment-and-operations/environment-configuration.md) | Documented and verified | Every variable cross-checked with `config/` | None | None |
| Docker image and entrypoint | `Dockerfile`, `docker/` | [docker-guide](09-deployment-and-operations/docker-guide.md) | Documented but partially verified | CI "Docker image builds" job passes | The image is built in CI but never started there | Optional CI smoke run |
| Render hosting | `render.yaml` | [production-deployment](09-deployment-and-operations/production-deployment.md), [infrastructure-overview](09-deployment-and-operations/infrastructure-overview.md) | Documented but partially verified | Live `/`, `/gallery` and `/up` return 200 | Render dashboard settings ("wait for CI", backups) | Owner actions in [project-handover](12-maintenance/project-handover.md) |
| Vercel entry | `vercel.json`, `api/index.php` | [deployment-overview](09-deployment-and-operations/deployment-overview.md) | Requires clarification | Files read | Whether it has ever been deployed | Decide whether to keep or remove it |
| Staging environment | Not implemented | [staging-deployment](09-deployment-and-operations/staging-deployment.md) | Not implemented | No staging service in `render.yaml` | None | Recommended: a second Render service |
| CI/CD | `.github/workflows/ci.yml` | [ci-cd-pipeline](09-deployment-and-operations/ci-cd-pipeline.md) | Documented and verified | All 7 jobs green on the latest pushes | Whether branch protection is on | Recommended: Dependabot, scheduled audit |
| Health check | `bootstrap/app.php` (`/up`), `app/Providers/AppServiceProvider.php` | [health-checks](09-deployment-and-operations/health-checks.md) | Documented and verified | `ApiV2Test::test_health_check_queries_the_database` | None | The queue is not checked |
| Logging and monitoring | `config/logging.php` | [logging-and-monitoring](09-deployment-and-operations/logging-and-monitoring.md) | Partially documented | Config read | Which monitoring service, if any, the Hub will use | No uptime alerts or error tracking (gap) |
| Scheduled jobs | `routes/console.php` (none) | [architectural-decisions](04-architecture/architectural-decisions.md) | Not implemented | File read | None | Add pruning jobs after a retention policy exists |
| Testing infrastructure | `tests/`, `phpunit.xml`, `playwright.config.ts` | [10-testing-and-quality](10-testing-and-quality/testing-strategy.md), [testing-guide](05-developer-guide/testing-guide.md) | Documented and verified | 197 PHPUnit tests pass locally | Exact Playwright test count (estimated) | Coverage gaps listed per page |
| Legacy system | `legacy/`, `reports/`, `publications/` | [project-structure](05-developer-guide/project-structure.md), [problem-statement](01-project-overview/problem-statement.md) | Requires clarification | Folder read; never served | How legacy MySQL data was migrated | Hub to confirm the data migration record |
| Product requirements and roadmap | Not in code | [13-product-and-business](13-product-and-business/product-overview.md) | Requires clarification | Requirements labelled Implemented, Inferred or Proposed | No Hub requirements document or user research | Hub to review the inferred personas and roadmap |

## Categories judged not applicable

| Category | Reason |
| --- | --- |
| Webhook payload reference | No webhooks exist. [webhooks.md](06-api-reference/webhooks.md) explains this and lists the only machine-to-machine POST (unsubscribe). |
| API authentication guide (tokens, OAuth) | The API is public by design. [authentication.md](06-api-reference/authentication.md) explains what is excluded and the recommended path if private endpoints are needed. |
| Mobile app documentation | No mobile app exists. [api-integrations.md](06-api-reference/api-integrations.md) explains how an app should use the API. |
