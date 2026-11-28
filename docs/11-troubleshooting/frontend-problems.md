# Frontend Problems

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Frontend architecture](../04-architecture/frontend-architecture.md), [Frontend development](../05-developer-guide/frontend-development.md), [Frontend testing](../10-testing-and-quality/frontend-testing.md), [End-to-end testing](../10-testing-and-quality/end-to-end-testing.md)

The front end is Blade views with Tailwind CSS v4 and Alpine.js, built by Vite (`vite.config.js`, inputs `resources/css/app.css` and `resources/js/app.js`) into `public/build`. There is no separate SPA. Scripts: `npm run dev`, `npm run build`, `npm run test:e2e`, `npm run test:e2e:chromium`.

## Vite manifest not found

**Symptoms:** Every page returns 500 with `Illuminate\Foundation\ViteManifestNotFoundException: Vite manifest not found at: ...\public\build\manifest.json`.

**Possible causes:** `npm run build` was never run, `public/build` was deleted, or the dev server is not running and no build exists. `public/build` is not committed.

**Diagnostic steps:**

```powershell
Test-Path public\build\manifest.json
node -v
```

**Commands / Resolution:**

```powershell
npm ci
npm run build
```

For daily work run the dev server instead, in a second terminal (or use `composer run dev`, which starts the web server, queue listener, log viewer and Vite together):

```powershell
npm run dev
```

**Prevention:** The Docker image builds assets in its first stage (`npm run build`) and copies `public/build` into the runtime image, so deployed sites are not affected unless the front-end stage fails. Run `npm run build` after every `git pull` that changes `resources/` or `package.json`.

## Styles or new classes are missing

**Symptoms:** A page is unstyled, or a Tailwind class you added has no effect, or new UI shows old styling.

**Possible causes:** The built CSS is old. The dev server is not running. The browser cached the old file. `public/hot` exists from a dev server that was killed, so Laravel points to a Vite server that is not there.

**Diagnostic steps:**

```powershell
Test-Path public\hot
```

**Resolution:**

```powershell
Remove-Item public\hot -ErrorAction SilentlyContinue
npm run build
```

Hard-refresh the browser (Ctrl+F5). Tailwind v4 finds classes by scanning source files, so a class assembled from string pieces (`'bg-' + color`) is never generated. Write full class names.

**Prevention:** Stop `npm run dev` with Ctrl+C, not by closing the terminal.

## Scripts blocked or the page looks half-broken (CSP)

**Symptoms:** Alpine components do nothing, or the console shows "Refused to execute inline script" or "Refused to load ... because it violates the Content-Security-Policy".

**Cause:** `SecurityHeaders` sends a Content-Security-Policy that allows only same-origin scripts, styles and fonts. Inline scripts and third-party CDNs are blocked. The policy is skipped only while the Vite dev server runs (`Vite::isRunningHot()`).

**Resolution:** Put scripts in `resources/js` and import them through Vite. Do not add CDN links in Blade. Test production behaviour with a build, not the dev server.

**Diagnostic steps:** Open the browser console and read the exact blocked source. See `app/Http/Middleware/SecurityHeaders.php`.

## Blade errors

| Symptom | Cause | Fix |
| --- | --- | --- |
| `View [x] not found` | Wrong name, or a leftover view folder | Check `resources/views`. Folders `admin`, `dashboard`, `grants`, `organizations`, `posts`, `projects`, `publications`, `reports` at the top level may be leftovers. Workspace views are under `resources/views/workspace` |
| `Undefined variable $x` | The controller did not pass it | Compare with the controller's `view()` array |
| `Attempted to lazy load` | Relation not eager loaded | See [database problems](database-problems.md#lazy-loading-exceptions) |
| Old markup after a change | Compiled view cache | `php artisan view:clear` |
| Component not found | Wrong tag name | Components live in `resources/views/components` (`<x-ui.button>`, `<x-layouts.workspace>`) |

CI has a step that compiles all Blade templates, so a syntax error fails the *Tests* job.

## Workspace sidebar or menu item missing

The sidebar filters items by permission (`resources/views/components/layouts/workspace.blade.php`). A missing item means the role lacks the permission, not a front-end fault. See [admin troubleshooting](../03-administration/admin-troubleshooting.md#menu-item-missing-or-403).

## Flash messages or forms lose data

- A 419 after a long edit means the session expired. See [authentication problems](authentication-problems.md#page-expired-419).
- Validation errors redirect back with old input. A file input is never repopulated, so the file must be chosen again.
- Long news articles: save as a draft often.

## Images do not appear

| Image | Cause and fix |
| --- | --- |
| Gallery photos missing on the public site | Files lost with the disk. `AlbumPhoto::isAvailable()` hides them. Re-upload, and use `GALLERY_DISK=s3`. See [deployment problems](deployment-problems.md#uploaded-files-disappear-after-a-deploy) |
| E-mail images broken | `MAIL_ASSET_URL` (default `APP_URL`) is not a public HTTPS address. Set it to the live site when sending from a local machine |
| Crest image missing | `public/images` was not copied. Check the Docker build |

## Responsive layout, accessibility and browser tests

Playwright specs are in `tests/e2e`: `journeys.spec.ts`, `responsive.spec.ts`, `a11y.spec.ts`. Helper scripts: `serve.mjs`, `find-overflow.mjs`, `measure-performance.mjs`. CI runs them on Chromium, Firefox, WebKit and a mobile profile.

```powershell
npx playwright install chromium
npm run test:e2e:chromium
```

**Symptoms:** "Executable doesn't exist" means the browsers are not installed (`npx playwright install ...`). Timeouts on the first test usually mean the server did not start. Read `tests/e2e/serve.mjs` for how the test server is prepared.

> **Requires confirmation:** the exact environment the e2e server needs (database file, seeded data). Read `tests/e2e/serve.mjs` and the *Prepare environment* step of `.github/workflows/ci.yml`.

## JavaScript errors

Open the browser console. Alpine errors show `Alpine Expression Error`. They name the failing expression and the element. Build output is minified, so use `npm run dev` for readable stack traces.

## Dark text on dark background, contrast and focus problems

These are covered by the accessibility tests (`tests/e2e/a11y.spec.ts`). Run them after styling changes:

```powershell
npx playwright test tests/e2e/a11y.spec.ts --project=chromium
```
