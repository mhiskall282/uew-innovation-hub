# User stories

> **Audience:** Product owners, developers, testers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [User personas](user-personas.md), [Functional requirements](functional-requirements.md), [Acceptance criteria](acceptance-criteria.md)

Stories are written for the features that exist. Each has a status: **Implemented (verified in `<file>`)**, **Inferred** or **Proposed**. Story IDs (US-n) are referenced by [Acceptance criteria](acceptance-criteria.md).

## Visitors

| ID | Story | Status |
| --- | --- | --- |
| US-1 | As a visitor, I want to browse open programmes so that I can decide whether to apply. | Implemented (verified in `Site\ProgramController`) |
| US-2 | As a visitor, I want to see which projects the Hub funds and filter them by focus area. | Implemented (verified in `Site\ProjectController`, `PublicSiteTest`) |
| US-3 | As a visitor, I want to download publications as PDF. | Implemented (verified in `Site\DocumentController`, `DocumentAccessTest`) |
| US-4 | As a visitor, I want to send the Hub a message. | Implemented (verified in `Site\ContactController`) |
| US-5 | As a visitor, I want to view event photos. | Implemented (verified in `Site\GalleryController`) |
| US-6 | As a developer, I want a JSON feed of public projects so that I can show them on another site. | Implemented (verified in `Api\V1\ProjectController`) |

## Members

| ID | Story | Status |
| --- | --- | --- |
| US-7 | As a new user, I want to create an account and verify my e-mail so that I can apply. | Implemented (verified in `Auth\RegisterController`, `EmailVerificationController`) |
| US-8 | As a member, I want to complete my profile once so that forms pre-fill. | Implemented (verified in `Portal\OnboardingController`, `EngagementTest`) |
| US-9 | As a member, I want to save an application as a draft and finish later. | Implemented (verified in `ApplicationService::save()`) |
| US-10 | As a member, I want to submit an application and get a reference number. | Implemented (verified in `ProgramApplication::generateReference()`, format `UEW-AP-<year>-<code>`) |
| US-11 | As a member, I want to withdraw an application I no longer want. | Implemented (verified in `ApplicationService::withdraw()`) |
| US-12 | As a member, I want to message the Hub about my application. | Implemented (verified in `Portal\MessageController`) |
| US-13 | As a member, I want to be told when my status changes. | Implemented (verified in `ApplicationService::review()`: in-app always, e-mail when the reviewer ticks notify) |
| US-14 | As an accepted applicant, I want to report progress and spending. | Implemented (verified in `Portal\ProgressReportController`) |
| US-15 | As an accepted applicant, I want to fix a report after feedback. | Implemented (verified in `ProgressReport::isEditable()`) |
| US-16 | As a member, I want to opt in or out of announcement e-mails. | Implemented (verified in `MemberService::unsubscribe()`, `site.unsubscribe`) |
| US-17 | As a member, I want to appear on the community page with my badges, or stay private. | Implemented (verified in `User::scopeOnLeaderboard()`) |
| US-18 | As a member, I want to reset my password if I forget it. | Implemented (verified in `Auth\PasswordResetController`) |
| US-19 | As a member, I want to delete my account and download my data myself. | Proposed (F-15) |

## Programme Officers

| ID | Story | Status |
| --- | --- | --- |
| US-20 | As an officer, I want to create a programme with its own application questions. | Implemented (verified in `Workspace\ProgramController`, `FormSchema`) |
| US-21 | As an officer, I want to set open and close dates and a capacity. | Implemented (verified in `ProgramRequest`, `Program::isAcceptingApplications()`) |
| US-22 | As an officer, I want to tell interested members when a programme launches. | Implemented (verified in `ProgramService::announce()`) |
| US-23 | As an officer, I want to export a programme's applications to CSV. | Implemented (verified in `workspace.programs.export`) |
| US-24 | As an officer, I want to award badges to winners and mentors. | Implemented (verified in `Workspace\AchievementController`, `EngagementTest`) |
| US-25 | As an officer, I want to send an announcement to a chosen audience. | Implemented (verified in `Workspace\BroadcastController`) |
| US-26 | As an officer, I want to publish or schedule an intern's draft. | Implemented (verified in `PostService::publishDate()`) |
| US-27 | As an officer, I want to answer enquiries and mark them replied. | Implemented (verified in `Workspace\MessageController`) |
| US-28 | As an officer, I want to maintain projects, partners and publications. | Implemented (verified in `Workspace\ProjectController`, `OrganizationController`, `PublicationController`) |
| US-29 | As an officer, I want to edit site pages without a developer. | Implemented (verified in `Workspace\PageController`) |
| US-30 | As an officer, I want to remind applicants before a deadline automatically. | Proposed (no scheduler) |

## Reviewers

| ID | Story | Status |
| --- | --- | --- |
| US-31 | As a reviewer, I want a queue of open applications, filtered by status. | Implemented (verified in `Workspace\ApplicationController::index()`: defaults to Submitted, Under review, Shortlisted) |
| US-32 | As a reviewer, I want to score and annotate an application privately. | Implemented (verified in `ApplicationService::review()`) |
| US-33 | As a reviewer, I want to decide many applications at once. | Implemented (verified in `bulkReview()`) |
| US-34 | As a reviewer, I want to download an applicant's uploaded PDFs. | Implemented (verified in `workspace.applications.file`) |
| US-35 | As a reviewer, I want to accept a report or ask for changes. | Implemented (verified in `ProgressReportService::review()`) |
| US-36 | As a reviewer, I do not want to see applicants' gender or disability. | Implemented (verified in `EngagementTest`) |

## Interns and read-only staff

| ID | Story | Status |
| --- | --- | --- |
| US-37 | As an intern, I want to write a news post and save it as a draft. | Implemented (verified in `ContentPublishingTest::test_intern_drafts_stay_drafts_even_if_they_ask_to_publish`) |
| US-38 | As an intern, I want to upload photos to an album before it is published. | Implemented (verified in `GalleryTest::test_interns_upload_but_cannot_publish_or_delete_and_viewers_have_no_access`) |
| US-39 | As read-only staff, I want to browse the project portfolio. | Implemented (verified in `Role::Viewer` permissions, `ProjectManagementTest::test_read_only_roles_cannot_write`) |

## Hub Director

| ID | Story | Status |
| --- | --- | --- |
| US-40 | As the Hub Director, I want to create staff accounts and assign roles. | Implemented (verified in `Workspace\UserController`, `UserManagementTest`) |
| US-41 | As the Hub Director, I want to see which role can do what. | Implemented (verified in `Workspace\RoleController`, `PrivilegesTest::test_roles_page_is_director_only_and_shows_the_matrix`) |
| US-42 | As the Hub Director, I want to configure SMTP and send a test e-mail. | Implemented (verified in `workspace.settings.mail`, `workspace.settings.mail.test`) |
| US-43 | As the Hub Director, I want to edit e-mail templates. | Implemented (verified in `Workspace\EmailTemplateController`) |
| US-44 | As the Hub Director, I want to search the audit log. | Implemented (verified in `Workspace\AuditLogController`) |
| US-45 | As the Hub Director, I want to export a PDF report for a date range. | Implemented (verified in `ReportExportTest`) |
| US-46 | As the Hub Director, I want to pause registrations. | Implemented (verified in `SettingsService`) |
| US-47 | As the Hub Director, I want to protect my account with a second factor. | Proposed (F-12) |
| US-48 | As the Hub Director, I want to see which legacy grant applications are still awaiting a decision. | Implemented (verified in `Workspace\GrantApplicationController`) |
