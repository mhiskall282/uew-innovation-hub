# Role and Permission Management

> **Audience:** Hub Director · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Authorization architecture](../04-architecture/authorization-architecture.md), [User roles and permissions](../02-user-guide/user-roles-and-permissions.md), [User management](user-management.md), [Authorization security](../08-security/authorization-security.md)

Roles and permissions are fixed in code. The *Roles & permissions* page shows them but cannot change them. This page explains what you can and cannot do, how to read the page, and how admin permissions differ from ordinary staff and member permissions.

## The Roles & permissions page

Route `workspace.roles.index` (`/workspace/roles`), needs `users.manage`. Code: [`RoleController`](../../app/Http/Controllers/Workspace/RoleController.php), view `resources/views/workspace/roles/index.blade.php`.

The page is read-only. It lists every role with its description, the number of **active** accounts in that role, and a grid of permissions grouped as Workspace, Portfolio & reports, Programmes, People, Website and Administration. It reads straight from `Role::permissions()` and `Permission::group()`, so it always matches what the code enforces.

## What you can change

| Task | Where | Notes |
| --- | --- | --- |
| Move a person to another staff role | Staff accounts > Edit | Not your own role. Not the last Hub Director |
| Give someone staff access | Staff accounts > New account | Chooses one of five staff roles |
| Remove access | Staff accounts > Deactivate | Immediate |

## What you cannot change in the workspace

- The list of permissions a role holds.
- Creating a new role or a custom permission set.
- Giving one person one extra permission.

> **Recommended, not implemented:** a per-user override or editable roles. Today, changing what a role may do means editing [`app/Enums/Role.php`](../../app/Enums/Role.php) and deploying. Add a test in `tests/` for any change, because the matrix is the only place permissions are defined.

## Admin permissions compared with ordinary permissions

Three layers decide whether an action is allowed.

| Layer | Question | Where |
| --- | --- | --- |
| Account state | Is the person signed in, active and e-mail-verified? | middleware `auth`, `active`, `verified.email` |
| Permission | Does the role hold the named permission? | `can:<permission>` on the route, `@can` in views |
| Policy | May this person act on this particular record? | `app/Policies/*` |

Short summary of the permission tiers (full matrix in [authorization architecture](../04-architecture/authorization-architecture.md)):

| Tier | Permissions | Roles |
| --- | --- | --- |
| Member | none. Members have no workspace permission and use `/portal` | `member` |
| Ordinary staff | `workspace.access`, `projects.view`, plus read or draft rights | `viewer`, `intern`, `reviewer` |
| Programme administration | `programs.manage`, `applications.review`, `members.manage`, `broadcasts.send`, `content.publish`, `pages.manage`, `publications.manage`, `partners.manage`, `messages.manage`, `projects.manage` | `officer` (and Hub Director) |
| System administration | `users.manage`, `settings.manage`, `audit.view`, `projects.delete` | `super_admin` only |

Every permission has a Gate with the same name, so the same check applies on the route, in the menu and in code.

### Object-level rules that go beyond the role

| Rule | Policy |
| --- | --- |
| A drafter (`content.draft`) can edit or delete only their own unpublished posts. Publishers (`content.publish`) can edit any post | `PostPolicy` |
| A drafter can edit an album only while it is unpublished. Only publishers can delete an album | `AlbumPolicy` |
| A reviewer cannot review their own progress report | `ProgressReportPolicy::review` |
| A staff account cannot be deactivated or deleted by itself | `UserPolicy::deactivate` |
| Staff-account actions apply to staff only. Members are handled under Members | `UserPolicy::update` |

### Permission names are not the same as menu labels

| Permission | Label shown on the Roles page |
| --- | --- |
| `projects.delete` | Archive or delete projects |
| `reports.export` | Export portfolio data (CSV) |
| `reports.view` | View reports and export PDF reports |
| `applications.review` | Review applications, message applicants, review progress reports |
| `content.publish` | Publish news and gallery albums |
| `settings.manage` | Change site settings, e-mail (SMTP) and templates |

## Inconsistencies found

- The *Partners* sidebar item is shown to anyone with `projects.view`, including Read-only Staff, who can open the list but not add or edit partners (`OrganizationPolicy` needs `partners.manage` for changes). This matches the policy but may surprise viewers.
- The *Legacy grants* and *Partners* routes carry only `can:workspace.access` in `routes/web.php`. Access is enforced by the policies, so it is safe, but `route:list` alone does not show the real requirement.

## Checking who has what

```powershell
# List routes with their middleware (read-only)
php artisan route:list --path=workspace --columns=method,uri,name,middleware
```

```bash
php artisan route:list --path=workspace
```

For an on-screen answer, open **Roles & permissions** and read the active-account count per role. To list staff by role, use **Staff accounts** and the role filter.
