# Tailwind CSS v4 & Brand Styling Skill

> **Platform**: UEW Innovation Hub Enterprise  
> **Domain**: CSS Architecture, Tokens, and Npontu Aesthetics

---

## 1. UEW Official Institutional Color Palette
```css
@theme {
  --color-uew-navy-dark: #0a1b4d;
  --color-uew-navy: #102a72;
  --color-uew-navy-light: #1e3a8a;
  --color-uew-royal: #3157d5;
  --color-uew-purple: #6d28d9;
  --color-uew-gold: #f59e0b;
  --color-uew-gold-light: #fbbf24;
  --color-uew-gold-dark: #d97706;
  --color-npontu-surface: #0f172a;
  --color-npontu-card: #1e293b;
  --color-npontu-border: #334155;
  --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
  --font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, monospace;
}
```

---

## 2. Npontu Button Specification
- **Primary Action**: Gradient from `#102a72` to `#3157d5` with subtle gold accent glow on hover:
  `bg-gradient-to-r from-[#102a72] to-[#3157d5] hover:from-[#0a1b4d] hover:to-[#1e3a8a] text-white shadow-md shadow-blue-900/20 active:scale-[0.98] transition-all duration-150`
- **Secondary Action**: Clean bordered neutral button:
  `border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all`
- **Danger Action**: Soft red badge button:
  `bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-900/50 hover:bg-rose-100 transition-all`

---

## 3. Ergonomics & Density
- High data density: table cell padding `py-2.5 px-4`, font size `text-xs` or `text-sm`.
- Monospace font `font-mono` for all numeric metrics, counts, dates, and IDs.
- Ambient dark sidebar (`bg-[#0a1b4d]`) paired with crisp light workspace (`bg-slate-50`).
