# Integration Testing

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [testing-strategy](testing-strategy.md), [unit-testing](unit-testing.md), [test-data-management](test-data-management.md), [testing guide (commands)](../05-developer-guide/testing-guide.md), [request-response lifecycle](../04-architecture/request-response-lifecycle.md)

In this project, integration tests are the Laravel feature tests in `../../tests/Feature`. Each test sends an HTTP request through the full middleware stack, controller, service, model and database, then asserts on the response, the database, the audit log, queued mail or stored files. There are 189 cases in 22 files. This page explains the pattern, lists what each file covers, and names areas without coverage.

## How the tests are built

| Element | How it is done |
| --- | --- |
| Base class | `Tests\TestCase` (`../../tests/TestCase.php`) |
| Database | `use RefreshDatabase;` in every feature class. SQLite in memory locally, PostgreSQL 16 in the CI `pgsql` job |
| Users | `$this->signInAs(Role::Officer)` creates a staff user and calls `actingAs`. `$this->staff(Role::Viewer)` creates without signing in. Members come from `User::factory()->member()` |
| Fakes | `Mail::fake()`, `Storage::fake('gallery')`, `Storage::fake('private')`. See [test-data-management](test-data-management.md) |
| Config switches | `config([...])` inside the test, for example `hub.block_default_passwords`, `hub.search_indexing`, `api.versions.v1.deprecated_at` |
| Client IP | `withServerVariables(['REMOTE_ADDR' => ...])` for lockout tests |
| Rate limits | `withoutMiddleware(ThrottleRequests::class)` in `PrivilegesTest` only, so authorisation is tested instead of 429 |
| Lazy loading | `Model::preventLazyLoading()` is on outside production (`AppServiceProvider`), so an N+1 in a tested page throws and fails the test |

CSRF is not disabled in tests. Laravel's testing helpers skip the token check, and `SecurityHeadersTest::test_forms_are_csrf_protected` checks that the middleware is registered in the `web` group.

## What each file covers

### Public site and documents

| File | Cases | Covers |
| --- | --- | --- |
| `Site/PublicSiteTest.php` | 19 | 8 public pages render (home, projects, publications, news, partners, programmes, contact, sign-up) with account links and no staff internals; guests see sign-in and create account; old apply/track/news URLs redirect; CMS pages and hidden pages (404); home impact figures; internal projects hidden; project filter and search; only published posts; publication type filter; partner e-mails hidden; branded 404 |
| `Site/DocumentAccessTest.php` | 7 | PDF streaming with `nosniff`; internal project reports not public; download counter; missing file is 404; path traversal refused; grant proposals restricted to reviewers; legacy file locations still found |
| `GalleryTest.php` | 10 | Album create and photo upload (resize, EXIF removed), rejected uploads, role limits, draft albums hidden, public pages and caching, caption/order/cover, file cleanup on delete, lost files hidden, cross-album delete refused, EXIF orientation |

### Authentication and accounts

| File | Cases | Covers |
| --- | --- | --- |
| `Auth/AuthenticationTest.php` | 11 | Sign-in page leaks nothing, URL redirects, session regeneration, member vs staff landing, same message for wrong password and unknown e-mail, deactivated accounts, lockout after five failures, mid-session deactivation, sign-out, staff password change |
| `Auth/MemberAccountTest.php` | 10 | Sign-up creates an unverified member and e-mails a hashed code; role injection refused; unverified members blocked; code verification and onboarding; 5-attempt limit; expiry; resend throttle; registration pause; password reset by code; forgot password does not reveal accounts |
| `Auth/EmailLinksTest.php` | 9 | Verification and reset buttons (POST only, once), tampered/expired/replaced links, suspended accounts, branded e-mail HTML, HTML injection in e-mail, one-click unsubscribe headers |

### Member portal

| File | Cases | Covers |
| --- | --- | --- |
| `Portal/EngagementTest.php` | 12 | Accepting an application notifies, awards a badge and XP; submitting notifies reviewers; notification links stay local and per-owner; application messages; progress reports with server-side totals; report access limits; review and resubmit; award and revoke badges; community page privacy; profile prefill; reviewers do not see gender or disability; public programmes API hides drafts and forms |

### Staff workspace

