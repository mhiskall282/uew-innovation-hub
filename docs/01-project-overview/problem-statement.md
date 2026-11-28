# Problem statement

> **Audience:** Non-technical readers, sponsors, new team members · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Project overview](project-overview.md), [Project objectives](project-objectives.md), [Business requirements](../13-product-and-business/business-requirements.md)

This page explains what was wrong with how the Innovation Hub worked before, and what the platform changes. The history comes from [`.ai/context.md`](../../.ai/context.md) and the code in [`legacy/`](../../legacy/). Statements about the old system describe what the old files do, not interviews with staff.

## Background

The UEW Innovation Hub supports innovation, research and enterprise at the University of Education, Winneba. The lead directorate is the Directorate of Research, Innovation and Development (DRID), according to `.ai/context.md`. Its work is to fund and support projects by researchers, students and partners.

## The old system

The first system was a set of procedural PHP scripts on MySQL (`legacy/index.php`, `add_project.php`, `edit_project.php`, `delete_project.php`, `dashboard.php`, `export.php`, `print.php`, `publications_api.php`, `file.php`, `home.php`, `login.php`, `users.php`). It could:

- record projects with partner organisations, beneficiaries and a PDF report;
- sign in two kinds of staff user (`admin` and `viewer`);
- show a dashboard, export to Excel or CSV, and print a list;
- store newsletters and publications;
- show a public landing page.

## Problems

| Problem | Evidence | Effect on the Hub |
| --- | --- | --- |
| It was a project register for staff, not a way to take applications. | No application, applicant or review files in `legacy/`. | Calls for grants, fellowships and training had to be run outside the system (for example by e-mail). **Requires confirmation:** how the Hub ran calls before this platform. |
| Code, SQL and HTML were mixed in single files. | `legacy/index.php`, `legacy/dashboard.php`. | Hard to change safely, hard to test. |
| Only two roles. | `legacy/auth.php`, `legacy/users.php`. | Interns, reviewers and officers could not be given narrower rights. |
| Backup copies of scripts sat next to live ones. | `legacy/add_project_backup.php`, `edit_project_backup2.php`, `index_backup2.php`. | No version control discipline, risk of running the wrong file. |
| No automated tests or build checks. | No test folder in `legacy/`. | Changes could break things unnoticed. |
| Applicants had no account or status view. | No member concept in `legacy/`. | Applicants could not see where an application stood. |
| No audit trail of who changed what. | No log table in the legacy scripts. | Staff actions could not be traced. |

## What the platform changes

| Need | How the platform meets it | Where |
| --- | --- | --- |
| Take applications online | Staff build a programme with its own application form; members apply, save drafts and submit. | `ProgramService`, `ApplicationService`, `FormSchema` |
| Give applicants visibility | Status, decision message, in-app notifications and a message thread per application. | `ConversationService`, `NotificationCenter` |
| Track what happens after funding | Accepted applicants send progress and budget reports; staff accept them or ask for changes. | `ProgressReportService` |
| Separate duties | Six roles and 20 permissions; interns can draft but not publish. | `app/Enums/Role.php`, `Permission.php`, `PostPolicy` |
| Show the Hub's work | Public pages for programmes, projects, publications, news, partners, gallery and a community page. | `app/Http/Controllers/Site` |
| Keep an audit trail | Every important action writes an audit log entry. | `AuditLogger`, `activity_logs` table |
| Keep old records | `projects` and `publications` have a `legacy_id` column. `DemoContentSeeder` creates sample projects from the legacy data. PDFs are copied by `hub:import-documents`. | `database/seeders/DemoContentSeeder.php`, `app/Console/Commands/ImportLegacyDocuments.php` |
| Make changes safe | Layered code, 177 PHPUnit tests, browser tests and CI. | `tests/`, `.github/workflows/ci.yml` |

> **Requires confirmation:** whether every legacy database row was migrated into the new database. The repository contains the import command for PDF files. I found no command that reads rows from the legacy MySQL database; the only legacy-derived data is the sample content in `DemoContentSeeder`. Check with the project owner before relying on "zero data loss" (a goal stated in `AGENTS.md`).

## What the platform does not try to solve

- It does not pay out grants or hold financial accounts. Budget figures in progress reports are self-reported by members.
- It does not replace UEW's student or staff systems. Accounts are separate; there is no single sign-on.
- It does not give outside systems write access. The API is read-only.

See [System scope](system-scope.md) for the full list.
