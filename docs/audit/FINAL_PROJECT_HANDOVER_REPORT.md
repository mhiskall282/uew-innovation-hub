# Final handover report

Date: 2026-11-26. Companion documents: [SYSTEM_AUDIT_REPORT.md](SYSTEM_AUDIT_REPORT.md) (findings and baseline) and [TEST_RESULTS.md](TEST_RESULTS.md) (evidence).

## 1. Status of audit findings

| ID | Severity | Finding | Status | Evidence |
| --- | --- | --- | --- | --- |
| F-01 | Critical | Hosted demo accounts, including the Hub Director, use the password `password` | **Open: needs you** (see section 4). Code fix ready: `hub:secure-demo-accounts` runs on every boot and moves those accounts to `SEED_PASSWORD` once it is set and strong; `BLOCK_DEFAULT_PASSWORDS=true` then refuses default passwords outright. | `SecureDemoAccountsTest`, `SecurityHardeningTest`; live check: `password` still signs in |
| F-02 | High | A tester password was committed (commit `87df882`) | **Mitigated**: removed from the code. Still in git history; treat it as public and don't use it. Rewriting history needs a force push (your call). | secret scan of tracked and untracked files: 0 hits |
| F-03 | Medium | No per-account limit against credential stuffing | **Verified fixed**: 20 failures per account across all IPs, plus 5 per e-mail and IP | `SecurityHardeningTest::test_one_account_is_locked_after_many_failures_from_different_ips` |
| F-04 | Medium | Placeholder phone number shown publicly | **Verified fixed**: hidden until set in Site settings | `test_placeholder_phone_number_is_not_shown` |
| F-05 | Low | One-time-code attempt count not atomic | **Fixed**: row lock in a transaction | auth and member tests pass (SQLite and PostgreSQL) |
| F-06 | Low | "TODO" text in demo content | **Fixed** | |
| F-07 | Low | No sitemap; test site indexable | **Verified fixed**: `/sitemap.xml` (public content only), dynamic `/robots.txt`, `X-Robots-Tag: noindex` unless `SEARCH_INDEXING=true` | `test_robots_and_sitemap_*`, `test_sitemap_lists_only_public_content` |
| F-08 | Low | Privacy notice incomplete | **Fixed for new installs; needs legal review.** Default text now covers demographics, the reviewer restriction, the community page and cookies. An existing site keeps its edited page, so update it under Pages. | |
| F-09 | Low | Animations ignore reduced-motion | **Fixed** (`prefers-reduced-motion`) | |
| F-10 | Info | No browser, accessibility or responsive tests | **Done**: Playwright suites, now in CI | TEST_RESULTS.md section 2 |
| F-11 | Info | CSP allows `'unsafe-eval'` (Alpine.js) | Accepted risk | |
| F-12 | Info | No MFA for staff | Recommendation (needs working e-mail in production first) | |
| F-13 | Info | Laravel 13 / PHPUnit 13 available | Deferred; 12.x and 11.x are supported | |
| F-14 | Info | `legacy/`, `reports/`, `publications/` folders | Kept as the document import source; not web-reachable | |
| F-15 | Info | No self-service account deletion | Recommendation; deletion is on request by e-mail | |

Also fixed during testing: 11 WCAG contrast failures; 18 unnamed progress bars; horizontal scrolling on phones (320 to 390px); and the first Hub Director (`hub:create-admin`) being created unverified, which could lock them out before e-mail is set up.

Added: Gmail/SMTP e-mail verified end to end; `MAIL_ALWAYS_TO` safety net for non-production; `scripts/email-flow-test.php` and `scripts/signin-check.ps1`; reserved CMS slugs for `community`, `notifications` and others; Organization structured data on the home page.

## 2. Readiness score (evidence-based)

Each area is scored on what was demonstrated, not what the code suggests.

| Area | Score | Basis |
| --- | --- | --- |
| Functionality | 90 | Every route as every role with no 5xx; journeys pass on 3 engines; all 11 e-mail flows deliver |
| Security | 70 | Strong controls verified by tests, but F-01 (Critical) is open on the live site until `SEED_PASSWORD` is set; no DAST scan; F-02 still in history |
| Code quality | 88 | Pint clean, layered services and policies, no TODO/FIXME left |
| Performance | 60 | Not measured (run cancelled). Assets are fingerprinted and cached; heavy queries are paginated or batched |
| Accessibility | 85 | axe WCAG 2.2 A/AA: 0 violations on 30+ screens in Chromium and Firefox; keyboard and skip link tested; no screen-reader session |
| UI/UX | 85 | Consistent UEW branding; responsive 320 to 1920px with no overflow; no placeholder content |
| Testing | 88 | 148 PHPUnit tests plus 172 browser tests; 82% line coverage; WebKit/iOS only in CI |
| Documentation | 80 | Guides, API, security, deployment, audit and test results; the full set of separate reports requested was not produced |
| Deployment | 80 | CI gates (lint, build, tests on 2 databases, audits, Docker, browsers); auto-deploy; health check. Needs paid database and storage for launch |
| **Overall** | **81 / 100** | Unweighted mean, rounded |

## 3. Final status

**READY FOR USER ACCEPTANCE TESTING, once you complete the first item in section 4.** It is not ready for production approval until the remaining items in section 4 are done and UAT is signed off.

## 4. What needs you

1. **Set `SEED_PASSWORD` in Render** (Dashboard → uew-innovation-hub → Environment) to a strong value: 12+ characters with upper and lower case and a number. On the next deploy every demo account moves to it. Then set `BLOCK_DEFAULT_PASSWORDS=true`. Until then, anyone can sign in to the live Hub Director account with `password`.
2. **Turn on e-mail on the live site.** Live e-mail is currently `log`, so new online sign-ups never receive their code. Sign in as the Hub Director → Site settings → E-mail: SMTP, `smtp.gmail.com`, port 587, STARTTLS, your Gmail address and app password, then **Send test e-mail**. The password is stored encrypted. When UEW gives you the official mailbox, change it in the same place.
3. Decide whether to rewrite git history to remove the committed tester password (F-02). Either way, don't use that password anywhere.
4. Before launch: a paid or Neon PostgreSQL database (Render's free one expires after 30 days), S3/R2 storage for uploads, `SEED_DEMO_DATA=false` with a fresh database, `SEARCH_INDEXING=true`, and legal review of the Privacy and Terms pages.
