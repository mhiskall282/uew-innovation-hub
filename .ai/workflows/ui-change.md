# UI Change & Npontu Styling Workflow

> **Platform**: UEW Innovation Hub Enterprise

---

## 1. Understand the Visual Target
1. Identify the affected Blade views, components, and Alpine.js interactions.
2. Review the current rendering at multiple breakpoints (320px through 1440px).
3. Consult `.ai/brand.md` for UEW color tokens and Npontu ergonomic specifications.

---

## 2. Plan the Change
- Which Blade component(s) or layout file(s) are affected?
- Are new reusable components needed (`<x-npontu.button>`, `<x-npontu.badge>`, etc.)?
- Does the change require new Tailwind tokens or Alpine.js state?

---

## 3. Implement
1. Update or create Blade components using UEW design tokens.
2. Use Npontu button styles (gradient primary, outlined secondary, danger ghost).
3. Apply correct typography: `Inter` for body, `JetBrains Mono` for telemetry metrics.
4. Test sidebar, KPI cards, tables, modals, and print layouts at each target breakpoint.
5. Verify WCAG AA color contrast (minimum 4.5:1 for body text).

---

## 4. Verify
- Inspect mobile layout (320px, 375px) for touch target sizes ≥ 44px.
- Verify dark sidebar against light content canvas contrast boundaries.
- Confirm modals and drawers open/close smoothly with keyboard accessibility.
- Confirm print CSS layout renders cleanly when `window.print()` is invoked.

---

## 5. Record
- Update `.ai/state.md` reflecting the new component or layout additions.
- Update `.ai/changelog.md` under `### UI / Frontend`.
