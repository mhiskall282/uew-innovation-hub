# Project objectives

> **Audience:** Sponsors, managers, new team members · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Problem statement](problem-statement.md), [Business requirements](../13-product-and-business/business-requirements.md), [Product roadmap](../13-product-and-business/product-roadmap.md)

This page lists what the platform is meant to achieve and whether the code meets each objective today. Objectives come from [`.ai/context.md`](../../.ai/context.md), [`.ai/product.md`](../../.ai/product.md) and `AGENTS.md`. The status column is based on the code, not on the intent.

Status labels: **Met** (verified in the file named), **Partly met**, **Not met**.

| # | Objective | Status | Evidence |
| --- | --- | --- | --- |
| 1 | Present the Hub's work publicly | Met | `app/Http/Controllers/Site/*`, `resources/views/site/*` |
| 2 | Let people apply to programmes online | Met | `Portal\ApplicationController`, `ApplicationService`, `FormSchema` |
| 3 | Let staff review applications fairly and record decisions | Met | `Workspace\ApplicationController`, `ApplicationService::review()` and `bulkReview()` |
| 4 | Keep applicants informed | Partly met | In-app notifications work. E-mail is sent only when a mailer other than `log` is configured (`render.yaml` sets `MAIL_MAILER=log`). |
| 5 | Follow funded work through progress and budget reports | Met | `ProgressReportService`, `Portal\ProgressReportController` |
| 6 | Give each staff member only the access they need | Met | `Role::permissions()`, `tests/Feature/Workspace/PrivilegesTest.php` |
| 7 | Keep an audit trail of important actions | Met | `AuditLogger`, `Workspace\AuditLogController` (Hub Director only) |
| 8 | Publish news safely (draft, then publish) | Met | `PostService`, `PostPolicy`, `tests/Feature/Workspace/ContentPublishingTest.php` |
| 9 | Share open data with other systems | Partly met | Read-only API exists. No authentication, no write endpoints, no webhooks. |
| 10 | Protect personal data and uploads | Partly met | Strict validation, signed links, private document streaming, security headers. Legal review of Privacy and Terms is still pending ([handover report](../audit/FINAL_PROJECT_HANDOVER_REPORT.md)). No self-service account deletion. |
| 11 | Keep old records and files from the legacy system | Partly met | PDF import command exists. See the confirmation note in [Problem statement](problem-statement.md). |
| 12 | Be easy to change safely | Met | Layered code, 177 PHPUnit tests, CI on SQLite and PostgreSQL, Pint style check |
| 13 | Be usable on phones and by people using assistive technology | Met (as tested) | Playwright responsive and axe tests in `tests/e2e`. No screen-reader session was done (audit report). |
| 14 | Run at low cost for a test or pilot | Met | `render.yaml` free web service and free PostgreSQL |
| 15 | Be ready for production launch | Not met | Open owner actions: strong `SEED_PASSWORD`, SMTP, paid or Neon database, S3 storage, `SEED_DEMO_DATA=false`, legal review ([roadmap](../13-product-and-business/product-roadmap.md)). |

## Brand and experience objectives

`AGENTS.md` asks that the platform keep the UEW colours (navy `#102a72`, royal blue `#3157d5`, purple `#6d28d9`, gold `#f59e0b`) and the Innovation Hub crest, in a dense dashboard style. The colours are defined in `resources/css/app.css` and the layouts under `resources/views/components/layouts`. Judging visual quality is outside the scope of these docs.

## How success could be measured

Recommended, not implemented: the code has no analytics or service-level targets. The dashboards show counts (applications, members, projects) through `MetricsService` and `InsightsService`. Suggested measures a Hub could track using those figures:

- applications submitted per programme and the share decided within an agreed number of days;
- share of accepted teams that file a progress report on time;
- member sign-ups and verified-e-mail rate;
- time from intern draft to published news post.

None of these targets exists in the code. They would need to be agreed with the Hub.
