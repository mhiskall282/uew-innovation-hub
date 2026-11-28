# Acceptance criteria

> **Audience:** Product owners, testers, UAT participants · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [User stories](user-stories.md), [Functional requirements](functional-requirements.md), [Testing guide](../05-developer-guide/testing-guide.md), [Quality assurance checklist](../10-testing-and-quality/quality-assurance-checklist.md)

Each criterion is written as Given / When / Then and tied to the story it checks. The **Automated test** column names an existing test in `tests/Feature` or `tests/Unit` (class and method, run by `php artisan test`). Criteria with "None" have no automated test and must be checked by hand during user acceptance testing (UAT). Test names were read from the files; this page does not claim they were re-run on the day of writing (the handover report states 177 tests passing).

Labels: **Covered** (test asserts it), **Partly covered**, **Manual** (no test).

## Accounts

| ID | Story | Criterion | Automated test | Coverage |
| --- | --- | --- | --- | --- |
| AC-1 | US-7 | Given a visitor, when they sign up, then an unverified member is created and a hashed code is e-mailed. | `Auth\MemberAccountTest::test_sign_up_creates_an_unverified_member_and_emails_a_hashed_code` | Covered |
| AC-2 | US-7 | Given a sign-up form, when it includes `role=super_admin`, then the account is still a member. | `MemberAccountTest::test_role_cannot_be_injected_at_sign_up` | Covered |
| AC-3 | US-7 | Given an unverified member, when they open the portal, then they are sent to verify first. | `MemberAccountTest::test_unverified_members_cannot_use_the_portal` | Covered |
| AC-4 | US-7 | Given a correct code, when submitted, then the e-mail is verified and onboarding is required. | `MemberAccountTest::test_correct_code_verifies_then_onboarding_is_required` | Covered |
| AC-5 | US-7 | Given wrong codes, when a sixth attempt is made, then it is refused. Expired codes are refused. | `MemberAccountTest::test_wrong_codes_are_limited_to_five_attempts`, `test_expired_codes_are_rejected` | Covered |
| AC-6 | US-18 | Given any e-mail, when "forgot password" is used, then the message is the same whether or not the account exists. | `MemberAccountTest::test_forgot_password_does_not_reveal_whether_an_account_exists` | Covered |
| AC-7 | US-7 | Given a real mailbox, when a person signs up on the live site, then the code arrives within a few minutes. | None. The test site uses `MAIL_MAILER=log`. | Manual (blocked until SMTP is set) |
| AC-8 | US-40 | Given a staff sign-in, when it succeeds, then the user lands in `/workspace` and the session is regenerated; members land in `/portal`. | `Auth\AuthenticationTest::test_staff_sign_in_lands_in_the_workspace_and_regenerates_the_session`, `test_members_land_in_the_portal_never_the_workspace` | Covered |
| AC-9 | US-40 | Given five failed sign-ins, when another is tried, then sign-in is locked. | `AuthenticationTest::test_sign_in_locks_out_after_five_failures` | Covered |
| AC-10 | US-40 | Given a deactivated account, when it tries to sign in or is already signed in, then access is refused. | `AuthenticationTest::test_deactivated_accounts_cannot_sign_in`, `test_a_user_deactivated_mid_session_is_signed_out_on_the_next_request` | Covered |

## Programmes and applications

