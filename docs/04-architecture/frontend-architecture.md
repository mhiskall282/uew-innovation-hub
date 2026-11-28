# Frontend Architecture

> **Audience:** Developers, designers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Application architecture](application-architecture.md), [Frontend development](../05-developer-guide/frontend-development.md), [Frontend testing](../10-testing-and-quality/frontend-testing.md), [Security overview](../08-security/security-overview.md)

The interface is server-rendered Blade with Tailwind CSS v4 for styling and Alpine.js for small interactive pieces. There is no single-page app framework and no client-side router. Vite builds one CSS file and one JavaScript file. Fonts are self-hosted so the site runs under a strict Content Security Policy.

## Stack

| Concern | Choice | Source |
| --- | --- | --- |
| Templates | Blade, anonymous components in `resources/views/components` | `resources/views` |
| CSS | Tailwind CSS 4 (`tailwindcss`, `@tailwindcss/vite`), CSS-first config with `@theme` | `resources/css/app.css`, `package.json` |
| JavaScript | Alpine.js 3 (`alpinejs`), axios (only sets `X-Requested-With`) | `resources/js/app.js`, `resources/js/bootstrap.js` |
| Build | Vite 7 with `laravel-vite-plugin` | `vite.config.js` |
| Fonts | `@fontsource-variable/inter`, `@fontsource-variable/jetbrains-mono`, bundled by Vite | `resources/js/app.js` |
| Browser tests | Playwright with axe-core | `tests/e2e`, `playwright.config.ts` |

`vite.config.js` has two inputs, `resources/css/app.css` and `resources/js/app.js`, plus `refresh: true` and a watch exclusion for compiled views. The Docker build runs `npm ci` then `npm run build` in a Node 22 stage and copies `public/build` into the final image. `COPY app/Enums` is in that stage because Tailwind scans enum files for class names (`@source '../../app/Enums/*.php'`).

## Layouts

Layouts are Blade components in `resources/views/components/layouts`, used as `<x-layouts.site>` and so on.

| Layout | Used for | Notes |
| --- | --- | --- |
| [`site`](../../resources/views/components/layouts/site.blade.php) | Public website | Header, footer, flash banner, JSON-LD organisation data, loads Vite CSS and JS. |
| [`auth`](../../resources/views/components/layouts/auth.blade.php) | Sign-in, sign-up, verification, reset | Minimal chrome. |
| [`portal`](../../resources/views/components/layouts/portal.blade.php) | Member portal | Wraps the site chrome and adds an account tab bar (Overview, My applications, Progress reports, Notifications, Profile). It has no `@vite` of its own. |
| [`workspace`](../../resources/views/components/layouts/workspace.blade.php) | Staff workspace | Sidebar declared as one array of groups. An item shows only when `$user->can($item['can'])`. Badges show open applications, awaiting reports, open legacy grants and new enquiries. Sets `<meta name="robots" content="noindex, nofollow">`. |
| `errors/layout` | 403, 404, 419, 429, 500, 503 | Loads the CSS only, no JavaScript. |

The workspace layout calls `MetricsService` and runs a few count queries on each page for the sidebar badges. `MetricsService` caches its figures for `METRICS_CACHE_TTL` seconds (default 300).

## Components

| Folder | Components | Purpose |
| --- | --- | --- |
| `components/layouts` | `site`, `auth`, `portal`, `workspace` | Page shells (above). |
| `components/ui` | `alert`, `badge`, `button`, `card`, `checkbox`, `confirm-form`, `crest`, `empty`, `field`, `icon`, `input`, `notification-bell`, `page-header`, `page-hero`, `progress`, `select`, `stat`, `textarea` | Design-system pieces. `button` has variants `primary`, `gold`, `secondary`, `ghost`, `danger`, `danger-ghost` and sizes `xs` to `lg`. `icon` is an inline SVG set (about 66 lines of path data). |
| `components/emails` | `layout`, `button`, `panel` | Table-based e-mail shell with inline styles. |
| `components/npontu` | none | The folder exists but is **empty**. The "Npontu" dashboard styling described in `.ai/decisions.md` (ADR-003) is implemented in `layouts/workspace`, `ui/stat`, `ui/card` and `ui/button`, not in a separate folder. |

