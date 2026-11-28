# Features and capabilities

> **Audience:** Non-technical readers, then everyone · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Feature inventory (with routes)](../13-product-and-business/feature-inventory.md), [User guide](../02-user-guide/getting-started.md), [System scope](system-scope.md)

This page describes what people can do on the platform, grouped by who does it. Route-level detail is in the [feature inventory](../13-product-and-business/feature-inventory.md).

## Public website (no account)

| Capability | Notes |
| --- | --- |
| Browse programmes | Grants, fellowships, incubation, competitions, training and events (`ProgramType`). Shows Open, Opens soon or Closed. |
| Browse projects | Public projects only; filter by focus area and search. Internal projects are hidden. |
| Read and download publications | PDF downloads; a download count is kept. Filter by type. |
| Read news, blog posts and announcements | Only published posts. Scheduled posts appear once their date passes. |
| See partners | Partner contact e-mails are not shown. |
| Community page | A leaderboard of members who opted in, with levels and badges. |
| Photo gallery | Published albums only. |
| Contact form | Stored for staff to answer. Rate limited and bot-filtered. |
| Site pages | About, FAQ, Privacy, Terms and other pages edited by staff (`{page}` route). |
| Search engine files | `/sitemap.xml` and `/robots.txt`. Indexing is off unless `SEARCH_INDEXING=true`. |

## Accounts

- Create a free account, then confirm the e-mail with a six-digit code or a signed link. Codes expire after 10 minutes, allow 5 attempts and can be resent after 60 seconds (`VerificationService`).
- Complete a short onboarding profile (affiliation, institution, interests, demographic details used to pre-fill forms).
- Reset a forgotten password by code or link. The reset page does not reveal whether an account exists.
- Sign-in locks after repeated failures, per e-mail and IP and per account.
- Registration can be paused by the Hub Director (`registration_open` setting).

## Member portal

| Capability | Notes |
| --- | --- |
| Apply to a programme | Custom form per programme; save as draft or submit; PDF uploads allowed on file fields. Answers pre-fill from the profile. |
| Track applications | Statuses: Draft, Submitted, Under review, Shortlisted, Accepted, Not selected, Withdrawn. |
| Withdraw | Allowed while Draft, Submitted or Under review. |
| Message the Hub | One conversation thread per application, both directions. |
| Progress and budget reports | Only for accepted applications. Totals are recomputed on the server from line items. Editable again only if staff request changes. |
| Notifications | Bell and inbox inside the site. |
| Badges and XP | Points for submitting applications (10) and reports (20); staff award badges (Winner, Finalist, Participant, Alumni, Mentor, Recognition). Levels run from Newcomer to Hub Legend. |
| Profile | Edit details, change password, choose announcement e-mails, opt in to the community page. |
| Unsubscribe | One-click link in every announcement e-mail. |

## Staff workspace

| Area | What staff do | Roles (see [roles page](../02-user-guide/user-roles-and-permissions.md)) |
| --- | --- | --- |
| Dashboard | Counts and recent activity | All staff |
| Programmes | Create, edit, publish, close, archive; build the application form; announce to members | Officer, Hub Director |
| Applications | Review, score, decide, add a message to the applicant, bulk decide, export CSV, download uploads | Officer, Reviewer, Hub Director |
| Progress reports | Accept or request changes with feedback | Officer, Reviewer, Hub Director |
| Members | List, export, activate or suspend, award badges, send reset or verification e-mail | Officer, Hub Director |
| Broadcasts | Draft and send announcement e-mails to opted-in, verified members | Officer, Hub Director |
| Enquiries | Read and answer contact messages | Officer, Hub Director |
| News and gallery | Draft (interns included); publish (officers) | Intern drafts; Officer, Hub Director publish |
| Pages | Edit About, FAQ and other pages (Markdown, sanitised) | Officer, Hub Director |
| Projects, partners, publications | Maintain the portfolio; export CSV | View: all staff; manage: Officer, Hub Director; archive or delete projects: Hub Director only |
| Legacy grant applications | Review old-style grant applications | Officer, Reviewer, Hub Director |
| Reports | Date-range insights and a PDF export | Officer, Reviewer, Hub Director |
| Staff accounts and roles | Create staff, change roles, reset passwords, view the permission matrix | Hub Director |
| Site settings, SMTP, e-mail templates | Branding text, contact details, SMTP with test e-mail, template editing | Hub Director |
| Audit log | Searchable record of actions | Hub Director |

## Open data API

Read-only JSON under `/api/v1`: `stats`, `projects`, `publications`, `news`, `partners`, `programmes`, `community`. Paginated (up to 50 per page), cached for 60 seconds, limited to 60 requests per minute per IP. See [API overview](../06-api-reference/api-overview.md).

## Cross-cutting capabilities

- **Audit log** of sign-ins, applications, decisions, content changes and exports.
- **Security controls:** CSRF protection, security headers with a strict content security policy, signed e-mail links, hashed one-time codes, encrypted stored SMTP password, spreadsheet-formula neutralising in CSV exports.
- **Accessibility:** reduced-motion support, skip link, axe checks with no violations on 30+ screens (audit report).
- **Image handling:** gallery photos are re-encoded and resized; metadata is stripped; EXIF orientation is applied.

## Capabilities the platform does not have

Online payments, single sign-on, staff multi-factor authentication, a mobile app, API keys, write API endpoints, webhooks, self-service account deletion and scheduled jobs are not implemented. See [System scope](system-scope.md).
