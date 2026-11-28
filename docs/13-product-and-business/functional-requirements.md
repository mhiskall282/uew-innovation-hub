# Functional requirements

> **Audience:** Analysts, developers, testers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Business requirements](business-requirements.md), [Feature inventory](feature-inventory.md), [Acceptance criteria](acceptance-criteria.md), [Endpoints](../06-api-reference/endpoints.md)

Functional requirements (FR-n) describe what the system does. Labels: **Implemented (verified in `<file>`)**, **Inferred**, **Proposed**. Route names refer to the output of `php artisan route:list`. Test names refer to `tests/Feature`.

## Accounts and access

| ID | Requirement | Label |
| --- | --- | --- |
| FR-1 | A visitor can create a member account with the role fixed to `member`; a posted role is ignored. | Implemented (verified in `Auth\RegisterController`, `MemberAccountTest::test_role_cannot_be_injected_at_sign_up`) |
| FR-2 | Registration can be paused by the Hub Director (`registration_open`). | Implemented (verified in `SettingsService`, `MemberAccountTest::test_registration_can_be_paused`) |
| FR-3 | A new member confirms their e-mail with a one-time code (10 minutes, 5 attempts, 60-second resend) or a signed link. | Implemented (verified in `VerificationService`, `EmailLinksTest`) |
| FR-4 | Unverified members cannot use the portal; verified members complete onboarding first. | Implemented (verified in `routes/web.php` middleware, `MemberAccountTest`) |
| FR-5 | Sign-in regenerates the session; staff land in `/workspace`, members in `/portal`. | Implemented (verified in `Auth\SessionController`, `AuthenticationTest`) |
| FR-6 | Sign-in locks after repeated failures, per e-mail and IP and per account. | Implemented (verified in `AuthenticationTest::test_sign_in_locks_out_after_five_failures`, `SecurityHardeningTest`) |
| FR-7 | Deactivated users cannot sign in and are signed out on their next request. | Implemented (verified in `EnsureAccountIsActive`, `AuthenticationTest`) |
| FR-8 | Password reset by code or signed link; the response does not reveal whether the account exists; other sessions are revoked. | Implemented (verified in `Auth\PasswordResetController`, `MemberAccountTest`, `EmailLinksTest`) |
| FR-9 | Staff can change their own password only with the current password. | Implemented (verified in `AdministrationTest`) |
| FR-10 | The Hub Director creates staff accounts, changes roles, deactivates users and sends reset or verification e-mails. Nobody can change their own role; the last active Hub Director cannot be demoted. | Implemented (verified in `Workspace\UserController`, `UserManagementTest`, `PrivilegesTest`) |

## Programmes and applications

| ID | Requirement | Label |
| --- | --- | --- |
| FR-11 | Staff with `programs.manage` create, edit, publish, close, archive and delete programmes. | Implemented (verified in `ProgramService`, `AdministrationTest::test_officer_creates_publishes_and_deletes_a_programme_with_a_form`) |
| FR-12 | A programme has a custom form of up to 40 fields in 12 types. Keys stay stable when labels change. | Implemented (verified in `FormSchema`, `DomainTest::test_form_schema_normalises_keys_and_drops_invalid_fields`) |
| FR-13 | The public sees only published and closed programmes. Drafts and forms are hidden from the API. | Implemented (verified in `Program::scopeVisible()`, `EngagementTest::test_public_programmes_api_hides_drafts_and_forms`) |
| FR-14 | A programme accepts applications only while published, inside its window and under capacity. | Implemented (verified in `Program::isAcceptingApplications()`) |
| FR-15 | A member saves a draft or submits. Submitting requires a declaration, validates answers server-side, stores a profile snapshot and earns 10 XP. | Implemented (verified in `Portal\ApplicationController::store()`, `ApplicationService::save()`) |
| FR-16 | Form answers pre-fill from the member's profile. | Implemented (verified in `EngagementTest::test_application_questions_prefill_from_the_profile`) |
| FR-17 | A member can withdraw an application in Draft, Submitted or Under review. | Implemented (verified in `ApplicationStatus::canWithdraw()`) |
| FR-18 | Reviewers set a status (Submitted, Under review, Shortlisted, Accepted, Not selected), score, internal notes and a decision message; an in-app notice is always sent, e-mail only if "notify" is ticked. | Implemented (verified in `ApplicationService::review()`) |
| FR-19 | Bulk decisions on up to 500 applications at once. | Implemented (verified in `Workspace\ApplicationController::bulk()`) |
| FR-20 | Staff export a programme's non-draft applications as CSV with spreadsheet-formula neutralising. | Implemented (verified in `ApplicationService::exportCsv()`, `cell()`) |
| FR-21 | Uploaded application files are downloadable only by the owner and by reviewers. | Implemented (verified in `routes` `portal.applications.file`, `workspace.applications.file`, `ProgramApplicationPolicy`) |
| FR-22 | Reviewers do not see gender or disability details. | Implemented (verified in `EngagementTest::test_reviewers_do_not_see_gender_or_disability`) |
| FR-23 | Accepting an application awards a "Participant" badge. | Implemented (verified in `ApplicationService::review()`, `EngagementTest`) |
| FR-24 | Staff can announce a published programme to opted-in members whose interests match its focus area. | Implemented (verified in `ProgramService::announce()`) |
| FR-25 | Applicants and staff exchange messages on an application; strangers cannot. | Implemented (verified in `ConversationService`, `EngagementTest::test_applicant_and_staff_can_message_but_strangers_cannot`) |

## Progress reports

