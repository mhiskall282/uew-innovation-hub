# Feature inventory

> **Audience:** Product owners, testers, developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Features and capabilities](../01-project-overview/features-and-capabilities.md), [Functional requirements](functional-requirements.md), [Endpoints](../06-api-reference/endpoints.md), [Authorization architecture](../04-architecture/authorization-architecture.md)

Every user-facing feature, with its surface, who can use it, the route names and the controller. Route names and controller actions come from the output of `php artisan route:list` and `routes.json`. Roles come from [`app/Enums/Role.php`](../../app/Enums/Role.php). All features below are **Implemented** unless the Status column says otherwise.

Role abbreviations: **HD** Hub Director, **PO** Programme Officer, **RV** Reviewer, **IN** Communications Intern, **RO** Read-only Staff, **M** Member, **All staff** = HD, PO, RV, IN, RO. "Public" means no sign-in. Permission in brackets is the gate (`can:` middleware or policy).

Controllers are in `app/Http/Controllers/<Surface>`: `Site`, `Portal`, `Workspace`, `Api\V1`, `Auth`.

## Public website

| Feature | Surface | Roles | Route names | Controller | Status |
| --- | --- | --- | --- | --- | --- |
| Home page | Public | Public | `site.home` | `Site\HomeController` | Live |
| Programme list and detail | Public | Public | `site.programs.index`, `site.programs.show` | `Site\ProgramController` | Live |
| Project list and detail | Public | Public | `site.projects.index`, `site.projects.show` | `Site\ProjectController` | Live; internal projects hidden |
| Publications list | Public | Public | `site.publications.index` | `Site\PublicationController` | Live |
| News list and article | Public | Public | `site.news.index`, `site.news.show` | `Site\NewsController` | Live; `/news-and-blogs` redirects |
| Partners | Public | Public | `site.partners.index` | `Site\PartnerController` | Live |
| Community leaderboard and profile | Public | Public | `site.community.index`, `site.community.show` | `Site\CommunityController` | Live; opted-in members only |
| Gallery albums and photos | Public | Public | `site.gallery.index`, `site.gallery.show`, `media.photo` | `Site\GalleryController` | Live |
| Contact form | Public | Public | `site.contact`, `site.contact.store` | `Site\ContactController` | Live; throttled |
| Project report PDF download | Public | Public | `site.documents.project-report` | `Site\DocumentController` | Live; internal projects refused |
| Publication PDF download | Public | Public | `site.documents.publication` | `Site\DocumentController` | Live; counts downloads |
| CMS pages (About, FAQ, Privacy, Terms...) | Public | Public | `site.page` | `Site\PageController` | Live |
| Sitemap and robots | Public | Public | `site.sitemap`, `site.robots` | `Site\SeoController` | Live |
| Unsubscribe from announcements | Public (signed link) | Member via e-mail | `site.unsubscribe`, `site.unsubscribe.store` | `Site\UnsubscribeController` | Live |
| Old URL redirects | Public | Public | `/apply`, `/track`, `/news-and-blogs`, `/sign-in` variants | `routes/web.php` | Live |

## Accounts

| Feature | Surface | Roles | Route names | Controller | Status |
| --- | --- | --- | --- | --- | --- |
| Sign up | Public | Guest | `register`, `register.store` | `Auth\RegisterController` | Live; can be paused |
| Sign in and out | Public | Guest, signed-in | `login`, `login.store`, `logout` | `Auth\SessionController` | Live |
| E-mail code verification | Public | Signed-in unverified | `verification.notice`, `verification.verify`, `verification.resend` | `Auth\EmailVerificationController` | Live; needs working mail |
| E-mail link verification | Public (signed link) | Any | `verification.link`, `verification.link.store` | `Auth\EmailLinkController` | Live |
| Forgot and reset password | Public | Guest | `password.request`, `password.email`, `password.reset`, `password.update` | `Auth\PasswordResetController` | Live |
| Reset by link | Public (signed link) | Any | `password.link`, `password.link.store` | `Auth\EmailLinkController` | Live |
| Onboarding profile | Portal | M | `onboarding.show`, `onboarding.store` | `Portal\OnboardingController` | Live |
| Notifications (list, open, read all) | Shared | Signed-in verified | `notifications.index`, `notifications.open`, `notifications.read-all` | `NotificationController` | Live |

## Member portal

