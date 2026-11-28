# Authorization Security

> **Audience:** developers, security reviewers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Authorization architecture](../04-architecture/authorization-architecture.md) (canonical role matrix), [User roles and permissions](../02-user-guide/user-roles-and-permissions.md), [Authentication security](authentication-security.md), [Data protection](data-protection.md)

This page covers how access is enforced and where it can fail. The role-to-permission matrix itself is canonical in [authorization architecture](../04-architecture/authorization-architecture.md); it is defined in [`Role::permissions()`](../../app/Enums/Role.php).

## Enforcement layers

| Layer | Mechanism | Example |
| --- | --- | --- |
| 1. Authentication | `auth` middleware, guests redirected to `login` | `bootstrap/app.php` `redirectGuestsTo` |
| 2. Account state | `active` ([`EnsureAccountIsActive`](../../app/Http/Middleware/EnsureAccountIsActive.php)) signs out deactivated users on the next request | all `portal`, `workspace`, `notifications` routes |
| 3. E-mail verified | `verified.email` ([`EnsureEmailIsVerified`](../../app/Http/Middleware/EnsureEmailIsVerified.php)) | same |
| 4. Onboarding | `onboarded` ([`EnsureOnboarded`](../../app/Http/Middleware/EnsureOnboarded.php)) for members | `portal.*`, programme apply |
| 5. Permission gates | `can:<permission>`; every `Permission` case is a Gate in `AppServiceProvider::registerPermissionGates()`. `hasPermission()` also requires `is_active` | `can:workspace.access`, `can:members.manage` |
| 6. Policies | Per-record checks in `app/Policies` | `ProgramApplicationPolicy::view`, `ProgressReportPolicy` |
| 7. Query scoping | Public pages filter on `is_published`, `is_public`, `published_at` | `PublicSiteTest` |

Workspace routes carry `auth,active,verified.email,can:workspace.access` plus a specific `can:` where one applies. The `workspace.users.*` routes have no route-level `can:users.manage`; the controller calls `Gate::authorize(...)` against [`UserPolicy`](../../app/Policies/UserPolicy.php), which requires `users.manage` and `isStaff()` on the target. `PrivilegesTest::every_state_changing_workspace_route_enforces_its_permission` iterates routes to catch a missing check.

Policies present: Album, GrantApplication, Organization, Post, ProgramApplication, ProgressReport, Project, Publication, User.

## Privilege escalation controls

- `role`, `is_active`, `email_verified_at`, `onboarded_at` are not in `User::$fillable`. Only services set them ([`UserService`](../../app/Services/UserService.php), `MemberService`).
- Models that handle application data use `$fillable = []` and `forceFill` in services: `ProgramApplication`, `ProgressReport`, `ApplicationMessage`, `Achievement`, `VerificationCode`.
- `RegisterRequest` returns only `name, email, phone, gender, date_of_birth, region, password`. A posted `role` is ignored (`role_cannot_be_injected_at_sign_up`).
- Nobody can change their own role. The last active Hub Director cannot be demoted, deactivated or deleted. Admins cannot deactivate or delete themselves.
- Staff accounts cannot be turned into members through the staff form, and Programme Officers manage members but not staff (`UserPolicy::update` requires `$target->isStaff()` and `users.manage`).
- Interns can only create drafts. A publish request from an intern is ignored in `PostService` (`content.publish` check).
- Role changes are audited with old and new values.

## Record-level rules

