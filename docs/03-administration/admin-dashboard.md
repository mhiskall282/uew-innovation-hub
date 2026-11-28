# Admin Dashboard and Reports

> **Audience:** Hub Director, Programme Officers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Administrator overview](administrator-overview.md), [Audit and monitoring](audit-and-monitoring.md), [Data management](data-management.md), [API endpoints](../06-api-reference/endpoints.md)

This page describes the workspace dashboard (`/workspace`), the Reports page (`/workspace/reports`), the PDF report and the CSV exports. Code: [`DashboardController`](../../app/Http/Controllers/Workspace/DashboardController.php), [`ReportController`](../../app/Http/Controllers/Workspace/ReportController.php), [`InsightsService`](../../app/Services/InsightsService.php), [`ReportExportService`](../../app/Services/ReportExportService.php), [`DateRange`](../../app/Support/DateRange.php).

## Dashboard

Route `workspace.dashboard`, open to everyone with `workspace.access`. Each card appears only when the person holds the permission shown.

| Card or counter | Needs permission | Source |
| --- | --- | --- |
| Export button in the page header | `reports.export` | `workspace.projects.export` |
| Projects, Beneficiaries, Funding managed | everyone | `MetricsService::workspaceStats()` |
| Applications to review (counter links to the queue), Partners | `applications.review` | `MetricsService::openApplicationCount()`, `workspaceStats()` |
| Projects by focus area, Projects started per year | everyone | `workspaceStats()` |
| Recently updated projects (6 latest) | everyone | `Project::latest('updated_at')` |
| Open programmes (up to 5, by closing date) | `programs.manage` | `Program::open()` |
| Waiting for review (5 oldest open applications) | `applications.review` | `ProgramApplication` with open status |
| My drafts | `content.draft` | your unpublished posts |
| Recent activity (8 latest audit entries) | `audit.view` | `ActivityLog` |

Totals are cached. `METRICS_CACHE_TTL` (default 300 seconds, `config/hub.php`) controls how stale the portfolio numbers can be. The open-applications count is cached for 60 seconds. If you change a project and the dashboard figure does not move, wait for the cache to expire. See [performance problems](../11-troubleshooting/performance-problems.md).

## Reports page

Route `workspace.reports.index`. Needs `reports.view` (Hub Director, Programme Officer, Reviewer).

### Date ranges

Pick a preset or a custom range. Presets come from `DateRange::PRESETS`.

| Preset key | Label | Covers |
| --- | --- | --- |
| `30d` | Last 30 days | today and the 29 days before |
| `90d` | Last 90 days | today and the 89 days before |
| `12m` | Last 12 months | start of the month 11 months ago, to today (default) |
| `ytd` | This year | 1 January to today |
| `last_year` | Last year | previous calendar year |
| `all` | All time | from 2015-01-01 to today |
| `custom` | Custom range | `from` and `to` as `YYYY-MM-DD` |

Rules worth knowing:

- Custom dates earlier than 2015-01-01 are moved up to that date. Dates in the future are moved back to today.
- A reversed range (`from` after `to`), an invalid date such as `2026-02-31`, or a missing end date silently falls back to Last 12 months. The page does not show an error.
- The monthly growth chart shows at most the last 24 months.

### What each figure means

`InsightsService::build()` defines the period rules:

| Figure | Counted when |
| --- | --- |
| Applications | submitted in the period (drafts excluded) |
| Approved, acceptance rate | the decision was made in the period; rate is approved divided by approved plus rejected |
| New members, verified new | account created in the period |
| Gender, region, age band, affiliation, interests | members who joined in the period |
| Budget received, spent, people reached | progress reports whose reporting period ended in the period |
| Most active members | XP earned in the period; for All time, the stored running total |

Demographics are returned as totals only. No report lists an individual's gender, age or region.

Report sections (`ReportController::SECTIONS`): Programmes and applications, Membership and interests, Inclusion (anonymous), Incubation budgets, Most active members.

## PDF report

Route `workspace.reports.pdf`, throttled to 10 requests per minute per user.

1. On the Reports page choose the range and the sections to include. If you tick none, all five sections are included.
2. Press the PDF button. The file downloads as `uew-innovation-hub-report-<from>-<to>.pdf` (for example `20251201-20261124`).
3. The PDF is built by `barryvdh/laravel-dompdf` from `resources/views/workspace/reports/pdf.blade.php`, A4, with remote resources and PHP in the template disabled.
4. Each export writes an audit entry `report.exported` with the range and sections.

If the PDF fails with a memory or time-out error, shorten the range or select fewer sections. See [backend problems](../11-troubleshooting/backend-problems.md).

## CSV exports

| Export | Route | Permission | Contents |
| --- | --- | --- | --- |
| Projects | `workspace.projects.export` | `reports.export` | Portfolio with the Projects page filters applied, plus a totals row |
| Members | `workspace.members.export` | `members.manage` | Name, e-mail, phone, gender, age, region, education, affiliation, institution, department, interests, announcements opt-in, verified, joined date; honours the Members filters |
| Programme applications | `workspace.programs.export` | `applications.review` | Every submitted application for one programme, including form answers (file fields excluded) |

All three stream the file in chunks of 500 rows, start with a UTF-8 byte-order mark so Excel reads accents correctly, and prefix cells that start with `=`, `+`, `-` or `@` so spreadsheets cannot run them as formulas.

> **Warning:** The member and application exports contain personal data covered by the Data Protection Act, 2012 (Act 843). Store downloads securely and delete them when finished. The members and applications exports are not written to the audit log. Only the PDF export is. This is a gap; see [audit and monitoring](audit-and-monitoring.md#what-is-not-logged).

## Dashboard and report problems

| Symptom | Likely cause |
| --- | --- |
| Reports menu item missing | Role lacks `reports.view` (Communications Intern, Read-only Staff) |
| Dashboard has no Export button | Role lacks `reports.export` (Communications Intern, Read-only Staff) |
| Figures lag behind recent edits | Metrics cache; wait up to `METRICS_CACHE_TTL` seconds |
| Chosen custom range ignored | Dates reversed, incomplete or invalid; the page used Last 12 months |
| PDF button returns 429 | More than 10 PDF requests in a minute |
