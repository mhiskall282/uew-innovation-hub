# University of Education, Winneba (UEW) Innovation Hub — AI Agent Operating Instructions

> **Platform**: UEW Innovation Hub Project & Research Management System  
> **Institution**: University of Education, Winneba (UEW), Ghana  
> **Architecture Transition**: Legacy Procedural PHP (`project_mysql`) ➔ Enterprise Laravel 12 + Blade + Tailwind CSS v4 + Alpine.js + Npontu UI System  
> **Brand Identity**: UEW Deep Navy (`#102a72`), Royal Blue (`#3157d5`), Imperial Purple (`#6d28d9`), Academic Gold (`#f59e0b` / `#fbbf24`), and official Crest `innovation_hub_logo.jpg`

---

## 1. Primary Operating Protocol

Before modifying or creating any code, schema, views, routes, or configurations, all AI agents **MUST** read and respect the foundational engineering specifications inside the `.ai/` directory:

1. [`.ai/ai.md`](file:///c:/Users/user/Desktop/uew-inno-hub/.ai/ai.md) — Master AI Engineering Operating System & Architectural Directives
2. [`.ai/context.md`](file:///c:/Users/user/Desktop/uew-inno-hub/.ai/context.md) — Project Context, UEW Mandate, Domains, and Stakeholders
3. [`.ai/brand.md`](file:///c:/Users/user/Desktop/uew-inno-hub/.ai/brand.md) — Brand System, UEW Palette & Npontu Dashboard & Button Specifications
4. [`.ai/architecture.md`](file:///c:/Users/user/Desktop/uew-inno-hub/.ai/architecture.md) — Technical Architecture, Laravel Subsystems, and Data Flow
5. [`.ai/database.md`](file:///c:/Users/user/Desktop/uew-inno-hub/.ai/database.md) — Relational Schemas, Eloquent Models, and Data Migration Integrity
6. [`.ai/state.md`](file:///c:/Users/user/Desktop/uew-inno-hub/.ai/state.md) — Current System State, Migration Roadmap, and Active Milestones
7. Rules inside [`.ai/rules/`](file:///c:/Users/user/Desktop/uew-inno-hub/.ai/rules/) (`core.md`, `laravel.md`, `frontend.md`, `ux.md`, `security.md`, `database.md`, `git.md`)
8. Domain skills inside [`.ai/skills/`](file:///c:/Users/user/Desktop/uew-inno-hub/.ai/skills/) (`laravel`, `blade`, `tailwind`, `alpine`, `database`, `security`, `ui-ux`, `cms`, `testing`)

---

## 2. Standard Workflows

- For implementing new features: Follow [`.ai/workflows/new-feature.md`](file:///c:/Users/user/Desktop/uew-inno-hub/.ai/workflows/new-feature.md)
- For resolving bugs and errors: Follow [`.ai/workflows/bug-fix.md`](file:///c:/Users/user/Desktop/uew-inno-hub/.ai/workflows/bug-fix.md)
- For database migrations and schema changes: Follow [`.ai/workflows/database-change.md`](file:///c:/Users/user/Desktop/uew-inno-hub/.ai/workflows/database-change.md)
- For UI redesign and Npontu styling adjustments: Follow [`.ai/workflows/ui-change.md`](file:///c:/Users/user/Desktop/uew-inno-hub/.ai/workflows/ui-change.md)
- For architectural decisions: Document in [`.ai/decisions.md`](file:///c:/Users/user/Desktop/uew-inno-hub/.ai/decisions.md) using [`.ai/templates/adr.md`](file:///c:/Users/user/Desktop/uew-inno-hub/.ai/templates/adr.md)
- After every meaningful change: Update [`.ai/state.md`](file:///c:/Users/user/Desktop/uew-inno-hub/.ai/state.md) and [`.ai/changelog.md`](file:///c:/Users/user/Desktop/uew-inno-hub/.ai/changelog.md)

---

## 3. Core Architectural Commitments

- **Modern Laravel 12 Architecture**: Clean models, FormRequests, policies, service classes, jobs, and zero raw SQL or fat controllers.
- **Brand Continuity & Npontu UX**: Preserve UEW colors (`#102a72`, `#3157d5`, `#6d28d9`, `#f59e0b`) and institutional logo while implementing the high-density, ergonomic **Npontu SRE dashboard style** (collapsible sidebar, telemetry counters, glowing status badges, high-contrast action buttons, JetBrains Mono metrics, and glassmorphic cards).
- **Zero Data Loss**: Maintain backward compatibility and a verified migration path for all existing project records, partner organizations, beneficiaries, uploaded report PDFs, and publications from the legacy MySQL database.
- **Enterprise Security**: Enforce CSRF protection, strict FormRequest validation, server-side RBAC, prepared statements, file streaming isolation, and PHP runtime hardening.
