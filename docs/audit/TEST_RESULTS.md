# Test results

## Update: 2026-11-27 (release 1.4.0)

| Check | Result |
| --- | --- |
| PHPUnit, SQLite (local) | **177 passed, 0 failed** (1,279 assertions) |
| CI on `c41cf32` | all 7 jobs green: code style, build, SQLite tests, PostgreSQL tests, browser tests (Chromium, Firefox, WebKit, Android and iOS emulation), dependency audit, Docker build |
| Playwright viewport suite (local, Edge) | 14/14 at 320, 375, 390, 768, 1024, 1440 and 1920px. It caught a 44px header overflow at 1024px after Gallery joined the menu; fixed. |
| Permission matrix (`PrivilegesTest`) | 144 role-and-route pairs: every state-changing workspace route refuses each role without its permission, and guests are sent to sign in |
| Gallery (`GalleryTest`, 10 tests) | re-encode and resize, EXIF/GPS removed, disguised PHP and PDF rejected, intern vs officer rights, draft photos hidden, cache headers, file cleanup, lost files hidden |
| Live e-mail, Gmail SMTP, new UEW design with hosted crest | **15/15 sent to hello@johnokyere.xyz and 15/15 to mdza@uew.edu.gh**, 0 problems. Delivery and appearance in the inbox are for the recipients to confirm. |
| Live site | admin sign-in and sign-out work; `/`, `/gallery` and `/sitemap.xml` return 200; `/workspace/roles` and `/workspace/albums` redirect guests to sign-in |

---

Date: 2026-11-26. Everything below was run, not estimated. "Not run" is stated where it applies.

## 1. PHPUnit (unit, feature, integration, security)

| Run | Result |
| --- | --- |
| Full suite, SQLite | **148 passed, 0 failed, 0 skipped** (895 assertions) |
| CI, PostgreSQL 16 | passes on every push (see GitHub Actions) |
| Line coverage of `app/` (PCOV) | **82.0%** of lines, 76.3% of methods, measured before the 13 `AdministrationTest` cases were added. Not re-measured afterwards. |

New suites added in this audit:
- `SecurityHardeningTest`: default-password refusal, per-account lockout across IPs (credential stuffing), robots and sitemap rules, sitemap listing only public content, no placeholder phone number, mass-assignment of `role` and `xp`.
- `AdministrationTest`: programme builder validation and RBAC; SMTP password stored encrypted and never echoed; settings reaching the public site; broadcast audience and signed unsubscribe; Markdown XSS (`<script>`, `javascript:` links, `onerror`); reserved CMS slugs; contact form honeypot and rate limit; password change rules; idempotent `hub:create-admin`.
- Existing: `RouteSmokeTest` (every GET route as a guest and as 7 accounts, no 5xx), `EngagementTest`, `ReportExportTest`, `SecureDemoAccountsTest`, access-control matrix, auth, API and security headers.

## 2. Browser tests (Playwright)

Run against an isolated SQLite database rebuilt from synthetic demo data. Engines were real browser engines; the mobile runs used device emulation, not physical phones.

| Target | Journeys | Accessibility (axe, WCAG 2.2 A/AA) | Responsive |
| --- | --- | --- | --- |
| Microsoft Edge (Chromium), desktop | 22/22 passed | 16/16 passed | 14/14 passed (320 to 1920px) |
| Android emulation (Pixel 7, Edge engine) | 22/22 passed | n/a | n/a |
| Firefox 159, desktop | 22/22 passed | 16/16 passed (one timeout after the PC slept, passed on rerun) | n/a |
| WebKit (Safari engine), desktop and iPhone emulation | **Not run locally**: the disk was full (0.2 GB free) and the download failed | runs in CI | runs in CI |

Defects found by these tests and fixed:
- 11 pages failed WCAG contrast (grey text at 2.4 to 2.6:1). Fixed: text on light backgrounds moved to `slate-500` (4.4 to 4.8:1); the dark sidebar keeps the lighter grey (7.3:1).
- 18 progress bars had no accessible name. Fixed in the shared component.
- Pages scrolled sideways on phones: programme tabs at 320px; portal and workspace at 320 to 390px. Root causes were grid items with `min-width: auto`, card header actions that did not wrap, and `sr-only` labels escaping table scrollers. All fixed in shared CSS and components.

## 3. Live e-mail test (Gmail SMTP)

`php scripts/email-flow-test.php` ran every e-mail flow through `smtp.gmail.com:587` against a throwaway copy of the dev database. `MAIL_ALWAYS_TO` sent every message to the tester's inbox.

| Flow | Result |
| --- | --- |
| SMTP test e-mail (Site settings) | sent |
| Sign-up verification code | sent; the code from the e-mail verified the account |
| Welcome e-mail after verification | sent |
| Forgot-password code | sent; the code reset the password |
| "Password changed" notice (member and staff) | sent |
| Application received (applicant) and new application (3 staff) | sent |
| Review decision (status e-mail) | sent |
| Contact enquiry alert (2 staff) | sent |
| Broadcast | sent |
| Legacy grant decision | sent |

**15 of 15 messages accepted by Gmail**, each with a Gmail message id. Delivery to the inbox is for the tester to confirm.

## 4. Sign-in checks through the real web forms

| Site | Check | Result |
| --- | --- | --- |
| Local | admin and member sign-in, landing page, sign-out, protected page afterwards | pass (lands on `/workspace` or `/portal`; afterwards redirects to `/sign-in`) |
| Local | web sign-up | pass (redirects to `/verify-email`, code e-mail sent) |
| Local | wrong password | generic "These details do not match an active account." |
| Live (`uew-innovation-hub.onrender.com`) | admin, reviewer and member sign-in and sign-out | pass |

## 5. Not measured

- **Performance and Core Web Vitals:** the measurement script (`tests/e2e/measure-performance.mjs`) is in the repo, but its run was cancelled, so there are no figures. Lighthouse was not run (no disk space for another browser).
- **Load testing:** not run.
- **OWASP ZAP / DAST scan:** not run (not installed; no disk space). The checks done are listed in SYSTEM_AUDIT_REPORT.md.
