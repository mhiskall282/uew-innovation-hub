# Frontend Development

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [coding-standards](coding-standards.md), [adding-new-features](adding-new-features.md), [testing-guide](testing-guide.md), `../04-architecture/frontend-architecture.md`

The front end is server-rendered Blade with Tailwind CSS v4, Alpine.js 3 and Vite 7. There is no SPA framework and no TypeScript in the application (TypeScript is used only for Playwright tests).

## Build tooling

| File | Role |
| --- | --- |
| `package.json` | Scripts: `build` (`vite build`), `dev` (`vite`), `test:e2e`, `test:e2e:chromium` |
| `vite.config.js` | `laravel-vite-plugin` with inputs `resources/css/app.css` and `resources/js/app.js`, `refresh: true`, and `@tailwindcss/vite`. Ignores `storage/framework/views/**` in the watcher |
| `resources/css/app.css` | `@import 'tailwindcss'`, `@source` lines, and the `@theme` block with design tokens |
| `resources/js/app.js` | Imports `bootstrap.js` (axios), the self-hosted Inter and JetBrains Mono variable fonts, then starts Alpine |
| `public/build/` | Output of `npm run build` (git-ignored). Contains `manifest.json` |

Layouts load assets with `@vite(['resources/css/app.css', 'resources/js/app.js'])` (`components/layouts/workspace.blade.php` and the others).

```powershell
npm install        # first time
npm run dev        # hot reload; writes public/hot while running
npm run build      # production build, as CI and the Dockerfile do
```

```bash
npm install && npm run dev
npm run build
```

While `npm run dev` runs, the page loads assets from the Vite server. Stop it with Ctrl+C, and `public/hot` is removed. If the file is left behind after a crash, the site looks unstyled because it points at a dead Vite server. Delete `public/hot`.

The Docker build copies only `resources/`, `vite.config.js`, `package*.json` and `app/Enums/` into the asset stage (`Dockerfile`). Tailwind scans `app/Enums/*.php` for class names (for example badge tones). If you add class names to PHP files outside `resources/` and `app/Enums/`, the production build will not see them.

## Design tokens

Defined in `resources/css/app.css` under `@theme`:

| Token group | Examples | Utility |
| --- | --- | --- |
| Brand | `--color-uew-navy` `#102a72`, `--color-uew-royal` `#3157d5`, `--color-uew-purple` `#6d28d9`, `--color-uew-gold` `#f59e0b`, plus `-light`, `-dark`, `-deep` and `uew-ink`, `uew-canvas`, `uew-red` | `bg-uew-navy`, `text-uew-purple`, `border-uew-gold` |
| Fonts | `--font-sans` (Inter Variable), `--font-mono` (JetBrains Mono Variable) | `font-sans`, `font-mono` |
| Shadows | `--shadow-card`, `--shadow-card-hover`, `--shadow-glow-gold` | `shadow-card` |

Use the tokens. Do not add raw hex colours in markup. Use `font-mono` for figures in dashboard cards.

## Blade structure

| Path | Use |
| --- | --- |
| `resources/views/components/layouts/{site,portal,workspace,auth}.blade.php` | Page layouts, used as `<x-layouts.workspace title="Gallery">` |
| `resources/views/components/ui/*.blade.php` | Building blocks: `alert`, `badge`, `button`, `card`, `checkbox`, `confirm-form`, `crest`, `empty`, `field`, `icon`, `input`, `notification-bell`, `page-header`, `page-hero`, `progress`, `select`, `stat`, `textarea` |
| `resources/views/components/emails/` | `button`, `layout`, `panel` for e-mail |
| `resources/views/emails/` | E-mail bodies |
| `resources/views/{site,portal,workspace,auth}/` | Pages |

Examples from `workspace/albums/index.blade.php`:

```blade
<x-layouts.workspace title="Gallery">
    <x-ui.page-header title="Gallery" description="...">
        <x-slot:actions>
            @can('create', \App\Models\Album::class)
                <x-ui.button :href="route('workspace.albums.create')" icon="plus">New album</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
    @if ($albums->isEmpty())
        <x-ui.card><x-ui.empty title="No albums yet" icon="photo">...</x-ui.empty></x-ui.card>
    @else
        ...
    @endif
</x-layouts.workspace>
```

`<x-ui.button>` props: `variant` (`primary`, `ghost`, and others in the file), `size` (`xs`, `sm`, `md`, `lg`), `href`, `type`, `icon`, `iconRight`. Icons are SVG paths in `components/ui/icon.blade.php`; add a new path there instead of loading an icon library.

Rules:

1. Use `route('name')` for links. No hard-coded paths.
2. Escape with `{{ }}`. Avoid `{!! !!}`.
3. Every form has `@csrf`, and `@method('PUT')` or `@method('DELETE')` where needed.
4. Check permissions in the view with `@can(...)` only to hide controls. The server must still enforce them.
5. Do not query the database in a view. Pass data from the controller with relations eager-loaded (lazy loading throws outside production).
6. Handle empty, error and success states. Show validation errors beside fields (`<x-ui.field>`).
7. Labels for every input, `alt` text for images (`alt=""` for decorative ones), `aria-label` for icon-only buttons, visible focus.
8. Touch targets of at least 44 px on mobile. Layouts work from 320 px to wide desktop. Test at 320, 768 and 1280.

## Alpine.js

`resources/js/app.js` starts Alpine with no registered components. Interactions use inline `x-data`, `x-show`, `x-transition` in Blade. Keep them small and server-driven. Page state stays on the server.

Content Security Policy (`app/Http/Middleware/SecurityHeaders.php`):

- `script-src 'self' 'unsafe-eval'`. No inline `<script>` blocks run. Put JavaScript in `resources/js/` and import it from `app.js`. `'unsafe-eval'` is allowed for Alpine's expression evaluation.
- `style-src 'self' 'unsafe-inline'`.
- `img-src 'self' data:`, `font-src 'self' data:`, `connect-src 'self'`. External images, fonts or scripts are blocked. Self-host them. Fonts come from `@fontsource-variable/*` packages.
- The CSP is not sent while the Vite dev server is running (`Vite::isRunningHot()`), so a problem that only appears in a production build may be a CSP issue. Check with `npm run build` and no `public/hot`.

## Images

Site photographs are in `public/images/` (campus photos in `public/images/uew/`, credited in `app/Support/Photos.php`). Uploaded gallery photos are served through `AlbumPhoto::url('thumb'|'large')` from the gallery disk. Always give images `width`/`height` or an aspect-ratio container to avoid layout shift, and `loading="lazy"` below the fold.

## Accessibility and responsive checks

`tests/e2e/a11y.spec.ts` runs axe-core on key pages, and `responsive.spec.ts` checks viewports for horizontal overflow and broken images (Chromium). Run them with `npm run test:e2e:chromium` ([testing-guide](testing-guide.md)). Contrast must meet WCAG AA (4.5:1 for body text). `--color-uew-canvas` has a comment noting slate-500 text on it meets 4.5:1.

## Adding a page

1. Route and controller ([backend-development](backend-development.md)).
2. View under the matching folder, wrapped in the matching layout.
3. Build with existing `ui` components. Add a component only after the second use.
4. `npm run build` and look at it at narrow and wide widths.
5. Add or extend a Playwright journey if it is a key flow.
