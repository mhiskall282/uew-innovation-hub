# UX & Accessibility Rules

> **Platform**: UEW Innovation Hub Enterprise  

1. **Account for all 4 UI states**: Every data view, table, and modal must explicitly handle **Loading**, **Empty**, **Error**, and **Success** states. Never assume data is always populated.
2. **Mobile touch targets**: All interactive buttons, menu triggers, and inputs must maintain a minimum touch target size of 44x44px on mobile devices.
3. **WCAG AA color contrast**: Text-to-background contrast must satisfy at least 4.5:1 for standard text and 3:1 for bold/large headers.
4. **Accessible form controls**: Every input field must have an explicit, permanently visible `<label>` element with matching `for` and `id` attributes. Never rely solely on placeholder text.
5. **Keyboard focus visibility**: Interactive elements must render visible `:focus-visible` outline rings for keyboard accessibility (`focus-visible:ring-2 focus-visible:ring-uew-blue`).
6. **Descriptive error feedback**: Place validation error messages directly beneath the offending input field in distinct, accessible red typography (`text-red-600 text-xs font-semibold`).
7. **Informative empty states**: When a filter returns no projects or publications, display an informative empty state illustration, clear explanation, and a quick "Reset Filters" action button.
8. **Meaningful alt text & aria tags**: All images and icons must have descriptive `alt` tags or `aria-label` attributes (`aria-label="Open sidebar menu"`).
9. **Micro-feedback on user actions**: Provide instantaneous feedback (spinners on submit buttons, toast notifications upon save, visual highlight on edited rows).
10. **Respect user motion preferences**: Support `motion-reduce:` variants on animations and transitions to respect users with vestibular sensitivities.
