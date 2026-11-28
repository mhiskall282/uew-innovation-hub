# Security Testing

> **Audience:** Developers, security reviewers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [testing-strategy](testing-strategy.md), [security overview](../08-security/security-overview.md), [authorization security](../08-security/authorization-security.md), [dependency security](../08-security/dependency-security.md), [authorization architecture](../04-architecture/authorization-architecture.md)

This page lists the automated security tests, the repository scan script, the CI dependency audit, and the rule for test fixtures. It separates what exists from what is recommended. The tests are regression tests: they prove that specific behaviours hold. They are not a penetration test.

## Run

```powershell
php artisan test --filter "SecurityHardeningTest|SecurityHeadersTest|SecureDemoAccountsTest|AccessControlTest|PrivilegesTest|DocumentAccessTest|EmailLinksTest|GalleryTest|OperationalFixesTest"
powershell -ExecutionPolicy Bypass -File scripts/security-scan.ps1
```

```bash
php artisan test --filter "SecurityHardeningTest|SecurityHeadersTest|SecureDemoAccountsTest|AccessControlTest|PrivilegesTest|DocumentAccessTest|EmailLinksTest|GalleryTest|OperationalFixesTest"
```

`scripts/security-scan.ps1` is PowerShell and needs `git`, `composer` and `npm` on the path. There is no bash version.

## Existing automated tests

### Authentication and passwords

| Test (file) | What it asserts |
| --- | --- |
| `SecurityHardeningTest::test_default_passwords_are_refused_when_blocking_is_on` | With `hub.block_default_passwords` true, signing in with a default password is refused with the same error for existing and non-existing accounts, nobody is signed in, two `auth.default_password_refused` audit entries exist, and a strong password still works |
| `SecurityHardeningTest::test_default_passwords_still_work_when_blocking_is_off` | With the switch off, default passwords sign in (development use) |
| `SecurityHardeningTest::test_one_account_is_locked_after_many_failures_from_different_ips` | 20 wrong attempts spread over six IP addresses (at most 4 per address, under the per-IP limit of five) lock the account. The right password from a new IP is then refused |
| `SecureDemoAccountsTest` (3 tests) | `hub:secure-demo-accounts` replaces default passwords with `hub.seed_password` and writes `user.password_rotated`. It does nothing and fails when the seed password is weak. `isStrong()` rules |
| `AuthenticationTest::test_sign_in_locks_out_after_five_failures` | Per-IP lockout after five failures |
| `AuthenticationTest::test_wrong_password_and_unknown_email_get_the_same_message` | No account enumeration at sign-in |
| `AuthenticationTest::test_staff_sign_in_lands_in_the_workspace_and_regenerates_the_session` | Session fixation protection |
| `AuthenticationTest::test_deactivated_accounts_cannot_sign_in`, `..._a_user_deactivated_mid_session_is_signed_out_on_the_next_request`, `PrivilegesTest::test_deactivated_staff_are_signed_out_on_their_next_request` | Deactivation takes effect at once |
| `AuthenticationTest::test_sign_out_invalidates_the_session` | Sign-out |
| `AuthenticationTest::test_sign_in_page_does_not_leak_credentials_or_demo_accounts` | The sign-in page shows no demo credentials |
| `MemberAccountTest` | Hashed verification code in the database, 5-attempt limit, expiry, resend throttle, role cannot be injected at sign-up, forgot-password does not reveal accounts |
| `UserManagementTest::test_password_reset_by_admin_rotates_the_remember_token` | Old "remember me" cookies stop working |

### Authorisation

