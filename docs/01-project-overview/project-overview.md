# Project overview

> **Audience:** Everyone, including non-technical readers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Problem statement](problem-statement.md), [Features and capabilities](features-and-capabilities.md), [System scope](system-scope.md), [Technology stack](technology-stack.md), [Glossary](glossary.md)

The UEW Innovation Hub platform is a website and back office for the Innovation Hub at the University of Education, Winneba (UEW), Ghana. It shows the Hub's work to the public, lets students, staff, alumni and partners apply for Hub programmes online, and gives Hub staff the tools to run those programmes, publish news and report on results. It replaced an older system written in plain PHP (kept in [`legacy/`](../../legacy/) for reference).

## What it is, in plain words

**Simple explanation:** Think of it as three connected things.

1. A **public website** that anyone can read: programmes you can apply to, funded projects, publications, news, partners, an event photo gallery and a contact form.
2. A **member area** where a person who has created a free account applies to programmes, follows the decision, messages the Hub about an application and, if accepted, sends progress and budget reports.
3. A **staff workspace** where Hub staff create programmes and application forms, review applications, manage members, publish content, send announcement e-mails and see reports.

There is also a small read-only **open data API** that other systems can read.

**Technical explanation:** It is one Laravel 12 application (PHP 8.4, Blade templates, Tailwind CSS v4, Alpine.js) that serves all four surfaces from one codebase and one database. Controllers are split by audience in `app/Http/Controllers/{Site,Portal,Workspace,Api/V1,Auth}`. Business rules live in `app/Services`. Access rules live in `app/Enums/Role.php` (role to permission matrix) and `app/Policies`. See [Technology stack](technology-stack.md) for versions.

## The four surfaces

| Surface | Address | Who uses it | Sign-in needed |
| --- | --- | --- | --- |
| Public website | `/` (route names `site.*`) | Anyone | No |
| Member portal | `/portal` (`portal.*`) | Members | Yes, with a verified e-mail |
| Staff workspace | `/workspace` (`workspace.*`) | Staff | Yes, with a verified e-mail and the `workspace.access` permission |
| Open data API | `/api/v1` (`api.v1.*`) | Developers, other websites | No |

Source: [`routes/web.php`](../../routes/web.php), the output of `php artisan route:list`.

## Who it is for

| Group | Examples | What they get |
| --- | --- | --- |
| Visitors | Funders, journalists, prospective applicants | Programmes, projects, publications, news, gallery, partners |
| Members | UEW students, staff, alumni, partner staff, members of the public (`Affiliation` enum) | Programme applications, messages, progress reports, notifications, badges |
| Staff | Hub Director, Programme Officers, Reviewers, Communications Interns, Read-only Staff | Workspace tools limited by role |
| Developers | Anyone consuming public data | `/api/v1` JSON |

Roles are defined in [`app/Enums/Role.php`](../../app/Enums/Role.php). The plain-language version is in [User roles and permissions](../02-user-guide/user-roles-and-permissions.md).

## How the system came to be

The Hub first ran on a procedural PHP and MySQL application (`legacy/index.php`, `add_project.php`, `dashboard.php`, `publications_api.php` and others). It tracked projects, partner organisations and PDF reports for staff only. The rebuild (documented in [`.ai/context.md`](../../.ai/context.md)) moved it to Laravel 12 and extended it from a project register into a programme and application system with a public face. The old report and newsletter PDFs are copied into the new document store by `php artisan hub:import-documents`. Details are in [Problem statement](problem-statement.md).

## Current status

- Version 1.3.0 is recorded in [`.ai/state.md`](../../.ai/state.md). The audit handover ([`docs/audit/FINAL_PROJECT_HANDOVER_REPORT.md`](../audit/FINAL_PROJECT_HANDOVER_REPORT.md)) describes release 1.4.0 (gallery, least-privilege hardening, branded e-mails).
- A test site runs at https://uew-innovation-hub.onrender.com (Render free plan, Docker, PostgreSQL). It deploys automatically from the `main` branch of https://github.com/mhiskall282/uew-innovation-hub.
- The handover report rates the system "ready for user acceptance testing", not yet approved for production. Open owner actions are listed in [Product roadmap](../13-product-and-business/product-roadmap.md).
- 177 automated tests passed at the time of writing, plus Playwright browser tests in `tests/e2e`.

## Known limits at a glance

These are explained in [System scope](system-scope.md) and the [FAQ](frequently-asked-questions.md).

- The free Render plan puts the site to sleep after 15 minutes without traffic (`render.yaml`).
- Uploaded files and gallery photos live on the container disk by default and are lost on redeploy unless an S3-compatible bucket is configured.
- E-mail uses the `log` mailer until SMTP details are entered, so no real e-mail is sent.
- There is no scheduler. The API is read-only and unauthenticated.

## Where to go next

| If you want to... | Read |
| --- | --- |
| Understand the problem it solves | [Problem statement](problem-statement.md) |
| See every feature | [Features and capabilities](features-and-capabilities.md) |
| Use the site | [User guide](../02-user-guide/getting-started.md) |
| Run the platform | [Administration](../03-administration/administrator-overview.md) |
| Work on the code | [Developer onboarding](../05-developer-guide/developer-onboarding.md) |
