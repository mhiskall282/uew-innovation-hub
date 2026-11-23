# Brand System & UI/UX Guidelines

> **Institution**: University of Education, Winneba (UEW) Innovation Hub  
> **Brand Identity**: Official UEW Navy, Royal Blue, Imperial Purple, Academic Gold, and Crest Logo  
> **Ergonomic Design System**: Npontu SRE Cockpit Aesthetic (High-Contrast Buttons, Telemetry Badges, Collapsible Nav)

---

## 1. Brand Essence & Visual Character

The **UEW Innovation Hub** bridges academic pedagogical excellence with technological entrepreneurship. Its visual design must reflect:
- **Academic Authority & Rigor**: Deep, grounded blues and structured layouts.
- **Innovation & Dynamism**: Electric royal blue and deep imperial purple gradients.
- **Prestige & Excellence**: Warm academic gold accents and crisp metallic highlights.
- **Npontu-Style Operational Clarity**: Information-dense, ergonomic dashboards with instant telemetry feedback, glowing status pills, and high-contrast interactive controls.

---

## 2. Color Palette & Design Tokens

The color palette unites the official UEW university colors with the high-productivity Npontu UI system:

| Token Name | Hex Code | Tailwind Utility Class | Intended Usage |
| :--- | :--- | :--- | :--- |
| **UEW Navy (Primary)** | `#102A72` | `bg-uew-navy` / `text-uew-navy` | Main institutional branding, primary headers, active sidebar accents |
| **UEW Royal Blue** | `#3157D5` | `bg-uew-blue` / `text-uew-blue` | Interactive links, focus borders, primary action button gradient start |
| **Imperial Purple** | `#6D28D9` | `bg-uew-purple` / `text-uew-purple` | Secondary brand accent, gradient transition target, research badges |
| **Purple Highlight** | `#8B5CF6` | `text-uew-purple-light` | Subtle glows, active telemetry highlights, focus rings |
| **Academic Gold** | `#F59E0B` | `bg-uew-gold` / `text-uew-gold` | KPI emphasis, active sidebar left border indicator, status warnings |
| **Warm Gold** | `#FBBF24` | `text-uew-gold-light` | Glowing badge borders, star ratings, prestige badges |
| **Cockpit Canvas (Dark)** | `#0A1128` | `bg-uew-cockpit` | Npontu dark sidebar, command palette, high-contrast modal headers |
| **Surface Mid (Dark)** | `#131F42` | `bg-uew-surface-dark` | Hover backgrounds on sidebar, dark card surfaces |
| **Canvas Background (Light)**| `#F4F7FB` | `bg-uew-surface-light` | Main administrative content canvas, clean readable background |
| **Card White** | `#FFFFFF` | `bg-white` | Elevated data cards, modals, table rows |
| **Border Divider** | `#E2E8F0` | `border-gray-200` | Subtle clean card borders, table dividers |

### Tailwind CSS v4 `@theme` Configuration Snippet
```css
@theme {
    --color-uew-navy: #102a72;
    --color-uew-navy-dark: #091a48;
    --color-uew-blue: #3157d5;
    --color-uew-blue-light: #4f75f5;
    --color-uew-purple: #6d28d9;
    --color-uew-purple-light: #8b5cf6;
    --color-uew-gold: #f59e0b;
    --color-uew-gold-light: #fbbf24;
    --color-uew-cockpit: #0a1128;
    --color-uew-surface-dark: #131f42;
    --color-uew-surface-light: #f4f7fb;

    --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
    --font-mono: 'JetBrains Mono', ui-monospace, 'Courier New', monospace;

    --radius-card: 12px;
    --radius-badge: 9999px;

    --shadow-card: 0 4px 16px rgba(16, 42, 114, 0.06);
    --shadow-glow-blue: 0 0 16px rgba(49, 87, 213, 0.35);
    --shadow-glow-gold: 0 0 16px rgba(245, 158, 11, 0.45);
}
```

---

## 3. Typography System

- **Primary Sans (UI & Body)**: `Inter`, sans-serif  
  *Crisp legibility for tables, labels, form controls, and descriptions.*
- **Monospace (Telemetry & Metrics)**: `JetBrains Mono`, monospace  
  *Exclusively used for beneficiary tallies, project IDs, timestamps, dates, currency amounts, and status counters.*
- **Heading Hierarchy**:
  - `h1` (Dashboard Title): `text-2xl font-black text-gray-900 tracking-tight`
  - `h2` (Card Headers): `text-lg font-bold text-uew-navy tracking-normal`
  - `h3` (Sub-sections): `text-sm font-semibold uppercase tracking-wider text-gray-500 font-mono`
  - Stat Values: `text-3xl font-black font-mono tracking-tight text-uew-navy`

---

## 4. Npontu-Style UI Components

### 4.1 Buttons: Npontu Precision Style
Npontu buttons feature subtle gradients, crisp contrasting borders, active press feedback, and micro-hover lifts:

```html
<!-- Primary Action Button (Add Project, Save, Submit) -->
<button class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-bold text-white bg-gradient-to-r from-[#102a72] to-[#6d28d9] border border-blue-400/40 shadow-sm hover:shadow-lg hover:shadow-indigo-500/25 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-150 cursor-pointer">
    <svg class="w-4 h-4 text-[#fbbf24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    <span>Register New Project</span>
</button>

<!-- Secondary / Export Action Button -->
<button class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold text-gray-700 bg-white border border-gray-300 shadow-sm hover:bg-gray-50 hover:border-gray-400 active:bg-gray-100 transition-all cursor-pointer">
    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <span>Export XLSX</span>
</button>

<!-- Danger Action Button -->
<button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-red-700 bg-red-50 border border-red-200 hover:bg-red-100 hover:border-red-300 transition-all cursor-pointer">
    <span>Delete</span>
</button>
```

### 4.2 Sidebar Navigation (Npontu SRE Cockpit)
- Deep cockpit navy canvas (`#0a1128`).
- Section groupings with collapsible headers.
- **Active Navigation Pill**:
  `bg-[#102a72] text-white shadow-sm border-l-4 border-[#f59e0b]` with warm gold icon highlight.
- **Telemetry Dot**:
  Glowing pulsing orb (`w-2 h-2 rounded-full bg-[#f59e0b] animate-pulse`) next to active section titles.

### 4.3 KPI & Telemetry Stat Cards
- Dual-tier display:
  - Top: Label in uppercase monospace (`text-xs font-mono text-gray-400 uppercase tracking-widest`).
  - Middle: High-impact number in JetBrains Mono (`text-3xl font-black text-uew-navy`).
  - Bottom: Comparative trend indicator with percentage badge and micro-sparkline.

### 4.4 Status Badges & Chips
- `Active / In Progress`: `bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono text-xs rounded-full px-2.5 py-0.5`
- `Completed`: `bg-blue-50 text-blue-700 border border-blue-200 font-mono text-xs rounded-full px-2.5 py-0.5`
- `Under Review`: `bg-amber-50 text-amber-700 border border-amber-200 font-mono text-xs rounded-full px-2.5 py-0.5`
- `On Hold`: `bg-purple-50 text-purple-700 border border-purple-200 font-mono text-xs rounded-full px-2.5 py-0.5`

---

## 5. Asset Placement

- **Brand Logo Asset**: Located at `innovation_hub_logo.jpg` in the workspace root.
  In Blade views: `<img src="{{ asset('images/innovation_hub_logo.jpg') }}" alt="UEW Innovation Hub" class="w-12 h-12 object-contain bg-white rounded-xl p-1 shadow-sm border border-white/20">`