| Feature | Surface | Roles | Route names | Controller | Status |
| --- | --- | --- | --- | --- | --- |
| Portal dashboard | Portal | M | `portal.dashboard` | `Portal\DashboardController` | Live |
| Apply (new or draft) | Portal | M | `portal.apply`, `portal.apply.store` | `Portal\ApplicationController` | Live |
| My applications, detail, file download | Portal | M (owner) | `portal.applications.index`, `.show`, `.file` | `Portal\ApplicationController` | Live |
| Withdraw application | Portal | M (owner) | `portal.applications.withdraw` | `Portal\ApplicationController` | Live |
| Message the Hub | Portal | M (owner) | `portal.applications.messages.store` | `Portal\MessageController` | Live; 20 per minute |
| Progress reports (create, edit, list, view) | Portal | M (owner, accepted) | `portal.reports.create`, `.store`, `.edit`, `.update`, `.index`, `.show` | `Portal\ProgressReportController` | Live |
| Profile, password | Portal | M | `portal.profile.edit`, `.update`, `.password` | `Portal\ProfileController` | Live |

## Staff workspace

| Feature | Surface | Roles (permission) | Route names | Controller | Status |
| --- | --- | --- | --- | --- | --- |
| Dashboard | Workspace | All staff (`workspace.access`) | `workspace.dashboard` | `Workspace\DashboardController` | Live |
| My account, password, sign out other sessions | Workspace | All staff | `workspace.profile.edit`, `.update`, `.password`, `.sessions.destroy` | `Workspace\ProfileController` | Live |
| Programmes: list, create, edit, delete | Workspace | HD, PO (`programs.manage`) | `workspace.programs.index`, `.create`, `.store`, `.edit`, `.update`, `.destroy` | `Workspace\ProgramController` | Live |
| Programme detail with applications | Workspace | HD, PO, RV (`applications.review`) | `workspace.programs.show` | `Workspace\ProgramController` | Live |
| Announce programme | Workspace | HD, PO (`programs.manage`) | `workspace.programs.announce` | `Workspace\ProgramController` | Live |
| Export programme applications (CSV) | Workspace | HD, PO, RV (`applications.review`) | `workspace.programs.export` | `Workspace\ProgramController` | Live |
| Applications: queue, review, files | Workspace | HD, PO, RV (`applications.review`) | `workspace.applications.index`, `.show`, `.update`, `.file` | `Workspace\ApplicationController` | Live |
| Bulk decision | Workspace | HD, PO, RV | `workspace.applications.bulk` | `Workspace\ApplicationController` | Live; max 500 |
| Message applicant | Workspace | HD, PO, RV | `workspace.applications.messages.store` | `Workspace\MessageController` | Live; 30 per minute |
| Progress reports: queue and review | Workspace | HD, PO, RV | `workspace.progress-reports.index`, `.show`, `.update` | `Workspace\ProgressReportController` | Live |
| Members: list, view, export | Workspace | HD, PO (`members.manage`) | `workspace.members.index`, `.show`, `.export` | `Workspace\MemberController` | Live |
| Suspend or reactivate member | Workspace | HD, PO | `workspace.members.toggle` | `Workspace\MemberController` | Live |
| Send reset or verification e-mail to a member | Workspace | HD, PO | `workspace.members.password-reset`, `workspace.members.verification` | `Workspace\AccountEmailController` | Live; 10 per minute |
| Award or remove badge | Workspace | HD, PO | `workspace.achievements.store`, `.destroy` | `Workspace\AchievementController` | Live |
| Broadcasts: list, create, edit, send, delete, audience count | Workspace | HD, PO (`broadcasts.send`) | `workspace.broadcasts.index`, `.create`, `.store`, `.show`, `.edit`, `.update`, `.destroy`, `.audience` | `Workspace\BroadcastController` | Live |
| Contact enquiries | Workspace | HD, PO (`messages.manage`) | `workspace.messages.index`, `.show`, `.update`, `.destroy` | `Workspace\ContactMessageController` | Live |
| News and blog posts | Workspace | IN (own drafts), PO, HD (`content.draft`, `content.publish`) | `workspace.posts.index`, `.create`, `.store`, `.edit`, `.update`, `.destroy` | `Workspace\PostController` | Live |
| Gallery albums and photos | Workspace | IN, PO, HD (`content.draft`; publish needs `content.publish`) | `workspace.albums.index`, `.create`, `.store`, `.edit`, `.update`, `.destroy`, `.photos.store`, `.photos.arrange`, `.photos.destroy` | `Workspace\AlbumController` | Live |
| Site pages | Workspace | HD, PO (`pages.manage`) | `workspace.pages.index`, `.create`, `.store`, `.edit`, `.update`, `.destroy` | `Workspace\PageController` | Live |
| Publications | Workspace | HD, PO (`publications.manage`) | `workspace.publications.index`, `.create`, `.store`, `.edit`, `.update`, `.destroy` | `Workspace\PublicationController` | Live |
| Partner organisations (list: all staff via `projects.view`; change: `partners.manage`) | Workspace | All staff view; HD, PO change | `workspace.organizations.index`, `.create`, `.store`, `.edit`, `.update`, `.destroy` | `Workspace\OrganizationController` | Live |
| Projects: view | Workspace | All staff (`projects.view`) | `workspace.projects.index`, `.show` | `Workspace\ProjectController` | Live |
| Projects: create, edit | Workspace | HD, PO (`projects.manage`) | `workspace.projects.create`, `.store`, `.edit`, `.update` | `Workspace\ProjectController` | Live |
| Projects: archive or delete | Workspace | HD only (`projects.delete`) | `workspace.projects.destroy` | `Workspace\ProjectController` | Live |
| Projects: export CSV, report PDF | Workspace | HD, PO, RV (`reports.export`) | `workspace.projects.export`, `workspace.projects.report` | `Workspace\ProjectController` | Live |
| Legacy grant applications | Workspace | HD, PO, RV (`grants.review`) | `workspace.grants.index`, `.show`, `.update`, `.proposal` | `Workspace\GrantApplicationController` | Live; review only |
| Reports and PDF export | Workspace | HD, PO, RV (`reports.view`) | `workspace.reports.index`, `workspace.reports.pdf` | `Workspace\ReportController` | Live; PDF 10 per minute |
| Staff accounts | Workspace | HD (`users.manage`, via `UserPolicy`) | `workspace.users.index`, `.create`, `.store`, `.edit`, `.update`, `.destroy`, `.toggle`, `.password-reset` | `Workspace\UserController`, `AccountEmailController` | Live |
| Roles and permissions matrix | Workspace | HD (`users.manage`) | `workspace.roles.index` | `Workspace\RoleController` | Live; read-only page |
| Site settings, SMTP, test e-mail | Workspace | HD (`settings.manage`) | `workspace.settings.edit`, `.update`, `.mail`, `.mail.test` | `Workspace\SettingsController` | Live |
| E-mail templates | Workspace | HD (`settings.manage`) | `workspace.templates.index`, `.edit`, `.update`, `.preview`, `.reset` | `Workspace\EmailTemplateController` | Live |
| Audit log | Workspace | HD (`audit.view`) | `workspace.audit.index` | `Workspace\AuditLogController` | Live |