| ID | Story | Criterion | Automated test | Coverage |
| --- | --- | --- | --- | --- |
| AC-11 | US-20 | Given an officer, when they create a programme with a form and publish it, then it appears publicly; when they delete it, then it is gone. | `Workspace\AdministrationTest::test_officer_creates_publishes_and_deletes_a_programme_with_a_form` | Covered |
| AC-12 | US-20 | Given bad programme input, when saved, then validation errors are shown. | `AdministrationTest::test_programme_validation_rejects_bad_input` | Covered |
| AC-13 | US-20 | Given a reviewer, when they try to manage programmes, then access is refused. | `AdministrationTest::test_reviewers_cannot_manage_programmes` | Covered |
| AC-14 | US-1 | Given draft programmes, when the API or public site is read, then drafts and form schemas are not exposed. | `Portal\EngagementTest::test_public_programmes_api_hides_drafts_and_forms` | Covered |
| AC-15 | US-8 | Given a member with a profile, when they open an application, then matching questions are pre-filled. | `EngagementTest::test_application_questions_prefill_from_the_profile` | Covered |
| AC-16 | US-10 | Given a programme that is open, when a member submits, then reviewers are notified and the member gets XP. | `EngagementTest::test_submitting_an_application_notifies_reviewers_and_gives_xp` | Covered |
| AC-17 | US-9 | Given a closed, unopened or full programme, when a member tries to save, then it is refused. | None. A search of `tests/Feature` for `closes_at`, `capacity`, `withdraw`, `bulk` and `exportCsv` found no matches. | Manual |
| AC-18 | US-11 | Given a submitted application, when the member withdraws, then the status becomes Withdrawn; an accepted one cannot be withdrawn. | None named for withdrawal in `tests/Feature`. | Manual |
| AC-19 | US-32 | Given a reviewer, when they decide, then the applicant gets an in-app notice and an accepted applicant gets a badge and XP. | `EngagementTest::test_accepting_an_application_notifies_awards_a_badge_and_xp` | Covered |
| AC-20 | US-36 | Given a reviewer, when they open an application, then gender and disability are not shown. | `EngagementTest::test_reviewers_do_not_see_gender_or_disability` | Covered |
| AC-21 | US-12 | Given an application, when the applicant or staff post, then it works; a stranger is refused. | `EngagementTest::test_applicant_and_staff_can_message_but_strangers_cannot` | Covered |
| AC-22 | US-33 | Given 2 to 500 submitted applications, when a reviewer applies a bulk decision, then all non-draft, non-withdrawn ones change. | None for the bulk route. | Manual |
| AC-23 | US-23 | Given a programme with applications, when an officer exports CSV, then drafts are excluded and dangerous cells are neutralised. | None for `ApplicationService::exportCsv()`. Projects export is tested: `ProjectManagementTest::test_csv_export_streams_filtered_rows_and_neutralises_formulas`. | Partly covered |

## Progress reports

| ID | Story | Criterion | Automated test | Coverage |
| --- | --- | --- | --- | --- |
| AC-24 | US-14 | Given an accepted application, when the owner submits a report with line items, then the spent total is the sum of the items on the server. | `EngagementTest::test_accepted_member_submits_a_report_with_server_side_totals` | Covered |
| AC-25 | US-14 | Given a non-accepted application or another member, when a report is attempted, then it is refused. | `EngagementTest::test_reports_are_limited_to_accepted_applications_and_their_owner` | Covered |
| AC-26 | US-35, US-15 | Given a submitted report, when staff request changes, then the member can edit and resubmit. | `EngagementTest::test_staff_review_requests_changes_and_member_can_resubmit` | Covered |

## Content, gallery and communication

| ID | Story | Criterion | Automated test | Coverage |
| --- | --- | --- | --- | --- |
| AC-27 | US-37 | Given an intern, when they ask to publish, then the post stays a draft. | `ContentPublishingTest::test_intern_drafts_stay_drafts_even_if_they_ask_to_publish` | Covered |
| AC-28 | US-37 | Given an intern, when they edit someone else's or a published post, then it is refused. | `ContentPublishingTest::test_intern_can_edit_own_draft_but_not_others_or_published_posts` | Covered |
| AC-29 | US-26 | Given an officer, when they publish, schedule or unpublish, then visibility follows; a past schedule is rejected. | `ContentPublishingTest::test_officer_publishes_and_schedules`, `test_scheduling_in_the_past_is_rejected`, `test_officer_can_unpublish_by_saving_as_draft` | Covered |
| AC-30 | US-2 | Given draft posts, when the public site is read, then only published posts appear. | `Site\PublicSiteTest::test_only_published_posts_are_public` | Covered |
| AC-31 | US-38, US-5 | Given an album, when photos are uploaded, then they are resized and stripped; disguised files and tiny images are rejected; draft photos are hidden from the public. | `GalleryTest::test_officer_creates_an_album_and_uploads_photos_which_are_resized_and_stripped`, `test_non_images_disguised_files_and_tiny_images_are_rejected`, `test_photos_in_draft_albums_are_hidden_from_the_public` | Covered |
| AC-32 | US-4 | Given the contact form, when submitted, then it is stored; a bot field is rejected; too many posts are limited. | `AdministrationTest::test_contact_form_stores_messages_and_rejects_bots`, `test_contact_form_is_rate_limited` | Covered |
| AC-33 | US-25, US-16 | Given a broadcast, when sent, then only opted-in matching members receive it and the unsubscribe link is signed and works. | `AdministrationTest::test_broadcast_reaches_only_opted_in_matching_members_and_unsubscribe_link_is_signed`, `Auth\EmailLinksTest::test_broadcasts_carry_one_click_unsubscribe_headers_that_work` | Covered |
| AC-34 | US-29 | Given page text in Markdown, when saved, then scripts are neutralised; reserved slugs are refused. | `AdministrationTest::test_cms_page_markdown_cannot_inject_html_or_scripts`, `test_cms_pages_cannot_take_reserved_or_existing_slugs` | Covered |
| AC-35 | US-17 | Given members, when the community page loads, then only opted-in members appear and no private data. | `EngagementTest::test_community_page_shows_only_opted_in_members_without_private_data` | Covered |

