# Dashboard guide

> **Audience:** Members and new staff · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Getting started](getting-started.md), [Feature guides](feature-guides/README.md), [User roles and permissions](user-roles-and-permissions.md), [Admin dashboard](../03-administration/admin-dashboard.md)

This page describes the two dashboards: **My account** for members (`/portal`) and the staff workspace dashboard (`/workspace`). It explains each card, where its numbers come from and what to do when a card looks wrong.

## Member dashboard: My account

- **Where:** account menu under your name, **My account**, or `/portal`. The old address `/track` redirects here.
- **Who:** signed-in members with a verified e-mail and a finished profile. Others are sent to `/verify-email` or `/onboarding` first.
- **Title:** "Hello, <first name>".

### Tabs under the title

Every portal page shows the same tab bar:

| Tab | Address | Guide |
| --- | --- | --- |
| Overview | `/portal` | This page |
| My applications | `/portal/applications` | [Programme applications](feature-guides/programme-applications.md) |
| Progress reports | `/portal/reports` | [Progress reports](feature-guides/progress-reports.md) |
| Notifications | `/notifications` | [Notifications guide](notifications-guide.md) |
| Profile & settings | `/portal/profile` | [Profile and account management](profile-and-account-management.md) |

The button **Browse programmes** at the top right opens `/programmes`.

### Cards

| Card | What it shows | How it is worked out |
| --- | --- | --- |
| **In progress** | Applications waiting for a decision | Your applications with status Submitted, Under review or Shortlisted |
| **Accepted** | Number of accepted applications | Status Accepted |
| **Drafts** | Saved, not submitted | Status Draft |
| **Profile** | Percentage complete, with a bar. Click it to open your profile | 11 checks: name, phone, gender, date of birth, region, affiliation, institution, education level, bio, interests, verified e-mail |
| **Finish your drafts** (yellow box) | Appears only when you have drafts. "Drafts are not reviewed until you submit them." | Count of drafts |
| **My applications** | Your 6 most recent applications, with reference, date and status. Press **All** for the full list | Sorted by last change. A draft links back to the form, others to the details page |
| **Recommended for you** | Up to 4 open programmes you have not applied to | Programmes whose focus area matches your interests come first, then by nearest deadline |
| **Your level** | Level name, XP, progress bar and up to 6 badges | See [Badges and leaderboard](feature-guides/badges-and-leaderboard.md) |
| **Your interests** | Your chosen interests. **Edit interests** opens your profile | Profile |

If **Recommended for you** is empty and you have announcements switched off, the card says "Switch on announcements in your settings to hear when something opens."

The link under **Your level** says **Appear on the community leaderboard** if your public profile is off, and **View my public profile** if it is on.

### Top bar (all pages)

- **Bell icon:** shows the number of unread notifications. It only appears after your e-mail is verified.
- **Your name menu:** My account, My applications, Progress reports, Profile & settings, Sign out.

## Staff workspace dashboard

- **Where:** `/workspace`. Staff are sent here after sign-in. Old address `/admin` redirects here.
- **Title:** "Good morning/afternoon/evening, <first name>".
- **Who:** any staff role with the `workspace.access` permission, which is all five staff roles.

The page is the same for every role, but some cards only appear when your role holds the permission shown.

| Card | Needs permission | Hub Director | Programme Officer | Reviewer | Communications Intern | Read-only Staff |
| --- | --- | --- | --- | --- | --- | --- |
| **Projects**, **Beneficiaries**, **Funding managed** | none beyond workspace access | Yes | Yes | Yes | Yes | Yes |
| **Applications to review** (with member count when allowed) | `applications.review` | Yes | Yes | Yes | No | No |
| **Partners** (replaces the card above) | none | No | No | No | Yes | Yes |
| **Projects by focus area**, **Projects started per year** | none | Yes | Yes | Yes | Yes | Yes |
| **Recently updated projects** | none | Yes | Yes | Yes | Yes | Yes |
| **Open programmes** | `programs.manage` | Yes | Yes | No | No | No |
| **Waiting for review** | `applications.review` | Yes | Yes | Yes | No | No |
| **My drafts** (your unpublished posts) | `content.draft` | Yes | Yes | No | Yes | No |
| **Recent activity** (audit log) | `audit.view` | Yes | No | No | No | No |
| **Export portfolio** button | `reports.export` | Yes | Yes | Yes | No | No |

The member count on the review card needs `members.manage`, so only the Hub Director and the Programme Officer see it.

Figures on the first row are cached for a few minutes (`METRICS_CACHE_TTL`, default 300 seconds). A new project may take up to five minutes to show in the totals.

### The left menu

The menu is built from your permissions. Groups and items, and who sees them, are in [User roles and permissions](user-roles-and-permissions.md#what-each-staff-role-sees-in-the-workspace-menu). A number badge next to **Applications**, **Incubation board**, **Legacy grants** and **Enquiries** counts items waiting for you.

On a wide screen the sidebar can be collapsed. The site remembers your choice in the browser (local storage key `workspace.sidebar`). On a phone, open the menu button at the top left.

## Common questions

| Question | Answer |
| --- | --- |
| The Recommended card shows nothing | No open programme matches or all open programmes already have an application from you. |
| The number of drafts is wrong | A withdrawn or submitted application is no longer a draft. Open **My applications** to check each status. |
| A staff card is missing | Your role does not hold the permission. Ask the Hub Director. See the table above. |
| The totals look old | They are cached. Wait five minutes and reload. |

Staff tasks for each menu item are in the [administration section](../03-administration/administrator-overview.md).
