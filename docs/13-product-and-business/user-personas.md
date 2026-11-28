# User personas

> **Audience:** Product owners, designers, analysts · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [User stories](user-stories.md), [User roles and permissions](../02-user-guide/user-roles-and-permissions.md), [Business workflows](business-workflows.md)

These personas are **Inferred**. They are composites built from the roles in [`app/Enums/Role.php`](../../app/Enums/Role.php), the affiliations in [`app/Enums/Affiliation.php`](../../app/Enums/Affiliation.php), the legacy user types in `.ai/context.md`, and what each screen allows. The names are invented. No user research was available; validate the goals and frustrations with real users before using them for design decisions.

## Members

### Ama, final-year UEW student (`Affiliation::Student`)

| | |
| --- | --- |
| Role | `member` |
| Goal | Find a grant or competition, apply with a team idea, learn the outcome. |
| Context | Uses a phone, often on mobile data. |
| Needs from the platform | A short form that pre-fills from her profile, draft saving, a clear status, an answer when she has a question. |
| Frustrations the design addresses | Unclear deadlines (status badges show Open, Opens soon, Closed), lost progress (drafts), silence after submitting (notifications and messages). |
| Open risk | The e-mail verification code does not arrive while the mailer is `log`. |
| Main surfaces | Public programmes pages, `/portal`, `/portal/applications`, notifications |

### Dr. Mensah, UEW lecturer and project lead (`Affiliation::Staff`)

| | |
| --- | --- |
| Role | `member` |
| Goal | Win funding, then report progress and spending with minimal paperwork. |
| Needs | Upload a PDF proposal, send periodic progress and budget reports, receive reviewer feedback and resubmit. |
| Frustrations addressed | Re-typing details (profile snapshot), unclear report requirements (report form with line items). |
| Main surfaces | `/portal/applications`, `/portal/reports` |

### Kojo, alumnus or partner contact (`Affiliation::Alumni`, `Partner`)

| | |
| --- | --- |
| Role | `member` |
| Goal | Follow Hub news, join training or events, mentor teams. |
| Needs | Announcement e-mails he can switch off, a community page that shows badges (for example Mentor). |
| Main surfaces | News, community, announcement e-mails, profile preferences |

### Visitor or funder (no account)

| | |
| --- | --- |
| Goal | Judge the Hub's work: projects, partners, publications, gallery. |
| Needs | Fast public pages, downloadable reports, contact form. Optional API for data. |
| Main surfaces | Public website, `/api/v1` |

## Staff

### Hub Director (`super_admin`)

| | |
| --- | --- |
| Goal | Run the Hub, keep control of people and settings, answer to the university. |
| Needs | Role management, SMTP and site settings, audit log, reports, PDF export. |
| Frustrations addressed | Not knowing who changed what (audit log), accidental privilege (nobody can edit their own role). |
| Responsibility | The only role that can archive or delete projects, manage staff accounts, change settings and view the audit log. |

### Programme Officer (`officer`)

| | |
| --- | --- |
| Goal | Launch programmes, handle applications, keep members informed, publish content. |
| Needs | A form builder, a review queue with bulk actions, CSV export, broadcasts, news publishing, enquiries inbox, project register. |
| Main surfaces | `/workspace/programmes`, `/workspace/applications`, `/workspace/members`, `/workspace/broadcasts`, `/workspace/posts` |

### Reviewer (`reviewer`)

| | |
| --- | --- |
| Goal | Assess applications and progress reports consistently. |
| Needs | A queue filtered by status, scores and notes, applicant messages, reports. Cannot see members, settings or content tools. |
| Constraint | Does not see gender or disability data. Cannot review their own application. |

### Communications Intern (`intern`)

| | |
| --- | --- |
| Goal | Prepare news posts and gallery albums. |
| Needs | Draft and upload; an officer publishes. Edits only own unpublished drafts. |

### Read-only Staff (`viewer`)

| | |
| --- | --- |
| Goal | See the project portfolio (for example a senior colleague or auditor). |
| Needs | Workspace sign-in and project views only. |

## Technical users

### Developer or maintainer

Needs setup instructions, tests, CI, the route list and environment variables. See [Developer onboarding](../05-developer-guide/developer-onboarding.md).

### API consumer

Needs stable JSON and clear limits (60 requests per minute per IP). See [API overview](../06-api-reference/api-overview.md).

## Gaps in the persona set

No persona exists for a UEW IT administrator who would host the system, or for a data protection officer. **Proposed:** add both before production launch, since hosting and privacy are open launch items.