| ID | Requirement | Label |
| --- | --- | --- |
| FR-26 | Only the owner of an accepted application can submit a progress report. | Implemented (verified in `ProgressReportService::submit()`, `EngagementTest::test_reports_are_limited_to_accepted_applications_and_their_owner`) |
| FR-27 | The spent total is the sum of line items, computed on the server. | Implemented (verified in `ProgressReportService::submit()`) |
| FR-28 | Staff accept a report or request changes with feedback; the member can then edit and resubmit. | Implemented (verified in `ProgressReportService::review()`, `EngagementTest::test_staff_review_requests_changes_and_member_can_resubmit`) |
| FR-29 | A funding-versus-spending view per programme. | Implemented (verified in `ProgressReportService::budgetByProgramme()`) |

## Content and portfolio

| ID | Requirement | Label |
| --- | --- | --- |
| FR-30 | Drafters write posts; only publishers publish or schedule; scheduling in the past is rejected. | Implemented (verified in `PostService`, `ContentPublishingTest`) |
| FR-31 | Interns edit only their own unpublished drafts. | Implemented (verified in `PostPolicy::update()`) |
| FR-32 | Albums: upload photos (resized, metadata stripped), arrange, caption, set cover; publishing and deleting need `content.publish`. | Implemented (verified in `GalleryService`, `AlbumPolicy`, `GalleryTest`) |
| FR-33 | Photos of unpublished albums are hidden from the public. | Implemented (verified in `GalleryTest::test_photos_in_draft_albums_are_hidden_from_the_public`) |
| FR-34 | Staff edit site pages in Markdown; HTML and scripts are neutralised; reserved slugs are refused. | Implemented (verified in `AdministrationTest::test_cms_page_markdown_cannot_inject_html_or_scripts`, `test_cms_pages_cannot_take_reserved_or_existing_slugs`) |
| FR-35 | Projects have partners with roles, a PDF report and a field-level change history; internal projects are hidden from the public. | Implemented (verified in `ProjectManagementTest`, `PublicSiteTest::test_internal_projects_are_hidden_from_listing_and_detail`) |
| FR-36 | Publications can be uploaded as PDF and downloaded publicly with a counter. | Implemented (verified in `ContentPublishingTest::test_officer_manages_publications_with_pdf_upload`, `DocumentAccessTest`) |
| FR-37 | Partners linked to projects cannot be deleted. | Implemented (verified in `UserManagementTest::test_partners_linked_to_projects_cannot_be_deleted`) |

## Communication

| ID | Requirement | Label |
| --- | --- | --- |
| FR-38 | The contact form stores messages, rejects bots (honeypot field) and is rate limited. | Implemented (verified in `StoreContactMessageRequest`, `AdministrationTest`) |
| FR-39 | Staff mark enquiries read, replied or archived, or delete them. | Implemented (verified in `ContactMessageService`, `ContactStatus`) |
| FR-40 | Broadcasts are drafted, previewed by audience count, sent as queued e-mails, and carry a signed one-click unsubscribe link. | Implemented (verified in `BroadcastService`, `SendBroadcast`, `EmailLinksTest::test_broadcasts_carry_one_click_unsubscribe_headers_that_work`) |
| FR-41 | In-app notifications with a bell, inbox and read-all; links accept only local paths. | Implemented (verified in `NotificationController`, `EngagementTest::test_notification_links_only_follow_local_paths_and_belong_to_their_owner`) |
| FR-42 | Staff edit e-mail templates and can reset them to defaults; the Hub Director sets SMTP and sends a test e-mail. | Implemented (verified in `EmailTemplateController`, `SettingsController`, `AdministrationTest::test_smtp_password_is_stored_encrypted_and_kept_when_left_blank`) |

## Reporting, audit and API

| ID | Requirement | Label |
| --- | --- | --- |
| FR-43 | Reports page with date-range presets and a PDF export that is audited. | Implemented (verified in `ReportExportTest`) |
| FR-44 | CSV export of projects and members. | Implemented (verified in `ReportExportService::projectsCsv()`, `ProjectManagementTest::test_csv_export_streams_filtered_rows_and_neutralises_formulas`; members export route `workspace.members.export`) |
| FR-45 | Audit log searchable by the Hub Director. | Implemented (verified in `UserManagementTest::test_audit_log_is_searchable_by_hub_director`) |
| FR-46 | Community page lists only opted-in members and shows no private data. | Implemented (verified in `EngagementTest::test_community_page_shows_only_opted_in_members_without_private_data`) |
| FR-47 | The API exposes stats, projects, publications, news, partners, programmes and community as read-only JSON with pagination, filters, a common envelope and 422 for bad filters. | Implemented (verified in `PublicApiTest`) |
| FR-48 | `/sitemap.xml` lists public content only; `/robots.txt` follows `SEARCH_INDEXING`. | Implemented (verified in `SecurityHardeningTest`) |
| FR-49 | Legacy grant applications can be reviewed and their proposals downloaded by reviewers only. | Implemented (verified in `GrantReviewTest`, `DocumentAccessTest::test_grant_proposals_are_restricted_to_reviewers`) |

## Not implemented

| ID | Requirement | Label |
| --- | --- | --- |
| FR-50 | Self-service account deletion and data export | Proposed |
| FR-51 | Staff multi-factor authentication | Proposed |
| FR-52 | Reminder e-mails before a deadline | Proposed |
| FR-53 | Excel (`.xlsx`) export | Proposed |
| FR-54 | API write endpoints, API keys, webhooks | Proposed |
| FR-55 | Enforced order of application statuses (for example Submitted before Accepted). Today a reviewer can set any reviewable status from any other (`Rule::enum(...)->only(reviewable())`). | Proposed; confirm that the flexibility is intended |