| Record | Rule | Source |
| --- | --- | --- |
| Programme application | Applicant, or a user with `applications.review` on a submitted (non-editable) application. Never other people's drafts | `ProgramApplicationPolicy::view` |
| Application messages | Applicant or reviewer, never on drafts | `ProgramApplicationPolicy::message` |
| Reviewing | Reviewer cannot review their own application | `ProgramApplicationPolicy::review` |
| Application files | Download goes through `Gate::authorize('view', $application)` | `Portal/ApplicationController::file` |
| Progress report | Author and reviewers; edit only while changes are requested | `ProgressReportPolicy` |
| Notifications | Always read through the signed-in user's relation; links must be local paths | `NotificationController`, `EngagementTest` |
| Gallery photo | A photo cannot be deleted through another album | `GalleryTest::a_photo_cannot_be_deleted_through_another_album` |
| Documents | Public project reports and publications only; proposals need review permission | `DocumentAccessTest` |

## Demographics privacy

The claim that reviewers do not see gender or disability is **partly verified**:

- Verified: `resources/views/workspace/applications/show.blade.php` renders the applicant snapshot without gender and disability (comment in the file says so), and `EngagementTest::reviewers_do_not_see_gender_or_disability` asserts it. Reviewers cannot open member profiles: `workspace/members/*` requires `members.manage`, which only the Programme Officer and Hub Director have.
- Verified: reports show demographics only as totals (`InsightsService`, `reports/pdf.blade.php`).
- **Gap:** the view shows age (calculated from `date_of_birth` in the snapshot) to reviewers. This is intentional for eligibility but is a demographic attribute.
- **Gap:** a programme question can be given `prefill` = `gender` or `date_of_birth` (`FormSchema`, `Demographics::prefillSources()`). The member's answer is stored in the application answers and shown to reviewers in the "Answers" card. The snapshot rule does not cover answers. A Programme Officer can therefore expose gender to reviewers by adding such a question. No code blocks this.
- **Gap:** the members CSV export (`workspace.members.export`) includes phone, gender and age. It needs `members.manage`. Since commit `d1296b1` the export is audited as `member.exported` (`MemberController::export`, with the active filters). The other exports are audited too: `application.exported`, `report.projects_exported` and `report.exported` (PDF). The file itself still contains personal data once downloaded.

> **Requires confirmation:** whether the Hub intends programme questions to collect gender. If not, restrict `prefillSources()` for reviewer-visible answers or hide those answers from reviewers.

## Route-level exposure checks

- `/admin`, `/apply`, `/login`, `/register`, `/signin`, `/signup`, `/track`, `/news-and-blogs`, `/workspace/sign-in` accept any method and are legacy redirects (`PublicSiteTest::old_apply_and_track_urls_redirect`, `AuthenticationTest::common_sign_in_urls_redirect`).
- The catch-all `{page}` route serves CMS pages. `Workspace/PageController` reserves slugs such as `portal`, `workspace`, `api`, `documents`, `unsubscribe`, `up`, `build`, `images` so a page cannot shadow them (`cms_pages_cannot_take_reserved_or_existing_slugs`).
- `RouteSmokeTest` signs in as each role and as a guest and checks that no GET route returns a server error.
- `AccessControlTest::role_can_reach_exactly_its_pages` checks the page matrix for every role.

## Open data API exposure

`/api`, `/api/v1/*` and `/api/v2/*` have no authentication and no permission checks. Every route is `GET`, and visibility is decided in the query (`Project::public()`, `Post::published()`, visible programmes, published albums), not by a policy. v1 and v2 expose the same kind of data. v2 adds `/api/v2/albums` (published albums, photos whose files exist), `/api/v2/reference` (filter values) and `/api/v2/community` (opted-in members only, as in v1). A staff or member session grants no extra API access. `ApiVersion` only labels responses and does not authorise anything.

## Gaps

| Gap | Risk |
| --- | --- |
| Programme prefill of gender/date of birth is visible to reviewers | Privacy |
| Members CSV file is unprotected once downloaded (the export itself is audited as `member.exported`) | Privacy |
| Authorization of `workspace.users.*` depends on controller code, not route middleware | A new route added without `Gate::authorize` would be open to any staff role. `PrivilegesTest` guards state-changing routes |
| No review of effective permissions by route is generated automatically | Manual check with `php artisan route:list` |