| File | Cases | Covers |
| --- | --- | --- |
| `Workspace/AccessControlTest.php` | 9 | Role by page matrix for 23 workspace pages, guests redirected, sidebar links, `noindex` |
| `Workspace/PrivilegesTest.php` | 10 | Every state-changing workspace route (144 role/route pairs), own-role change refused, escalation attempts, audit of role changes, director-sent reset and verification e-mails, officers help members only, roles page, sidebar, deactivated staff |
| `Workspace/UserManagementTest.php` | 9 | Account creation for all roles, invalid roles, mass assignment, deactivation, self-delete refused, last director protected, reset rotates remember token, partners with projects cannot be deleted, audit log search |
| `Workspace/AdministrationTest.php` | 13 | Programme create/publish/delete with a form, validation, reviewer denied, SMTP password encrypted, site settings, broadcast audience and signed unsubscribe link, CMS markdown safety, reserved slugs, contact form bots and rate limit, staff password change, `hub:create-admin` idempotent |
| `Workspace/ProjectManagementTest.php` | 8 | Create with partners and report, field-level diff on update, report replacement deletes the old file, validation, read-only roles, archive is director-only, CSV export with formula neutralising, staff filters |
| `Workspace/ContentPublishingTest.php` | 7 | Intern drafts stay drafts, intern edit limits, officer publish and schedule, past schedule rejected, unpublish, publications with PDF upload, intern cannot manage publications |
| `Workspace/GrantReviewTest.php` | 5 | Reviewer decision and e-mail, no mail when unticked or unchanged, decline needs a note, queue counts and filter, non-reviewers refused |
| `Workspace/ReportExportTest.php` | 4 | Date presets and custom ranges, counts by period, PDF export downloads and is audited, PDF needs the reports permission |
| `Workspace/OperationalFixesTest.php` | 6 | Failed broadcast state, audited CSV exports, audit area filter, onboarding opt-in not pre-ticked, programme list query count, viewers cannot export members |

### Whole-application checks

| File | Cases | Covers |
| --- | --- | --- |
| `RouteSmokeTest.php` | 8 | Seeds the demo data, then requests every named GET route as guest, super admin, officer, reviewer, intern, viewer, member, and a member awaiting onboarding. Fails on any HTTP 500, and on a 403 for the super admin on a `workspace.*` page. Asserts that more than 85 URLs resolve |
| `SecurityHardeningTest.php`, `SecurityHeadersTest.php`, `SecureDemoAccountsTest.php` | 14 | See [security-testing](security-testing.md) |
| `Api/PublicApiTest.php`, `Api/ApiV2Test.php` | 18 | See [api-testing](api-testing.md) |

## Workflows covered end to end (in PHPUnit)

```mermaid
flowchart LR
    A["Member signs up"] --> B["E-mail code verified"]
    B --> C["Onboarding"]
    C --> D["Application submitted"]
    D --> E["Reviewer decides"]
    E --> F["Notification, badge, XP"]
    F --> G["Progress report"]
    G --> H["Staff requests changes"]
    H --> G
```

Each arrow is tested in `MemberAccountTest`, `EngagementTest` or `GrantReviewTest`. There is no single test that walks the whole path, and no browser test does either (the Playwright member journey stops at saving a draft).

## Writing a new feature test

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Workspace;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_officer_can_do_it_and_viewer_cannot(): void
    {
        $project = Project::factory()->create();

        $this->signInAs(Role::Viewer);
        $this->post(route('workspace.example.store', $project))->assertForbidden();

        $officer = $this->signInAs(Role::Officer);
        $this->post(route('workspace.example.store', $project))->assertRedirect();

        $this->assertDatabaseHas('activity_logs', ['action' => 'example.created', 'user_id' => $officer->id]);
    }
}
```

`workspace.example.store` and `example.created` are placeholders, not real names. Checklist:

1. Test the allowed role, a denied role and a guest (`assertRedirect(route('login'))`).
2. Assert the audit entry when the action writes to `activity_logs`.
3. Fake mail and storage when the flow uses them.
4. Test validation failures with `assertSessionHasErrors`.
5. If the route is a GET with a model binding, add the binding to `RouteSmokeTest::urls()`.

## Known gaps (existing code with no direct feature test)

- E-mail template edit, update, reset and preview (`Workspace\EmailTemplateController`).
- `hub:import-documents` (`ImportLegacyDocuments`).
- Application withdrawal by a member, and the rule that a withdrawn application cannot be resubmitted (listed as open in the brief).
- Unrestricted application status transitions: no test asserts which transitions are allowed, because the code does not restrict them.
- Dashboard figures from `InsightsService` and `MetricsService`.
- Staff-to-applicant messages: `EngagementTest` checks the in-app message and access, not e-mail, because staff messages to applicants are in-app only.

## Recommended, not implemented

- One test per workspace controller that asserts the audit action names it writes. `AuditLogController::AREAS` lists 18 prefixes and `OperationalFixesTest` checks only the filter, not that every prefix is produced.
- A test that fails when a named GET route is skipped by `RouteSmokeTest` because no binding exists (today it is skipped silently, `continue 2`).
- Test the `pgsql` and `sqlite` differences that matter (case-insensitive search, date functions) with a small shared dataset.
