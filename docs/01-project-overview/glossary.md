# Glossary

> **Audience:** Everyone · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Project overview](project-overview.md), [User roles and permissions](../02-user-guide/user-roles-and-permissions.md), [Tables and columns](../07-database/tables-and-columns.md)

Terms used across these docs. Prose uses British spelling ("programme"). Code uses the identifier shown in backticks ("program"). Enum values come from [`app/Enums`](../../app/Enums/).

## Core terms

| Term | Meaning |
| --- | --- |
| Public website | Pages under `/` (route names `site.*`). No sign-in. |
| Member portal | `/portal` (`portal.*`). For people with the `member` role. |
| Staff workspace | `/workspace` (`workspace.*`). For staff roles. |
| Open data API | `/api/v1` (`api.v1.*`). Read-only JSON. |
| Member | A public account holder (`Role::Member`). |
| Staff | Any account whose role is not `member`. |
| Hub Director | The `super_admin` role. Full access. |
| Programme Officer | The `officer` role. Runs programmes, members, content. |
| Reviewer | The `reviewer` role. Reviews applications and sees reports. |
| Communications Intern | The `intern` role. Drafts news and gallery albums; cannot publish. |
| Read-only Staff | The `viewer` role. Views the project portfolio only. |
| Programme | A `programs` record: a grant, fellowship, incubation, competition, training or event that people can apply to. |
| Application | A member's application to a programme (`program_applications`). Reference format `UEW-AP-<year>-<code>`. |
| Legacy grant application | A record in `grant_applications` from the old system. Staff can review it; nobody creates new ones through the site. |
| Progress report | A report (`progress_reports`) from an accepted team on milestones and budget. |
| Album / photo | Gallery records (`albums`, `album_photos`). |
| Broadcast | An announcement e-mail sent to members (`broadcasts`). |
| Audit log | The `activity_logs` table, shown to the Hub Director at `/workspace/audit`. |

## Programme types (`ProgramType`)

`grant`, `fellowship`, `incubation`, `competition`, `training`, `event`.

## Programme statuses (`ProgramStatus`)

| Value | Meaning |
| --- | --- |
| `draft` | Being prepared. Not visible to the public. |
| `published` | Visible. Accepts applications only inside its window (see below). |
| `closed` | Visible, no longer accepts applications. |
| `archived` | Hidden from the public and not accepting applications. |

The word "Open" on the site is not a stored status. It is computed (`Program::availability()`): published, already opened, not yet closed. "Opens soon" means published with a future `opens_at`.

## Application statuses (`ApplicationStatus`)

| Value | Label shown | Meaning |
| --- | --- | --- |
| `draft` | Draft | Saved, not sent. Editable. |
| `submitted` | Submitted | Sent, waiting for review. |
| `under_review` | Under review | A panel is assessing it. |
| `shortlisted` | Shortlisted | Passed a first round. |
| `approved` | Accepted | Accepted. Allows progress reports. |
| `rejected` | Not selected | Declined. |
| `withdrawn` | Withdrawn | Withdrawn by the member. |

## Other status and type lists

| Enum | Values |
| --- | --- |
| `ReportStatus` (progress reports) | `submitted` (Awaiting review), `accepted`, `changes_requested` |
| `BroadcastStatus` | `draft`, `sending`, `sent` |
| `ContactStatus` (enquiries) | `new`, `read`, `replied`, `archived` |
| `GrantStatus` (legacy grants) | `submitted`, `under_review`, `shortlisted`, `approved` (Approved), `rejected` (Not funded) |
| `PostType` | `news`, `blog`, `announcement` |
| `ProjectStatus` | `pending`, `active`, `completed`, `suspended` |
| `AchievementType` (badges) | `winner` (300 XP), `finalist` (150), `participant` (50), `alumni` (100), `mentor` (120), `custom` "Recognition" (50) |
| `Affiliation` | `student` (UEW student), `staff` (UEW staff or faculty), `alumni` (UEW alumni), `partner` (Partner organisation), `external` (Member of the public) |

## Domain terms

| Term | Meaning |
| --- | --- |
| Focus area | Research theme on a project or programme. Configured in `config/hub.php`: STEM, Educational Tech, Agriculture, Inclusive Education, Digital Humanities, Community Outreach. |
| Organisation | A partner record (`organizations`). Linked to projects with a role such as Funding Partner or Research Partner. Called "partner" on the public site. |
| Publication | A PDF document such as a research paper, policy brief, newsletter, technical report or annual report. |
| Form schema | The list of questions staff define for a programme's application form (`App\Support\FormSchema`). Up to 40 fields. |
| Applicant snapshot | A copy of the member's profile details stored when an application is submitted (`applicant_snapshot`). |
| Capacity | Optional limit on submitted applications. A programme with capacity reached stops accepting more. |
| Decision message | Text a reviewer writes for the applicant when setting a status. |
| Onboarding | The one-time profile form after the first e-mail verification. |
| XP | Experience points. 10 for submitting an application, 20 for a first report, plus badge points. |
| Level | Newcomer (0 XP), Explorer (50), Builder (150), Innovator (400), Trailblazer (800), Hub Legend (1500). |
| Leaderboard / community page | Public list of active members who turned on a public profile and have more than 0 XP (`User::scopeOnLeaderboard`). |
| Notification | An in-app message (`HubNotification`), shown in the bell and inbox. |
| One-time code | Six-digit code sent by e-mail. Valid 10 minutes, 5 attempts. |
| Signed link | An e-mail link with a tamper-proof signature (verification, password reset, unsubscribe). |
| Permission | A named ability such as `applications.review`. Defined in `Permission`. |
| Gate / policy | Laravel's way of checking a permission or a rule on one record. |
| Queue | List of background jobs (sending e-mail). Stored in the database; a worker runs in the container. |
| Mailer `log` | Writes e-mail to the log file instead of sending it. |
| Demo data | Sample accounts and content created by `DemoContentSeeder` when `SEED_DEMO_DATA=true`. |
| DRID | Directorate of Research, Innovation and Development, the lead directorate named in `.ai/context.md`. |
| UEW | University of Education, Winneba. |
| Legacy system | The old procedural PHP and MySQL application in `legacy/`. |