## Portfolio, reports and administration

| ID | Story | Criterion | Automated test | Coverage |
| --- | --- | --- | --- | --- |
| AC-36 | US-28 | Given an officer, when they create a project with partners and a report, then it is saved; updates record a field-level diff. | `ProjectManagementTest::test_officer_creates_a_project_with_partners_and_a_report`, `test_update_records_a_field_level_diff_and_syncs_partners` | Covered |
| AC-37 | US-39 | Given read-only roles, when they try to change a project, then it is refused; only the Hub Director can archive. | `ProjectManagementTest::test_read_only_roles_cannot_write`, `test_only_the_hub_director_can_archive` | Covered |
| AC-38 | US-3 | Given an internal project, when its report is requested publicly, then it is refused; public ones stream as PDF. | `Site\DocumentAccessTest::test_internal_project_reports_are_not_public`, `test_public_project_report_streams_as_pdf_with_nosniff` | Covered |
| AC-39 | US-45 | Given a date range, when the PDF is exported, then it downloads, is audited and needs `reports.view`. | `ReportExportTest::test_pdf_export_downloads_and_is_audited`, `test_pdf_export_requires_reports_permission` | Covered |
| AC-40 | US-40 | Given the Hub Director, when they create accounts, then every role including intern can be created; invalid roles are rejected; they cannot deactivate themselves. | `UserManagementTest::test_hub_director_creates_accounts_for_every_role_including_intern`, `test_invalid_roles_and_unconfirmed_passwords_are_rejected`, `test_admins_cannot_deactivate_or_delete_themselves` | Covered |
| AC-41 | US-40 | Given staff, when anyone tries to change their own role, then it is refused; role changes are audited. | `PrivilegesTest::test_nobody_can_change_their_own_role`, `test_role_changes_are_audited_with_old_and_new_values` | Covered |
| AC-42 | US-41 | Given every role, when it requests every workspace page, then it reaches exactly the pages it should. | `Workspace\AccessControlTest::test_role_can_reach_exactly_its_pages`, `PrivilegesTest::test_every_state_changing_workspace_route_enforces_its_permission` | Covered |
| AC-43 | US-42 | Given SMTP settings, when saved, then the password is stored encrypted and kept when left blank; only the Hub Director can change settings. | `AdministrationTest::test_smtp_password_is_stored_encrypted_and_kept_when_left_blank`, `test_only_the_hub_director_changes_site_settings` | Covered |
| AC-44 | US-44 | Given the Hub Director, when they search the audit log, then results filter. | `UserManagementTest::test_audit_log_is_searchable_by_hub_director` | Covered |
| AC-45 | US-48 | Given a legacy grant application, when a reviewer declines it without a note, then it is refused. | `GrantReviewTest::test_declining_requires_a_note` | Covered |
| AC-46 | US-6 | Given the API, when filters are invalid, then a 422 envelope is returned; partner e-mails are never exposed; responses are cacheable and rate limited. | `Api\PublicApiTest::test_invalid_filters_return_a_422_envelope`, `test_partners_endpoint_never_exposes_contact_emails`, `test_responses_are_cacheable_and_rate_limited` | Covered |

## Cross-cutting

| ID | Criterion | Automated test | Coverage |
| --- | --- | --- | --- |
| AC-47 | No GET route returns a server error for any of 8 actors. | `RouteSmokeTest::test_no_get_route_returns_a_server_error` | Covered |
| AC-48 | Responses carry security headers and a strict CSP; forms are CSRF protected; `/up` responds. | `SecurityHeadersTest` (4 tests) | Covered |
| AC-49 | The site is indexable only when `SEARCH_INDEXING=true`; the sitemap lists only public content. | `SecurityHardeningTest::test_robots_and_sitemap_follow_the_indexing_setting`, `test_sitemap_lists_only_public_content` | Covered |
| AC-50 | Accessibility: no axe violations on key screens; no horizontal scroll from 320 px. | `tests/e2e/a11y.spec.ts`, `tests/e2e/responsive.spec.ts` (Playwright, `npm run test:e2e`) | Covered (browser tests, not PHPUnit) |

## UAT checklist for launch (manual)

1. A real e-mail address receives the sign-up code and a decision e-mail (AC-7).
2. A programme with a closing date refuses applications after that time (AC-17).
3. Withdraw, bulk decision and CSV export work on a realistic data set (AC-18, AC-22, AC-23).
4. Demo accounts no longer sign in with a default password.
5. Privacy and Terms pages have been reviewed by the university.
