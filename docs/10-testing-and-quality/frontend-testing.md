# Frontend Testing

> **Audience:** Developers, designers, QA · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [testing-strategy](testing-strategy.md), [end-to-end-testing](end-to-end-testing.md), [frontend architecture](../04-architecture/frontend-architecture.md), [frontend development](../05-developer-guide/frontend-development.md), [frontend problems](../11-troubleshooting/frontend-problems.md)

The front end is server-rendered Blade with Tailwind CSS 4, Alpine.js 3 and Vite 7. There is almost no custom JavaScript (`resources/js/app.js` imports two self-hosted fonts and starts Alpine). For that reason the project has no JavaScript unit tests and no component tests. Front-end quality is checked by the Vite build, a Blade compile check in CI, PHPUnit assertions on rendered HTML, and the Playwright suite. This page lists those checks and the gaps.

## What exists

| Check | Where | What it catches |
| --- | --- | --- |
| `npm run build` (`vite build`) | CI job `assets`, and before every Playwright run | Broken CSS or JS imports, Tailwind errors. The output (`public/build`) is uploaded as an artefact and reused by the `tests` and `e2e` jobs |
| Blade compile check | CI job `tests`: `php artisan view:cache`, then `php -l` on each compiled view in `storage/framework/views`, then `view:clear` | Syntax errors in any of the 142 Blade templates, including pages no test visits |
| Rendered-HTML assertions | PHPUnit feature tests (`assertSee`, `assertDontSee`) | Role-based navigation, public pages without staff internals, notification and e-mail text, no pre-ticked opt-in |
| Route smoke test | `RouteSmokeTest` | Every named GET route renders without a server error for eight kinds of actor, which catches a view that throws |
| Journeys | `tests/e2e/journeys.spec.ts` | Pages load in four engines and two phone profiles, navigation works (including the mobile menu button), forms behave, no JavaScript or CSP console errors |
| Accessibility | `tests/e2e/a11y.spec.ts` | axe-core violations for WCAG 2.0, 2.1 and 2.2 A and AA on 13 public pages, six member pages, seven staff pages. Skip link and visible focus |
| Responsive layout | `tests/e2e/responsive.spec.ts` | Horizontal scroll and broken images at 320, 375, 390, 768, 1024, 1440, 1920 px |
| CSP | `SecurityHeadersTest` and the console watcher in `helpers.ts` | The policy is strict (`default-src 'self'`, no CDN host). Inline scripts or third-party assets that violate it appear as console errors and fail journeys |

### Rendered-HTML assertions worth knowing

| Test | Assertion |
| --- | --- |
| `Workspace/AccessControlTest::test_sidebar_only_shows_links_the_role_can_use` and `PrivilegesTest::test_sidebar_only_lists_what_the_role_can_open` | An Intern sees "Gallery" and "News & posts" but not "Staff accounts", "Roles & permissions", "Site settings" or "Members" |
| `Site/PublicSiteTest::test_public_pages_render_with_account_links_but_no_staff_internals` (8 data sets) | Eight public pages render with sign-in links and without staff-only text |
| `Workspace/OperationalFixesTest::test_announcement_emails_are_not_pre_ticked_during_onboarding` | The marketing opt-in checkbox has no `checked` attribute |
| `Workspace/PrivilegesTest::test_roles_page_is_director_only_and_shows_the_matrix` | The roles page shows "Permission matrix" and labels |
| `SecurityHardeningTest::test_placeholder_phone_number_is_not_shown` | The contact page does not show a placeholder phone number |
| `Auth/EmailLinksTest::test_code_emails_are_branded_with_button_and_inline_styles` | E-mail HTML has a button and inline styles (mail clients ignore external CSS) |

Matching on visible text makes these tests sensitive to copy changes. When you reword a label, search `tests/` for the old text. Playwright specs also find elements by label and role (`getByLabel('E-mail address')`, `getByRole('button', { name: 'Sign in' })`), so renaming a label or an `aria-label` can break them.

## Commands

```powershell
npm run build                                   # what CI runs
php artisan view:cache; php artisan view:clear  # compile all Blade views locally
npm run test:e2e:chromium                       # journeys, a11y, responsive in Chromium
npx playwright test tests/e2e/a11y.spec.ts --project=chromium
node tests/e2e/find-overflow.mjs http://127.0.0.1:8000 320
```

```bash
npm run build
php artisan view:cache && php artisan view:clear
npm run test:e2e:chromium
```

`find-overflow.mjs` signs in as the member and the director demo accounts and prints the outermost elements that stick out of the viewport on each page. Start `php artisan serve` first, with demo data. Use it when `responsive.spec.ts` reports `page is 412px wide in a 390px viewport`.

## Manual checks for a UI change

Automated tests do not judge appearance. For a change to a layout, component or colour, check by hand:

1. Open the page at 320 px, 768 px and 1440 px (browser dev tools). Nothing scrolls sideways, text is not cut off.
2. Tab through the page. Focus is always visible, the order matches the visual order, the skip link works.
3. Zoom to 200 percent. Content still reads.
4. Check the page as each role that can see it (Hub Director, Officer, Reviewer, Intern, Viewer, Member), because sidebar and buttons depend on permissions.
5. Check contrast of new colours against the brand palette rules in `.ai/brand.md` (deep navy, royal blue, imperial purple, academic gold). axe-core reports contrast failures it can compute, but not on images or gradients.
6. Check the print or PDF view if the page feeds a PDF export.

## Gaps

| Gap | Detail |
| --- | --- |
| No JavaScript unit or component tests | Alpine behaviour (menus, toggles and any dynamic form rows) is exercised only if a Playwright journey touches it. The mobile menu is. Progress-report budget lines and the programme form builder have PHPUnit tests for the server side only. **Requires confirmation:** which of these screens use Alpine in the browser |
| No linting for JS, CSS or Blade | Only PHP is linted (Pint). No ESLint, Stylelint, Prettier or Blade formatter |
| No visual regression | No screenshot comparison between releases |
| Responsive tests in Chromium only | Firefox and WebKit run journeys and a11y, not viewport checks. The two phone projects run journeys only |
| Real devices | None. `Pixel 7` and `iPhone 14` are Playwright emulations |
| Manual accessibility audit | Referenced by `a11y.spec.ts` as `docs/audit/ACCESSIBILITY_AUDIT_REPORT.md`, which does not exist |
| Empty view folders and `components/npontu` | The brief lists `admin`, `dashboard`, `grants`, `organizations`, `posts`, `projects`, `publications`, `reports` view folders as possible leftovers. No test renders them directly. **Requires confirmation:** whether any controller renders them; check with `grep -r "view(" app/Http` before deleting |
| E-mail clients | HTML e-mail is tested as a string. No rendering test in real mail clients |

## Recommended, not implemented

- Add ESLint for `resources/js` and Prettier or `prettier-plugin-tailwindcss` for class order.
- Add a small Playwright test for each Alpine-driven widget (mobile menu, report date range picker, and the progress-report and form-builder screens if they use Alpine). The mobile menu is already used by the navigation journey.
- Add Playwright screenshot comparison for the home page, a programme page and the workspace dashboard, in one browser, with a fixed viewport and seeded data.
- Add a Lighthouse CI run on the e2e server for performance and accessibility scores, as an advisory step first ([performance-testing](performance-testing.md)).
- Add a colour-contrast table for the brand palette in the brand guide, and test the pairs in a unit test.
- Test e-mail HTML with an e-mail testing service or Litmus-style tool before changing templates.