| Test (file) | What it asserts |
| --- | --- |
| `AccessControlTest::test_role_can_reach_exactly_its_pages` (6 data sets) | For each role, 23 workspace pages return 200 only where the role's list allows, and 403 elsewhere. Members have no workspace access at all |
| `AccessControlTest::test_guests_are_sent_to_sign_in_for_every_workspace_page` | Guests are redirected |
| `AccessControlTest::test_sidebar_only_shows_links_the_role_can_use`, `..._workspace_pages_are_not_indexed_by_search_engines` | Navigation and `noindex` |
| `PrivilegesTest::test_every_state_changing_workspace_route_enforces_its_permission` | See the pair count below |
| `PrivilegesTest::test_nobody_can_change_their_own_role` | Self role change refused with a message |
| `PrivilegesTest::test_only_users_manage_can_change_roles_and_nobody_escalates_through_their_profile` | Posting `role=super_admin` or `is_active=0` to the profile route changes nothing |
| `PrivilegesTest::test_staff_form_cannot_turn_anyone_into_a_member` | The staff form rejects `member` |
| `PrivilegesTest::test_role_changes_are_audited_with_old_and_new_values` | `user.updated` log has `{from, to}` |
| `PrivilegesTest::test_officers_help_members_but_not_staff` | Officer cannot reset staff passwords |
| `PrivilegesTest::test_roles_page_is_director_only_and_shows_the_matrix` | Roles page is Hub Director only |
| `UserManagementTest` | Role mass-assignment refused, self-deactivation and self-delete refused, the last active Hub Director cannot be demoted |
| `SecurityHardeningTest::test_reviewers_cannot_be_given_extra_rights_by_posting_role_fields` | A member posting `role`, `is_active` and `xp` to the portal profile keeps role `member` and the same XP |
| `Portal/EngagementTest::test_reviewers_do_not_see_gender_or_disability` | Reviewers do not see sensitive profile fields |
| `Portal/EngagementTest::test_reports_are_limited_to_accepted_applications_and_their_owner`, `..._applicant_and_staff_can_message_but_strangers_cannot`, `..._notification_links_only_follow_local_paths_and_belong_to_their_owner` | Ownership checks and open-redirect protection on notification links |

**Role/route pair count (verified).** `PrivilegesTest` visits every `POST`, `PUT`, `PATCH` and `DELETE` route whose URI starts with `workspace`, skipping redirect routes. At commit `d1296b1` there are **53** such routes, and all 53 carry a `can:` middleware. For each route, every role that lacks the permission must get 403 or 404. That makes **144 role/route pairs**, which matches the comment in the test. I recomputed this by running the same loop against the route table. The test asserts only `>= 120`, so a drop from 144 to 121 would pass silently. Guests are also checked to be redirected to `login`.

> **Gap:** `PrivilegesTest` only covers `/workspace` routes. State-changing routes in `/portal`, and public POST routes (contact form, unsubscribe), are covered by their own feature tests, not by a loop. Routes without a `can:` middleware are not checked by the loop.

### Headers, CSP, CSRF, indexing