## Open data API

All routes are `GET`, public, throttled `api` (60 per minute per IP), and return the envelope described in `Api\V1\ApiController`.

| Feature | Route names | Controller | Status |
| --- | --- | --- | --- |
| Discovery index | `api.v1.index` | `Api\V1\IndexController` | Live |
| Statistics | `api.v1.stats` | `Api\V1\StatsController` | Live |
| Projects list and detail | `api.v1.projects.index`, `.show` | `Api\V1\ProjectController` | Live |
| Publications list and detail | `api.v1.publications.index`, `.show` | `Api\V1\PublicationController` | Live |
| News list and detail | `api.v1.news.index`, `.show` | `Api\V1\NewsController` | Live |
| Partners | `api.v1.partners.index` | `Api\V1\PartnerController` | Live; no contact e-mails |
| Programmes list and detail | `api.v1.programmes.index`, `.show` | `Api\V1\ProgramController` | Live; no drafts, no forms |
| Community | `api.v1.community` | `Api\V1\CommunityController` | Live |

## Console commands

| Command | Purpose | Source |
| --- | --- | --- |
| `php artisan hub:create-admin` | Create the first Hub Director from `ADMIN_*` variables | `app/Console/Commands/CreateAdministrator.php` |
| `php artisan hub:import-documents` | Copy legacy PDFs into the documents disk | `app/Console/Commands/ImportLegacyDocuments.php` |
| `php artisan hub:secure-demo-accounts` | Move accounts with default passwords to `SEED_PASSWORD` | `app/Console/Commands/SecureDemoAccounts.php` |

## Routes that are redirects, not features

`/admin`, `/login`, `/signin`, `/signup`, `/register`, `/workspace/sign-in`, `/apply`, `/track`, `/news-and-blogs` have no name in `routes.txt` and redirect to current pages (`routes/web.php`).

## Gaps found while compiling this table

- Routes `workspace.users.*`, `workspace.organizations.*`, `workspace.projects.*`, `workspace.posts.*`, `workspace.publications.*`, `workspace.grants.*` have only `can:workspace.access` in route middleware. Their specific permission is enforced by a policy inside the controller (`Gate::authorize(...)`). `PrivilegesTest::test_every_state_changing_workspace_route_enforces_its_permission` covers state-changing routes.
- `UserPolicy` checks `users.manage` and adds rules (a user cannot act on themselves, and staff screens only touch staff accounts). `workspace.users.*` routes do not carry `can:users.manage` in the route list, unlike `workspace.roles.index`, so the policy is the only gate on those routes.
