# User roles and permissions

> **Audience:** Members and staff, non-technical · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Authorization architecture](../04-architecture/authorization-architecture.md), [Role and permission management](../03-administration/role-and-permission-management.md), [Dashboard guide](dashboard-guide.md), [User management](../03-administration/user-management.md)

This page explains, in plain language, what each kind of account can do on the UEW Innovation Hub site. The source of truth is [`Role.php`](../../app/Enums/Role.php) and [`Permission.php`](../../app/Enums/Permission.php). The technical version of this page is [Authorization architecture](../04-architecture/authorization-architecture.md).

## Member or staff

**Simple explanation:** a **member** is a person from outside the Hub who signs up to apply for programmes. **Staff** are people who work for the Hub and run it. Staff have a role that decides which parts of the staff workspace they can open.

**Technical explanation:** there are six roles. `member` is the public role. The other five are staff roles. Each staff role holds a fixed list of permissions defined in code. A permission is checked on the route and again inside the action.

| | Member | Staff |
| --- | --- | --- |
| How the account is created | The person signs up at `/sign-up` | The Hub Director creates it in the workspace |
| E-mail verification | 6-digit code, then profile page | Already verified. No profile page |
| Where they work | Member portal, `/portal` | Staff workspace, `/workspace` |
| Can apply to programmes | Yes | No |
| Can see the staff workspace | No (403 "You do not have access") | Yes |

Staff can still read the public website and use the notification bell.

## The six roles

| Role (label on screen) | Code name | Who it is for | In one sentence |
| --- | --- | --- | --- |
| Member | `member` | Students, alumni, partners, the public | Public account used to apply for programmes |
| Hub Director | `super_admin` | Head of the Hub | Full access, including staff accounts, site settings, e-mail and the audit log |
| Programme Officer | `officer` | Day-to-day managers | Runs programmes and applications, members, broadcasts, projects, content and enquiries |
| Reviewer | `reviewer` | Review panel members | Reviews programme and grant applications and sees reports |
| Communications Intern | `intern` | Content helpers | Drafts news and blog posts for an officer to publish |
| Read-only Staff | `viewer` | Observers | Read-only access to the project portfolio |

## What each role can do

A tick means the role holds the permission. Members hold none, because they do not use the workspace. Their abilities (apply, message, report) come from owning the application, not from a permission.

| Permission (what it lets you do) | Code | Hub Director | Programme Officer | Reviewer | Communications Intern | Read-only Staff |
| --- | --- | :-: | :-: | :-: | :-: | :-: |
| Sign in to the staff workspace | `workspace.access` | ✓ | ✓ | ✓ | ✓ | ✓ |
| View the project portfolio | `projects.view` | ✓ | ✓ | ✓ | ✓ | ✓ |
| Create and edit projects | `projects.manage` | ✓ | ✓ | | | |
| Archive or delete projects | `projects.delete` | ✓ | | | | |
| Export portfolio data (CSV) | `reports.export` | ✓ | ✓ | ✓ | | |
| View reports and export PDF reports | `reports.view` | ✓ | ✓ | ✓ | | |
| Review legacy grant applications | `grants.review` | ✓ | ✓ | ✓ | | |
| Create programmes and application forms | `programs.manage` | ✓ | ✓ | | | |
| Review applications, message applicants, review progress reports | `applications.review` | ✓ | ✓ | ✓ | | |
| Manage members, award badges, export member data | `members.manage` | ✓ | ✓ | | | |
| Send announcement e-mails to members | `broadcasts.send` | ✓ | ✓ | | | |
| Draft news posts and gallery albums | `content.draft` | ✓ | ✓ | | ✓ | |
| Publish news and gallery albums | `content.publish` | ✓ | ✓ | | | |
| Edit website pages (About, FAQ, Privacy...) | `pages.manage` | ✓ | ✓ | | | |
| Manage publications | `publications.manage` | ✓ | ✓ | | | |
| Manage partner organisations | `partners.manage` | ✓ | ✓ | | | |
| Read and answer contact enquiries | `messages.manage` | ✓ | ✓ | | | |
| Manage staff accounts and roles | `users.manage` | ✓ | | | | |
| Change site settings, e-mail (SMTP) and templates | `settings.manage` | ✓ | | | | |
| View the audit log | `audit.view` | ✓ | | | | |

An account that is switched off (inactive) holds no permissions at all, whatever its role.

## What each staff role sees in the workspace menu

The left menu hides every item you cannot use. The **Needs** column gives the permission each item requires. Match it with the table above.

| Menu group | Item | Needs |
| --- | --- | --- |
| Overview | Dashboard | `workspace.access` |
| Overview | Reports | `reports.view` |
| Programmes | Programmes | `programs.manage` |
| Programmes | Applications | `applications.review` |
| Programmes | Incubation board | `applications.review` |
| Programmes | Legacy grants | `grants.review` |
| People | Members | `members.manage` |
| People | Broadcasts | `broadcasts.send` |
| People | Enquiries | `messages.manage` |
| Portfolio | Projects | `projects.view` |
| Portfolio | Partners | `projects.view` |
| Portfolio | Publications | `publications.manage` |
| Website | News & posts | `content.draft` |
| Website | Gallery | `content.draft` |
| Website | Pages | `pages.manage` |
| Administration | Staff accounts | `users.manage` |
| Administration | Roles & permissions | `users.manage` |
| Administration | Site settings | `settings.manage` |
| Administration | E-mail templates | `settings.manage` |
| Administration | Audit log | `audit.view` |
| You | Account settings | `workspace.access` |

Staff roles that only draft content (the Communications Intern) can write a post, but a Programme Officer or the Hub Director must publish it.

## Rules nobody can bypass

- **Nobody can change their own role.** If you try to, the site says "You cannot change your own role. Ask another Hub Director." This stops people raising their own access, and stops a Hub Director locking themselves out by accident.
- **Role and active status are not part of any form a member fills in.** A member cannot become staff by editing a form. The member sign-up always creates the `member` role.
- **Only the Hub Director manages staff accounts**, and only staff accounts. Members are never listed under **Staff accounts**.
- **The last active Hub Director is protected.** The site refuses to demote, deactivate or delete them: "This is the only active Hub Director. Assign another Hub Director first." and "The only active Hub Director cannot be deactivated."
- **A Hub Director cannot deactivate or delete their own account.**
- **A member sees only their own** applications, messages and reports. Reviewers see submitted applications, never other people's drafts, and cannot review an application they made themselves.
- **A deactivated account is signed out at the next click** and sees "Your account has been deactivated. Contact the Hub Director if this is unexpected."

## What a member can do

| Action | Condition |
| --- | --- |
| Read the public website | Always |
| Apply to a programme, save a draft | Verified e-mail, finished profile, programme open |
| Upload PDF answers | Part of an application |
| Withdraw an application | Status Submitted or Under review |
| Message the Hub about an application | Application submitted |
| Send a progress report | Application accepted |
| Edit own profile and password | Always |
| See other members' details | Never. The public community page shows only members who switched it on, and only their name, affiliation, institution, bio, XP and badges |

## How to ask for a change

Members cannot be promoted to staff from the member portal. To get a staff account, ask the Hub Director. To change a staff role, the Hub Director edits it under **Staff accounts**. See [User management](../03-administration/user-management.md).

## If you see "You do not have access"

Your role does not hold the permission for that page. This is the 403 page. Go back, or ask the Hub Director if you think you need access.
