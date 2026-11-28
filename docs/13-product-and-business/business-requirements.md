# Business requirements

> **Audience:** Product owners, sponsors, analysts · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Project objectives](../01-project-overview/project-objectives.md), [Functional requirements](functional-requirements.md), [Non-functional requirements](non-functional-requirements.md), [Product roadmap](product-roadmap.md)

Business requirements say what the Hub needs the platform to achieve. Each has an ID (BR-n) and one of three labels: **Implemented (verified in `<file>`)**, **Inferred** or **Proposed**. No stakeholder interviews were available, so requirements are reconstructed from `.ai/context.md`, `.ai/product.md`, the legacy code and the current features. Treat "Inferred" ones as questions to confirm with the Hub.

## Requirements

| ID | Business requirement | Label |
| --- | --- | --- |
| BR-1 | The Hub must present its programmes, projects, publications, news and partners publicly. | Implemented (verified in `routes/web.php`, `app/Http/Controllers/Site`) |
| BR-2 | People outside the Hub must be able to apply to programmes online, without paper or e-mail attachments. | Implemented (verified in `Portal\ApplicationController`, `FormSchema`) |
| BR-3 | Each programme must be able to ask its own questions and collect PDF documents. | Implemented (verified in `app/Support/FormSchema.php`, 12 field types including `file`) |
| BR-4 | A programme must be able to have an opening date, a closing date and a capacity. | Implemented (verified in `app/Models/Program.php`: `opens_at`, `closes_at`, `capacity`) |
| BR-5 | Reviewers must be able to score, note, and decide applications, singly or in bulk, and tell the applicant. | Implemented (verified in `ApplicationService::review()`, `bulkReview()`) |
| BR-6 | Reviewers' private notes must not be shown to applicants. Implemented (verified in `resources/views/workspace/applications/show.blade.php`; no portal view references `internal_notes`) |
| BR-7 | Applicants must be able to see the status of each application and talk to the Hub about it. | Implemented (verified in `ConversationService`, `portal/applications/show.blade.php`) |
| BR-8 | Accepted teams must report progress and spending, and the Hub must be able to accept the report or ask for changes. | Implemented (verified in `ProgressReportService`) |
| BR-9 | The Hub must see funding against reported spending per programme. | Implemented (verified in `ProgressReportService::budgetByProgramme()`) |
| BR-10 | Staff must have different levels of access, and no one may raise their own. | Implemented (verified in `Role::permissions()`, `PrivilegesTest`) |
| BR-11 | Junior staff must be able to prepare news without publishing it. | Implemented (verified in `PostService`, `PostPolicy`) |
| BR-12 | Every important action must be traceable to a person and a time. | Implemented (verified in `app/Services/AuditLogger.php`) |
| BR-13 | The Hub must be able to announce new programmes to interested members, and members must be able to opt out. | Implemented (verified in `ProgramService::announce()`, `BroadcastService`, `SendBroadcast`) |
| BR-14 | The Hub must keep a register of projects with partners, beneficiaries and report PDFs, as the legacy system did. | Implemented (verified in `Workspace\ProjectController`, `OrganizationController`) |
| BR-15 | The Hub must be able to export data for donors and committees. | Implemented as CSV and PDF (verified in `ReportExportService`, `ReportController::pdf`). Excel was a legacy feature and is not present. |
| BR-16 | Members must be recognised for participation (badges and levels). | Implemented (verified in `EngagementService`, `AchievementType`) |
| BR-17 | Hub staff must be able to answer enquiries from the public. | Implemented (verified in `ContactMessageService`, `Workspace\MessageController`) |
| BR-18 | Site text (About, FAQ, Privacy, Terms, home banner, contact details) must be editable without a developer. | Implemented (verified in `Workspace\PageController`, `SettingsService`) |
| BR-19 | Personal data must be handled lawfully, and a privacy notice must be published. | Partly implemented. Privacy text exists in `PageSeeder`; legal review is pending (handover report). |
| BR-20 | Old records and PDF files from the legacy system must remain available. | Partly implemented (`hub:import-documents` copies PDFs; `legacy_id` columns exist). See the confirmation note in [Problem statement](../01-project-overview/problem-statement.md). |
| BR-21 | The service must be available when applicants need it, for example before a programme deadline. | Not met on the test site (free plan sleeps). **Proposed:** paid hosting for launch. |
| BR-22 | Members must receive e-mail for verification and decisions. | Implemented in code; not active on the test site (`MAIL_MAILER=log`). |
| BR-23 | Staff accounts should be protected beyond a password. | **Proposed:** multi-factor authentication (audit finding F-12). Needs working e-mail first. |
| BR-24 | Members should be able to delete their own account and export their data. | **Proposed** (audit finding F-15). Today it is done on request by e-mail. |
| BR-25 | Programme deadlines should trigger reminders. | **Proposed.** No scheduler exists. |
| BR-26 | Partner systems should be able to receive Hub data through API keys or webhooks. | **Proposed.** Not implemented. |
| BR-27 | The Hub should be able to take legacy-style grant applications through the new site. | Inferred as not needed: new calls use programmes. Confirm with the Hub whether `grant_applications` should be retired. |

## Business rules found in code

| Rule | Where |
| --- | --- |
| Only members with a verified e-mail and a completed profile can reach the portal. | `routes/web.php` middleware `verified.email`, `onboarded` |
| An application can be edited only while it is a draft. | `ProgramApplication::isEditable()` |
| A member can have one application per programme. | Unique index `program_applications_program_id_user_id_unique` (a schema dump of a fresh migration run (see [tables-and-columns](../07-database/tables-and-columns.md))); `ApplicationService::forMember()` reuses the existing record. |
| A programme with capacity stops accepting applications when submitted ones reach it (drafts and withdrawn do not count). | `Program::isFull()` |
| Only accepted applications can file progress reports. | `ProgramApplication::canReportProgress()` |
| Only reports in "changes requested" can be edited. | `ProgressReport::isEditable()` |
| Declining a legacy grant application needs a note. | `ReviewGrantApplicationRequest` |
| Broadcasts go only to active, verified members who opted in. | `BroadcastService::recipients()` |
| Interns' publish requests are ignored. | `PostService::publishDate()` |
| A bulk decision covers at most 500 applications and skips drafts and withdrawn ones. | `Workspace\ApplicationController::bulk()`, `ApplicationService::bulkReview()` |
