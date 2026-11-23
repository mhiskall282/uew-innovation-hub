# Blade Templating Skill

> **Platform**: UEW Innovation Hub Enterprise  
> **Domain**: View Architecture & Component Hierarchy

---

## 1. Blade Component System
All UI primitives must be encapsulated as reusable Blade components under `resources/views/components/`:
- `x-npontu.button`: Styled action triggers with variants (primary, secondary, danger, ghost).
- `x-npontu.card`: Glassmorphic / card containers with subtle border and shadow tokens.
- `x-npontu.badge`: Telemetry and status pills with glowing ambient borders.
- `x-npontu.sidebar`: Collapsible dark navy navigation bar.
- `x-npontu.stat-card`: SRE KPI metrics with sparklines and count-up values.
- `x-npontu.table`: High-density responsive data grid with sorting and status indicators.

---

## 2. Layout Structure
- `layouts.app`: Main authenticated cockpit layout with sidebar, top telemetry bar, flash messages, and breadcrumb navigation.
- `layouts.guest`: Clean authenticated gateway layout for login/password reset.
- `layouts.print`: Minimal high-contrast print-optimized layout for PDF & physical document generation.

---

## 3. Security in Blade
- Always use `{{ $variable }}` for escaped rendering.
- Never use `{!! $variable !!}` unless the variable is sanitized HTML processed through an approved purifier.
- Every form mutation must have `@csrf` and appropriate `@method('PUT'|'DELETE')`.