| Test (file) | What it asserts |
| --- | --- |
| `SecurityHeadersTest::test_web_responses_carry_security_headers_and_a_strict_csp` | `/` has `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, and a CSP with `default-src 'self'`, `object-src 'none'`, `frame-ancestors 'self'`, and no `cdn.` host |
| `SecurityHeadersTest::test_api_responses_carry_security_headers` | `/api/v1` has `nosniff` |
| `SecurityHeadersTest::test_health_check_is_available` | `/up` returns 200 |
| `SecurityHeadersTest::test_forms_are_csrf_protected` | The CSRF middleware is registered in the `web` group (Laravel skips the check during tests, so this checks configuration only) |
| `SecurityHardeningTest::test_robots_and_sitemap_follow_the_indexing_setting` | `noindex` header and `Disallow: /` when indexing is off; private areas stay `noindex` |
| `SecurityHardeningTest::test_sitemap_lists_only_public_content` | Draft programmes and `/workspace` are not in the sitemap |

The browser tests add a second check: `watchConsole()` in `tests/e2e/helpers.ts` fails a journey on any console error, which includes CSP violations. See [end-to-end-testing](end-to-end-testing.md).

### Files and documents

| Test (file) | What it asserts |
| --- | --- |
| `DocumentAccessTest::test_public_project_report_streams_as_pdf_with_nosniff` | PDF content type and `nosniff` |
| `DocumentAccessTest::test_internal_project_reports_are_not_public` | Internal project report is 404 publicly, 200 for staff |
| `DocumentAccessTest::test_stored_paths_cannot_escape_their_folder` | A stored path `../proposals/applicant.pdf` returns 404 (path traversal) |
| `DocumentAccessTest::test_grant_proposals_are_restricted_to_reviewers` | Guest redirected, Intern 403, Reviewer 200 |
| `DocumentAccessTest::test_missing_files_return_404`, `..._legacy_proposals_stored_with_reports_are_still_found` | Missing and legacy files |
| `GalleryTest::test_non_images_disguised_files_and_tiny_images_are_rejected` | A text file named `.jpg`, a PDF and a 100 x 100 image are each rejected with `photos.0` errors, no `AlbumPhoto` row and no stored file |
| `GalleryTest::test_officer_creates_an_album_and_uploads_photos_which_are_resized_and_stripped` | A marker string placed in the EXIF block is absent from the stored large and thumbnail files; large image is resized to 1600 wide, thumbnail 640 |
| `GalleryTest::test_interns_upload_but_cannot_publish_or_delete_and_viewers_have_no_access` | Interns cannot publish, feature or delete; viewers get 403 |
| `GalleryTest::test_photos_in_draft_albums_are_hidden_from_the_public`, `..._a_photo_cannot_be_deleted_through_another_album` | Draft photos 404; cross-album delete refused |
| `ProjectManagementTest::test_replacing_a_report_deletes_the_old_file` | No orphan files |

Upload size and count limits (`docker/php.ini`: `upload_max_filesize` 25M, `post_max_size` 110M, `max_file_uploads` 12) are not tested, because PHPUnit does not run through PHP's upload layer.

### E-mail, injection and output safety

| Test (file) | What it asserts |
| --- | --- |
| `EmailLinksTest::test_verification_button_confirms_on_post_only_and_works_once` | Mail scanners that open links by GET do not confirm; one-time use |
| `EmailLinksTest::test_tampered_expired_and_replaced_links_are_refused` | Signed link integrity |
| `EmailLinksTest::test_reset_button_sets_a_new_password_once_and_revokes_sessions`, `..._reset_links_do_not_work_for_suspended_accounts` | Reset safety |
| `EmailLinksTest::test_member_supplied_text_cannot_inject_html_into_emails` | `<img onerror>` and a `javascript:` Markdown link do not appear as live HTML in the mail body |
| `EmailLinksTest::test_broadcasts_carry_one_click_unsubscribe_headers_that_work` | `List-Unsubscribe-Post: List-Unsubscribe=One-Click` and a working link |
| `AdministrationTest::test_cms_page_markdown_cannot_inject_html_or_scripts`, `..._cms_pages_cannot_take_reserved_or_existing_slugs` | Markdown sanitising and slug protection |
| `AdministrationTest::test_smtp_password_is_stored_encrypted_and_kept_when_left_blank` | The SMTP password is encrypted at rest |
| `AdministrationTest::test_contact_form_stores_messages_and_rejects_bots`, `..._contact_form_is_rate_limited` | Bot submissions are rejected, and the form answers HTTP 429 when rate-limited |
| `ProjectManagementTest::test_csv_export_streams_filtered_rows_and_neutralises_formulas` | CSV formula injection is neutralised |
| `PublicSiteTest::test_partner_contact_emails_are_not_published`, `PublicApiTest::test_partners_endpoint_never_exposes_contact_emails` | Private data is not exposed |

### Audit and export controls (`OperationalFixesTest`)

| Test | What it asserts |
| --- | --- |
| `test_csv_exports_of_members_projects_and_applications_are_audited` | A Hub Director's export of members, projects and programme applications writes `member.exported`, `report.projects_exported` and `application.exported`, one entry each, with the user id |
| `test_viewers_cannot_export_member_data` | A Viewer gets 403 on `workspace.members.export`, and no `member.exported` entry exists |
| `test_audit_log_can_be_filtered_by_every_recorded_area` | The audit filter works for an `album` area (only one area is exercised, not all 18) |
| `test_a_failed_broadcast_is_marked_failed_and_audited_instead_of_stuck_sending` | `broadcast.failed` audit entry |

## `scripts/security-scan.ps1`

A read-only PowerShell script. It changes nothing. It is not run by CI. Run it before a release and after any dependency change.

| Section | What it does |
| --- | --- |
| Dependencies | `composer audit --locked` and `npm audit` (last two lines of each) |
| Supply chain | `npm audit signatures`, `composer validate`, `composer check-platform-reqs --lock`, abandoned packages report |
| Executable files | Lists `.php`, `.phtml`, `.phar`, `.sh`, `.exe`, `.dll` and similar files under `public` and `storage`, except compiled views and `public/index.php` |
| Web-shell patterns | `git grep` for patterns such as `eval(base64_decode(`, `system($_GET`, `assert($_`, `create_function(` in `app`, `routes`, `config`, `bootstrap`, `public`, `resources`, `database`, `tests`, `scripts` |
| Dangerous functions | Lists uses of `exec`, `shell_exec`, `system`, `passthru`, `proc_open`, `popen`, `unserialize`, `eval` in `app/` for review |
| Raw SQL | `whereRaw`, `selectRaw`, `DB::raw` and similar with an interpolated variable in `app/` |
| Secrets in tracked files | Patterns for AWS keys, private keys, GitHub tokens, Slack tokens, `MAIL_PASSWORD=` and `APP_KEY=base64:` |
| Secrets in history | Same patterns across all commits, with values redacted in the output. It also reports whether the tester password committed in `87df882` is still in history |
| Files that must not be tracked | `.env`, `*.sqlite`, keys, logs; and whether `.env` is git-ignored |
| Repository integrity | `git fsck`, remote URL, unpushed commits |
| Production configuration | `APP_DEBUG` in `render.yaml`, `display_errors` and `expose_php` in `docker/php.ini`, and whether the queue worker runs as `www-data` |

A clean run prints "none" for each search. The script fails nothing: a person must read the output. `tests` is inside the web-shell search, so a test fixture that contains such a string is also flagged by the script.

> **Known finding:** the tester password in commit `87df882` is still in git history, so the scan reports it. Rotating that password and, if needed, rewriting history are decisions for the maintainers. This page does not give the value.

## Rule: test fixtures must not contain real attack payloads

Windows Defender flagged an earlier fixture as a web shell (a "WebShell" false positive) and quarantined the file. Commit `9f3bdee` ("remove web-shell string from a test fixture") fixed it and added the scan script. `GalleryTest::test_non_images_disguised_files_and_tiny_images_are_rejected` now uses harmless text ("This text file only pretends to be a photo.") and says why in a comment.

Follow these rules when you write fixtures:

1. To test that a "disguised" upload is refused, use harmless content with the wrong extension or MIME type. The validator checks the file type, not the payload.
2. Do not write strings that look like web shells, backdoors, malware signatures, or real exploit payloads into `tests/`, even inside a string literal. Antivirus scans the source and temp files.
3. Short, inert probes are fine, for example `<img src=x onerror=alert(1)>` in `EmailLinksTest` and `javascript:alert(2)` in a Markdown link. They are not executable on the server and Defender did not flag them. Keep them short.
4. Never put a real password, token or API key in a fixture. Use obviously fake values. Several tests use throwaway passwords that exist only inside the test database.
5. Run `scripts/security-scan.ps1` before committing a new fixture and read the "Web-shell patterns" section.

## CI dependency audit

Job `security` ("Dependency audit") in `../../.github/workflows/ci.yml` runs on every push and pull request:

```text
composer audit --locked --no-interaction
npm audit --omit=dev --audit-level=high
```

| Point | Detail |
| --- | --- |
| Composer | Fails on any advisory in `composer.lock` |
| npm | Fails only for `high` or `critical` advisories in production dependencies. Development tools (Playwright, axe-core, Vite) are excluded by `--omit=dev`. The local scan runs plain `npm audit`, which is stricter |
| Not covered | License checks, abandoned-package failure, signatures (`npm audit signatures`), secret scanning, SAST, container image scanning. No Dependabot configuration exists |
| Scheduling | Runs only when code is pushed. There is no scheduled run, so a new advisory on an idle repository is not noticed until the next push |

## Recommended, not implemented

- Add a scheduled (weekly) run of the `security` job and enable Dependabot or Renovate.
- Run `scripts/security-scan.ps1` logic in CI (a bash version, or `pwsh` on the Ubuntu runner).
- Add a secret scanner (for example gitleaks) and CodeQL or Semgrep.
- Make `PrivilegesTest` assert the exact pair count (144) so a silent drop is caught, and extend it to `/portal` routes.
- Add tests for: HTTP 429 on sign-in via the real throttle (not config), session cookie flags (`HttpOnly`, `Secure`, `SameSite`) in production config, `Strict-Transport-Security` if the site sets it (verify first), and a forced password change (not implemented in the app).
- Add a DAST pass (OWASP ZAP baseline) against a preview deployment, only against a site you own.
- Write ownership tests for every portal resource (applications, reports, messages, notifications) in one data-driven test.
