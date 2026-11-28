# Administrator Overview

> **Audience:** Hub Director, Programme Officers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Admin dashboard](admin-dashboard.md), [Role and permission management](role-and-permission-management.md), [Authorization architecture](../04-architecture/authorization-architecture.md), [Staff guide](../guides/staff-guide.md)

This page explains what "administering" the UEW Innovation Hub means in practice, who can do what, and where each administrative task lives in the staff workspace. Read it first, then follow the links to the task pages.

## The two kinds of administrator

The application has no separate "admin panel". Everyone who runs the Hub signs in to the **staff workspace** at `/workspace` (route names `workspace.*`). What a person sees there depends on their role.

| Role (label in the UI) | Code value | Typical job |
| --- | --- | --- |
| Hub Director | `super_admin` | Owns the whole system: staff accounts, site settings, e-mail, audit log. |
| Programme Officer | `officer` | Runs programmes, applications, members, broadcasts, projects, website content and enquiries. |
| Reviewer | `reviewer` | Reviews applications, progress reports and legacy grants; sees reports. |
| Communications Intern | `intern` | Drafts news posts and gallery albums. |
| Read-only Staff | `viewer` | Views the project portfolio and partner list. |

Source: [`app/Enums/Role.php`](../../app/Enums/Role.php). The full matrix is in [authorization architecture](../04-architecture/authorization-architecture.md) and, in plain language, in [user roles and permissions](../02-user-guide/user-roles-and-permissions.md). It is not repeated here.

## What only the Hub Director can do

These four permissions are held by `super_admin` alone. Programme Officers do not have them.

| Permission | What it unlocks | Sidebar item |
| --- | --- | --- |
| `users.manage` | Create, edit, deactivate and delete staff accounts; send staff password-reset e-mails; view the Roles page | Staff accounts, Roles & permissions |
| `settings.manage` | Site settings, SMTP settings, e-mail templates | Site settings, E-mail templates |
| `audit.view` | Read the audit log and the "Recent activity" card on the dashboard | Audit log |
| `projects.delete` | Archive projects | Delete button on a project |

A Programme Officer therefore cannot add staff, change the SMTP server, edit e-mail wording, read the audit log or archive a project. If an officer reports a missing menu item, check this table before looking for a bug.

> **Simple explanation:** Officers run the Hub's programmes and content. Only the Hub Director controls who has access and how the system behaves.
>
> **Technical explanation:** Each permission is registered as a Laravel Gate in `AppServiceProvider::registerPermissionGates()`. Routes use `can:<permission>` middleware, and the sidebar in `resources/views/components/layouts/workspace.blade.php` hides items the user cannot open. Policies in `app/Policies/*` add object-level rules, for example a drafter can only edit their own unpublished posts.

## Where each task lives

The sidebar groups below are built from the menu array in `resources/views/components/layouts/workspace.blade.php`. Items are filtered by permission.

| Sidebar label | Permission | Page in this guide |
| --- | --- | --- |
| Dashboard | `workspace.access` | [Admin dashboard](admin-dashboard.md) |
| Reports | `reports.view` | [Admin dashboard](admin-dashboard.md#reports-page) |
| Programmes, Applications, Incubation board, Legacy grants | `programs.manage`, `applications.review`, `grants.review` | [Content management](content-management.md), [Administrative workflows](administrative-workflows.md) |
| Members | `members.manage` | [User management](user-management.md#members) |
| Broadcasts | `broadcasts.send` | [Content management](content-management.md#broadcasts) |
| Enquiries | `messages.manage` | [Content management](content-management.md#contact-enquiries) |
| Projects, Partners, Publications | `projects.view`, `publications.manage` | [Data management](data-management.md) |
| News & posts, Gallery, Pages | `content.draft`, `pages.manage` | [Content management](content-management.md) |
| Staff accounts, Roles & permissions | `users.manage` | [User management](user-management.md), [Role and permission management](role-and-permission-management.md) |
| Site settings, E-mail templates | `settings.manage` | [System configuration](system-configuration.md) |
| Audit log | `audit.view` | [Audit and monitoring](audit-and-monitoring.md) |

Badges next to Applications, Incubation board, Legacy grants and Enquiries show how many items are waiting.

## Command-line administration

Three artisan commands exist for the person with server access. They are documented in [system configuration](system-configuration.md#command-line-tasks) and [data management](data-management.md#importing-legacy-documents).

| Command | Purpose |
| --- | --- |
| `php artisan hub:create-admin` | Create the first Hub Director if none exists |
| `php artisan hub:secure-demo-accounts` | Replace well-known default passwords with `SEED_PASSWORD` |
| `php artisan hub:import-documents` | Copy legacy PDFs onto the documents disk |

## Safeguards built into the system

- The last active Hub Director cannot be demoted, deactivated or deleted (`UserService::guardLastAdministrator`).
- Nobody can change their own role, and nobody can deactivate or delete their own account (`UserService::update`, `UserPolicy::deactivate`).
- Deactivating an account ends its sessions at once, because `EnsureAccountIsActive` runs on every request.
- Administrators can trigger a password-reset e-mail but never see or set another person's password for members. For staff, the Hub Director sets the first password at creation time.
- Every privileged change is written to the audit log with the actor's user ID and IP address, taken server-side.

## Things to know before you start

1. **The live site is a test deployment.** `render.yaml` sets `SEED_DEMO_DATA=true` and `MAIL_MAILER=log`. Until SMTP is configured, no e-mail leaves the server. See [system configuration](system-configuration.md#smtp-and-e-mail-delivery).
2. **Uploads can disappear.** On Render's free plan the container disk is wiped on every deploy. Documents and gallery photos need S3-compatible storage. See [data management](data-management.md#storage-and-durability).
3. **There is no scheduler.** `routes/console.php` holds only Laravel's default `inspire` command. Scheduled news posts go live because visibility is computed from `published_at` when the page is read.
4. **E-mail needs a queue worker.** Broadcasts, application status e-mails and password-change notices are queued. `docker/entrypoint.sh` starts a worker inside the web container unless `RUN_QUEUE_WORKER=false`. Verification and reset codes are sent synchronously and do not need the worker.

## Next steps

- New Hub Director: read [administrative workflows](administrative-workflows.md) for the first-day checklist.
- Something not working: see [admin troubleshooting](admin-troubleshooting.md) and [troubleshooting overview](../11-troubleshooting/troubleshooting-overview.md).
