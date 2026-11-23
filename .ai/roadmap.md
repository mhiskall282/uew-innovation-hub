# Rebuild Roadmap & Milestone Schedule

> **Platform**: UEW Innovation Hub Enterprise Rebuild  
> **Transition**: Legacy Procedural PHP ➔ Enterprise Laravel 12 + Npontu Cockpit UI  

---

## Phase 1 — Foundations, Design System & Operating System (Current)
- [x] Comprehensive review of legacy codebase (`index.php`, `add_project.php`, `edit_project.php`, `delete_project.php`, `auth.php`, `dashboard.php`, `export.php`, `print.php`, `publications_api.php`, `file.php`, `home.php`, `users.php`).
- [x] AI Engineering Operating System setup (`.ai/` directory, rules, workflows, skills, templates, root `AGENTS.md`).
- [ ] Laravel 12 application scaffold initialization with clean directory layout.
- [ ] Tailwind CSS v4 design tokens integration with official UEW palette (`#102a72`, `#3157d5`, `#6d28d9`, `#f59e0b`).
- [ ] Npontu UI base Blade components:
  - `<x-npontu.button>` (Primary gradient, Secondary clean, Danger, Ghost)
  - `<x-npontu.badge>` (Status pill, partner role tag, telemetry dot)
  - `<x-npontu.card>` & `<x-npontu.stat-card>` (KPI metrics with JetBrains Mono numbers)
  - `<x-npontu.modal>` & `<x-npontu.drawer>` (Smooth Alpine.js slide-overs)
  - `<x-layouts.app>` (Collapsible sidebar, top cockpit header, breadcrumbs)

---

## Phase 2 — Database Schema & Zero-Loss Legacy Migration
- [ ] Create normalized Laravel database migrations:
  - `projects` table (replacing `innovation_hub`)
  - `organizations` table (replacing `organization`)
  - `project_organizations` table (replacing `project_organisations` with multi-role support)
  - `publications` table
  - `users` table
  - `activity_logs` table
- [ ] Develop and execute `MigrateLegacyDataSeeder` to import 100% of historical projects, partner organizations, and publications from `project_mysql`.
- [ ] Move existing files from `reports/` and `publications/` into Laravel's protected storage (`storage/app/reports/` and `storage/app/publications/`).
- [ ] Define Eloquent models with full relationships, accessors, query scopes, and type casts.

---

## Phase 3 — Projects Subsystem & Npontu Dashboard Cockpit
- [ ] Build Npontu Cockpit Dashboard:
  - Real-time aggregate telemetry cards (Total Projects, Total Beneficiaries, Active Partners, Avg Reach).
  - Year tabs filter (`All`, `2026`, `2025`, `2024`, `No Date`) with live counts.
  - Multi-column project table with status chips, partner role badges, and action dropdowns.
- [ ] Project Management CRUD:
  - Dynamic Add Project modal/drawer with inline new organization creator and role assignment.
  - Full Edit Project modal with multi-partner synchronization and optional PDF replacement.
  - Safe soft-deletion with confirmation prompt and audit logging.
- [ ] Global quick-search palette (`Cmd+K`) for instantaneous project and partner lookup.

---

## Phase 4 — Publications & Secure Document Streaming
- [ ] Publications and Newsletters manager interface (`/publications`).
- [ ] Filter by publication category (`Newsletter`, `Policy Brief`, `Annual Report`, `Research Paper`).
- [ ] Document streaming controller (`FileStreamController`) serving PDFs inline or via attachment with anti-traversal protection and security headers.
- [ ] Upload validation enforcing strict PDF MIME verification and sanitized filename generation.

---

## Phase 5 — Reporting, Excel/CSV Export & Print Compendium
- [ ] Modernized `ReportExportService` supporting streaming Excel (`.xlsx`) and CSV downloads respecting active dashboard year/category filters.
- [ ] Dedicated Print Compendium view (`/projects/print`) with crisp institutional layout, official UEW crest, and pagination rules.
- [ ] Visual telemetry charts (Year-over-Year impact, Top Sponsors ranking, Largest Projects by Beneficiary Reach).

---

## Phase 6 — RBAC, Security Hardening & Production Release
- [ ] Granular authorization policies (`ProjectPolicy`, `PublicationPolicy`, `UserPolicy`).
- [ ] User administration portal (`/admin/users`) with role assignment and password resets.
- [ ] Automated audit trail recorder (`ActivityLogger` listener/middleware).
- [ ] PHP runtime hardening (eliminating upload progress RCE vectors, strict execution denial in storage dirs).
- [ ] Automated test suite covering auth, project CRUD, file uploads, and export generation.
