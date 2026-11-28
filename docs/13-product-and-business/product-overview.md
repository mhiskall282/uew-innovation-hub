# Product overview

> **Audience:** Product owners, sponsors, business analysts · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Project overview](../01-project-overview/project-overview.md), [Business requirements](business-requirements.md), [Feature inventory](feature-inventory.md), [Product roadmap](product-roadmap.md)

This page describes the platform as a product: what value it offers, to whom, and where it stands. The technical view is in [System architecture](../04-architecture/system-architecture.md).

## Evidence labels used in this section

| Label | Meaning |
| --- | --- |
| **Implemented (verified in `<file>`)** | I read the file and the behaviour is there. |
| **Inferred** | The code suggests it, or the intent is implied by existing docs, but I did not verify the behaviour end to end. |
| **Proposed** | Not in the code. A suggestion for the owner to accept or reject. |

## Product statement

For UEW students, staff, alumni and partners who want Hub support, and for the Hub team that gives it, the UEW Innovation Hub platform is a web application that publishes the Hub's work, takes and reviews applications to programmes, and tracks funded work through reports. Unlike the legacy project register, it supports applicants as well as staff. (Implemented, verified in `app/Services/ApplicationService.php`, `app/Services/ProgressReportService.php`.)

## Value by audience

| Audience | Value | Status |
| --- | --- | --- |
| Hub Director | One place to see programmes, people, decisions and the audit log; controls roles and settings | Implemented (verified in `app/Enums/Role.php`, `routes/web.php`) |
| Programme Officer | Builds programmes and forms, runs review, mails members, publishes content | Implemented (verified in `Workspace\ProgramController`, `BroadcastService`) |
| Reviewer | A review queue with scores, notes, bulk decisions; no access to members or settings | Implemented (verified in `Role::permissions()`) |
| Communications Intern | Can draft news and albums safely | Implemented (verified in `PostPolicy`, `AlbumPolicy`) |
| Member | Clear application status, messages and reports in one place | Implemented (verified in `Portal\*` controllers) |
| Visitor or funder | A public view of programmes, projects, publications and gallery | Implemented (verified in `Site\*` controllers) |
| Developer | Public JSON for stats, projects, publications, news, partners, programmes, community | Implemented (verified in `Api\V1\IndexController`) |

## Product modules

Taken from [`.ai/state.md`](../../.ai/state.md) and checked against the controllers.

| Module | Main code | Status |
| --- | --- | --- |
| Public site | `Site\*Controller` | Implemented |
| Accounts (sign-up, code verification, onboarding, reset) | `Auth\*`, `MemberService`, `VerificationService` | Implemented |
| Programmes and form builder | `ProgramService`, `FormSchema` | Implemented |
| Applications | `ApplicationService`, `ProgramApplicationPolicy` | Implemented |
| Notifications | `NotificationCenter`, `NotificationController` | Implemented |
| Application messages | `ConversationService` | Implemented |
| Progress and budget reports | `ProgressReportService` | Implemented |
| Badges, XP, community | `EngagementService`, `Site\CommunityController` | Implemented |
| Members, broadcasts, pages, templates, SMTP settings | `MemberController`, `BroadcastService`, `PageController`, `EmailTemplateController`, `SettingsController` | Implemented |
| Portfolio (projects, publications, partners, news) | `ProjectService`, `PublicationService`, `PostService` | Implemented |
| Gallery | `GalleryService`, `Workspace\AlbumController` | Implemented |
| Legacy grant review | `GrantApplicationService` | Implemented (review only) |
| Reports | `Workspace\ReportController`, `InsightsService` | Implemented |
| Open API v1 | `Api\V1\*` | Implemented |

## Product state

- Release: 1.3.0 in `.ai/state.md`; 1.4.0 features (gallery, least-privilege checks, branded e-mail) in the handover report.
- Quality evidence: 177 PHPUnit tests, Playwright browser tests (Chromium, Firefox, WebKit, mobile), CI with seven jobs (see [CI/CD pipeline](../09-deployment-and-operations/ci-cd-pipeline.md)).
- Readiness: "ready for user acceptance testing" but not approved for production (handover report). Open launch items are in the [roadmap](product-roadmap.md).

## Constraints the product owner should know

| Constraint | Effect | Source |
| --- | --- | --- |
| Test deployment on Render free plan | Sleeps when idle, database expires after 30 days, files lost on redeploy | `render.yaml` |
| E-mail set to `log` | Sign-up codes and notifications by e-mail are not delivered | `render.yaml` |
| Demo data enabled on the test site | Sample accounts and content exist; must be disabled for launch | `render.yaml` (`SEED_DEMO_DATA=true`) |
| No scheduler | No timed jobs, reminders or automatic closing outside what is computed at read time | `routes/console.php` |
| Read-only API without authentication | Fine for public data; no partner integrations that write | `routes/web.php`, `Api\V1\*` |

## Related product pages

[Business requirements](business-requirements.md) · [Functional requirements](functional-requirements.md) · [Non-functional requirements](non-functional-requirements.md) · [User personas](user-personas.md) · [User stories](user-stories.md) · [Business workflows](business-workflows.md) · [Feature inventory](feature-inventory.md) · [Acceptance criteria](acceptance-criteria.md) · [Product roadmap](product-roadmap.md)
