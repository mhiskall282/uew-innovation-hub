# System audit report

**Scope:** whole repository (Laravel 12 app, Blade/Tailwind/Alpine front end, migrations, seeders, Docker, Render blueprint, CI, docs) and black-box checks of the hosted test site.
**Date:** 2026-11-24 · **Baseline commit:** `87df882`

## 1. Baseline (before remediation)

| Check | Result | Evidence |
| --- | --- | --- |
| PHPUnit | 128 passed, 0 failed, 0 skipped | local run, SQLite |
| CI (GitHub Actions) | all 6 jobs green | run for `87df882`: lint, build, tests (SQLite + PostgreSQL), audit, Docker |
| Code style (Pint) | pass | `vendor/bin/pint --test` |
| Front-end build | pass | `npm run build` |
| Blade compile + `php -l` of every compiled view | 0 failures | `view:cache` + lint loop |
| `composer audit --locked` | 0 advisories | |
| `npm audit` | 0 vulnerabilities | |
| Code coverage | not measurable locally (no driver) | PCOV installed for this audit, see TEST_RESULTS.md |
| Routes | 172 registered | `route:list` |

## 2. Architecture summary

Single Laravel monolith. Controllers are split by audience (`Site`, `Portal`, `Workspace`, `Api\V1`, `Auth`), business rules live in `app/Services`, authorisation in `Role::permissions()` (registered as Gates) plus policies. One `users` table for staff and members. Documents are streamed from a private disk. The architecture is appropriate for the scale (a university innovation hub) and is not being changed.

## 3. Findings

Severity follows SECURITY_AUDIT_REPORT.md. Status is updated in the checklist below.

| ID | Severity | Area | Finding |
| --- | --- | --- | --- |
| F-01 | Critical | Hosted site | Demo staff accounts (incl. Hub Director) on the hosted site sign in with the well-known password `password`. `SEED_PASSWORD` is not set in Render, so the boot-time rotation could not run. |
| F-02 | High | Repository | A tester password was committed in `tests/Feature/SecureDemoAccountsTest.php` (commit `87df882`, pushed). Removed from the working tree; still in git history. |
| F-03 | Medium | Auth | Sign-in lockout is keyed on e-mail + IP, so one account can be attacked from many IPs (credential stuffing) without a per-account cap. |
| F-04 | Medium | Content | The default contact phone `+233 (0) 20 000 0000` is a placeholder shown on every public page. |
| F-05 | Low | Auth | One-time-code attempt counter is read-then-increment, not atomic; parallel guesses could exceed 5 per code (route throttle limits impact). |
| F-06 | Low | Content | Demo news post contains "TODO: add quotes from Dr. Appiah." |
| F-07 | Low | SEO / privacy | No `sitemap.xml`; `robots.txt` allows everything; the demo test site is indexable. |
| F-08 | Low | Privacy | Privacy page does not describe cookies or the data added in 1.3 (demographics, messages, progress reports, community profile). Needs human/legal review. |
| F-09 | Low | Accessibility | Animations (`animate-pulse`, transitions) ignore `prefers-reduced-motion`. |
| F-10 | Info | Testing | No browser-level end-to-end, accessibility or responsive tests; no measured performance. |
| F-11 | Info | Security | CSP needs `'unsafe-eval'` for Alpine.js (documented trade-off). |
| F-12 | Info | Security | No multi-factor authentication for staff. Needs working SMTP first (hosted site uses `MAIL_MAILER=log`). |
| F-13 | Info | Dependencies | Laravel 13 / PHPUnit 13 majors available; staying on supported 12.x / 11.x for this release. |
| F-14 | Info | Repository | `legacy/` (24 files of the old procedural app) and `reports/`, `publications/` folders kept as the source for `hub:import-documents`; not web-reachable (document root is `public/`). |
| F-15 | Info | Data protection | No self-service account deletion or data export; privacy page offers deletion by e-mail. |

Verified as **not** issues (with evidence): session regenerated on sign-in and invalidated on sign-out (`SessionController`); codes hashed, single-use, 10-minute expiry, 5 attempts; password reset does not reveal accounts and revokes other sessions; uploads validated as PDF by extension and MIME on the server; `role`, `is_active`, `email_verified_at` not mass-assignable; no raw user input in SQL; CSRF on all forms (live check: 419 without token); `display_errors=Off`, `expose_php=Off`, strict session mode; dotfiles denied by Apache; API has no CORS headers (same-origin only, read-only public data); no secrets in tracked files apart from F-02 (regex scan of all tracked files).

## 4. Remediation checklist

Status: Pending · In progress · Implemented · Tested · Verified · Blocked

| ID | Action | Status |
| --- | --- | --- |
| F-01 | Fail closed: refuse sign-in with a well-known default password in production; rotate on boot when `SEED_PASSWORD` is strong. Set `SEED_PASSWORD` in Render. | see FINAL_PROJECT_HANDOVER_REPORT.md |
| F-02 | Remove value (done); treat as exposed, never use it; history rewrite needs owner approval (force push). | see handover |
| F-03 | Add a per-account limit across all IPs. | see handover |
| F-04 | Default phone to empty (hidden until set in Site settings). | see handover |
| F-05 | Make attempt counting atomic (row lock in a transaction). | see handover |
| F-06 | Replace placeholder text in demo content. | see handover |
| F-07 | `sitemap.xml`, robots rules, `X-Robots-Tag: noindex` unless `SEARCH_INDEXING=true`. | see handover |
| F-08 | Update default privacy text; flag for legal review. | see handover |
| F-09 | Respect reduced motion. | see handover |
| F-10 | Playwright E2E + axe + viewport tests; Lighthouse measurements. | see handover |
| F-11–F-15 | Document as accepted risks / recommendations. | Documented |

Final statuses and evidence for every row are in FINAL_PROJECT_HANDOVER_REPORT.md and TEST_RESULTS.md.