Other view folders: `auth`, `site`, `portal`, `workspace` (59 files in 20 sub-folders), `emails` (5), `errors` (7), `notifications`, `pagination` (custom `default` and `simple` views, set in `AppServiceProvider`), `partials`, `reports`. Empty legacy folders are listed in [Application architecture](application-architecture.md#leftover-view-folders).

### E-mail rendering

E-mails are built from `emails.templated` plus the `x-emails.*` components. Images are **hosted, not attached**: the crest is `{MAIL_ASSET_URL or APP_URL}/images/uew/email-crest.png`. Many school and work mail systems block or show CID attachments as files. Set `MAIL_ASSET_URL` to a public HTTPS URL when `APP_URL` is not public (`config/mail.php` key `asset_url`). The subject and body come from `EmailTemplates` (database overrides in `email_templates`, defaults in code). See [Integration architecture](integration-architecture.md).

## Tailwind v4 tokens

Tokens are in the `@theme` block of [`resources/css/app.css`](../../resources/css/app.css) (130 lines). Utilities such as `bg-uew-navy` and `font-mono` come from them.

| Token | Value | Use |
| --- | --- | --- |
| `--color-uew-ink` / `ink-soft` | `#0a1128` / `#131f42` | Workspace sidebar and dark surfaces |
| `--color-uew-navy-deep` / `navy` / `navy-light` | `#0a1b4d` / `#102a72` / `#1e3a8a` | Primary brand |
| `--color-uew-royal` / `royal-light` | `#3157d5` / `#4f75f5` | Links, focus ring, button gradient |
| `--color-uew-purple` / `purple-light` | `#6d28d9` / `#8b5cf6` | Secondary accent |
| `--color-uew-gold` / `gold-light` / `gold-dark` | `#f59e0b` / `#fbbf24` / `#b45309` | Accent, `gold` button |
| `--color-uew-canvas` | `#f8fafc` | Page background |
| `--color-uew-red` | `#e31e24` | Crest red, used sparingly |
| `--font-sans` | Inter Variable | Body text |
| `--font-mono` | JetBrains Mono Variable | Metrics and codes |
| `--shadow-card`, `--shadow-card-hover`, `--shadow-glow-gold` | see file | Cards and focus glow |

Base layer rules worth knowing:

- `:focus-visible` shows a 2px royal-blue outline everywhere.
- `[x-cloak]` is hidden so Alpine components do not flash.
- `.grid > *` gets `min-width: 0`, and `.overflow-x-auto` gets `position: relative`, to stop wide tables from forcing sideways page scroll on phones.
- `prefers-reduced-motion: reduce` shortens animations and transitions to 0.01 ms.
- `.prose-uew` styles Markdown article bodies. `.no-print` hides elements in print.

The `@source` lines tell Tailwind to scan Blade files, JS, enum files, compiled views and the Laravel pagination views for class names. A class built from a variable at run time is not found unless the full class name appears in one of those files.

## Alpine usage

`resources/js/app.js` imports the fonts, starts Alpine (`window.Alpine = Alpine; Alpine.start()`) and nothing else. All behaviour is declared inline in Blade with `x-data`. 33 `x-data` attributes in 28 view files. Common patterns:

| Pattern | Example | Where |
| --- | --- | --- |
| Double-submit guard | `x-data="{ busy: false }"` on forms (9 uses) | Forms with uploads or e-mail |
| Dismissible flash message | `x-data="{ show: true }"` | `layouts/site` |
| Open/close menus and scroll state | `x-data="{ open: false, scrolled: false }"` | Site header |
| Table filter, bulk select | `x-data="{ filter: '' }"`, `{ selected: [], all: false }` | Workspace lists |
| Server data into Alpine | `x-data="{ tab: @js($active) }"` | Tabs, programme form builder, mail settings |

Server values go in through `@js(...)`, which JSON-encodes and escapes them. There are no `<script>` blocks other than one `application/ld+json` data block in `layouts/site`.

## Content Security Policy

[`SecurityHeaders`](../../app/Http/Middleware/SecurityHeaders.php) is appended to the `web` and `api` groups in `bootstrap/app.php`. It sets the following when Vite is not in hot mode and no CSP header already exists.

| Directive | Value | Reason |
| --- | --- | --- |
| `default-src` | `'self'` | Everything else blocked by default |
| `script-src` | `'self' 'unsafe-eval'` | The standard Alpine build evaluates expressions with `new Function`. This is why `'unsafe-eval'` is present. Inline scripts remain blocked. |
| `style-src` | `'self' 'unsafe-inline'` | Inline `style` attributes in views and e-mail previews |
| `img-src` | `'self' data:` | Images are same-origin or data URIs |
| `font-src` | `'self' data:` | Self-hosted fonts |
| `connect-src` | `'self'` | No third-party calls from the browser |
| `object-src` | `'none'` | No plugins |
| `base-uri`, `form-action`, `frame-ancestors` | `'self'` | Limits injected forms and framing |

Other headers: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` (camera, microphone, geolocation, payment disabled), `Cross-Origin-Opener-Policy: same-origin`, and `Strict-Transport-Security` (one year, includeSubDomains) on secure requests. `X-Robots-Tag: noindex, nofollow` is set for private paths and for every response while `SEARCH_INDEXING` is false (the default).

Consequences for developers:

- Do not add third-party scripts, fonts, analytics or CDN images. They will be blocked. Self-host through npm and Vite.
- Do not add inline event handlers (`onclick="..."`). Use Alpine `@click`.
- Switching to the CSP-friendly Alpine build would let `'unsafe-eval'` be removed. This is **Recommended, not implemented**.
- `X-Frame-Options: SAMEORIGIN` and `frame-ancestors 'self'` allow framing only by the site itself.
- When `Vite::isRunningHot()` (`npm run dev`) no CSP is sent, so a CSP problem does not appear in development. Test against `npm run build` output. Header behaviour is covered by `tests/Feature/SecurityHeadersTest.php`.

## Assets and caching

| Asset | Served from | Cache |
| --- | --- | --- |
| Vite output `public/build/assets/*` | Apache | `Cache-Control: public, max-age=31536000, immutable` (`docker/apache-vhost.conf`) |
| Images `public/images`, `public/images/uew` | Apache | Default |
| Gallery photos | `/media/photos/{photo}/{size}` through Laravel, with an album-published check | `public, max-age=31536000, immutable` for published albums, `private, no-store` otherwise (`GalleryController::photo()`) |
| Uploaded documents | Laravel streaming only, never under `public/` | None |

## Accessibility and responsive checks

`tests/e2e/a11y.spec.ts` (axe-core), `responsive.spec.ts` and `journeys.spec.ts` run in CI on Chromium, Firefox, WebKit and a mobile profile. The base CSS follows from them: visible focus, reduced motion, the overflow fixes above, and a canvas colour chosen so slate-500 text meets 4.5:1 contrast (comment in `app.css`).
