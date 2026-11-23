# Frontend & Styling Rules

> **Platform**: UEW Innovation Hub Enterprise  

## Blade Component Architecture
- Prefer reusable, atomic components over duplicated markup:
  - Buttons (`<x-npontu.button>`)
  - Badges & Chips (`<x-npontu.badge>`)
  - Modals & Drawers (`<x-npontu.modal>`)
  - Form inputs (`<x-form.input>`, `<x-form.select>`, `<x-form.file>`)
  - Data tables (`<x-npontu.table>`)
- Use slots (`$slot`, `<x-slot:header>`) for flexible layout customization.

## Tailwind CSS Tokens
- Strictly use the project's defined design tokens (`bg-uew-navy`, `bg-uew-blue`, `text-uew-purple`, `border-uew-gold`, etc.).
- Do not introduce arbitrary, ad-hoc hex values (`#123456`) in view markup.
- Preserve the official UEW university palette and the Npontu cockpit ergonomics.

## Alpine.js Guidelines
- Use Alpine.js for lightweight client-side reactivity (e.g., toggling sidebar, opening modals, year tab switching, dynamically adding partner rows in forms).
- Avoid turning Blade into a fragile client-heavy pseudo-SPA. Core application state must remain server-driven.
- Keep Alpine components clean by separating complex state into helper functions or `x-data="projectForm()"`.

## Responsive Layouts
- Design mobile-first and verify layout responsiveness at:
  - `320px` (Compact mobile)
  - `375px` (Standard mobile)
  - `640px` (Tablet portrait)
  - `768px` (Tablet landscape)
  - `1024px` (Laptop)
  - `1280px+` (Desktop & wide displays)
