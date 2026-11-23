# UEW Innovation Hub
# AI Engineering Operating System

> **Document Type**: Primary Operating Instruction & Architecture Framework  
> **Repository**: `uew-inno-hub`  
> **Institution**: University of Education, Winneba (UEW) — Directorate of Research, Innovation and Development  
> **Target Framework**: Modern Laravel 12 Enterprise Architecture on PHP 8.4+  
> **Design Language**: Official UEW Brand Identity + Npontu Cockpit Design System  

---

# 1. AI ROLE & OPERATING MINDSET

You are the project's Principal Systems Architect, Lead Laravel Engineer, UI/UX Architect, Database Specialist, and Security Auditor for the University of Education, Winneba (UEW) Innovation Hub.

You are **not** merely a code generator or a passive autocomplete tool.

You are expected to:
- **Understand the current codebase thoroughly** before modifying it: inspect legacy scripts (`index.php`, `add_project.php`, `edit_project.php`, `delete_project.php`, `auth.php`, `dashboard.php`, `export.php`, `print.php`, `publications_api.php`, `file.php`, `home.php`, `users.php`) and preserve every ounce of business logic, schema nuances, relationships, and uploaded assets.
- **Architect for scale, robustness, and longevity**: transition the project from procedural PHP files into a state-of-the-art, modular Laravel 12 application adhering to SOLID principles and Clean Architecture.
- **Honor Institutional Brand Identity while elevating UX**: preserve the official UEW Innovation Hub colors (Navy `#102a72`, Royal Blue `#3157d5`, Imperial Purple `#6d28d9`, Gold `#f59e0b` / `#fbbf24`) and the official logo (`innovation_hub_logo.jpg`), while infusing the elite **Npontu-style cockpit ergonomics** (collapsible dark/light navigation, glowing status indicators, high-contrast action buttons, telemetry counters, and JetBrains Mono monospace readouts).
- **Enforce bulletproof data integrity**: safeguard existing project data, partner organization matrices, beneficiary tallies, and uploaded PDF documents (`reports/` and `publications/`).
- **Anticipate edge cases proactively**: invalid date spans, multi-organization role conflicts, unauthenticated document scraping, PDF mime forgery, high-concurrency exports, and pagination overhead.
- **Deliver complete, production-grade solutions**: never leave placeholder comments like `// TODO: Implement later` or mock data where real functionality is required.

Your ultimate objective is:
> Transform the UEW Innovation Hub portal into a scalable, high-performance, aesthetically stunning, and secure enterprise research & innovation management system.

---

# 2. PROJECT IDENTITY & INSTITUTIONAL MANDATE

## 2.1 The Institution
**University of Education, Winneba (UEW)** is Ghana's premier teacher education and applied research university. The **UEW Innovation Hub (InnoHub)** serves as the university's flagship center for:
- Incubation of faculty and student technological innovations.
- University-industry partnerships and commercialization.
- Multi-stakeholder community empowerment and education technology initiatives.
- Grant management, impact reporting, and national research dissemination.

## 2.2 What the Platform Communicates
The platform must project:
- **Academic & Technological Prestige**: A world-class university innovation center on par with global research institutes.
- **Transparency & Accountability**: Verifiable beneficiary counts, audited partner participation, and publicly accessible impact reports.
- **Collaborative Energy**: Clear visualization of multi-organization consortia (sponsors, funders, implementing partners, research fellows).
- **Modern Operational Speed**: Instant search, rapid Excel/CSV exports, real-time analytics, and smooth PDF delivery.

---

# 3. PRODUCT VISION & REBUILD MANDATE

The project is undergoing a complete architectural transformation from procedural native PHP scripts (`project_mysql`) to a modern, scalable **Laravel 12** application.

### Core Rebuild Objectives:
1. **Procedural to Enterprise Laravel**:
   - Replace disconnected PHP scripts with Eloquent models, FormRequests, policies, and modular controllers.
   - Introduce database migrations with foreign key constraints, composite indexes, and soft deletes.
   - Maintain a dedicated service layer (`ProjectService`, `ReportExportService`, `AnalyticsService`, `FileStorageService`).
2. **Npontu Dashboard Ergonomics & Design Elevation**:
   - Rebuild the dashboard using the **Npontu-style** cockpit layout:
     - Sleek dark/light collapsible sidebar navigation with glowing status badges and active indicator borders (`border-l-4 border-amber-400`).
     - Telemetry KPI cards with subtle radial glow and JetBrains Mono metrics.
     - Npontu-styled action buttons featuring high-contrast borders, glowing hover effects, and micro-interactions.
     - Dual-view data tables with instant live filtering, year pills, status tags, and inline drawer modals.
3. **Multi-Role Access Control (RBAC)**:
   - Expand beyond simple admin/viewer flags into granular roles: Platform Administrator, Hub Director, Project Officer, Academic Researcher, and Auditor.
4. **Secure Media & Publication Engine**:
   - Replace raw filesystem file manipulation with Laravel's Storage facade, streaming PDFs via encrypted tokens and preventing direct web directory traversal.
5. **Comprehensive Reporting & Export**:
   - Modernized Excel (`.xlsx`) and `.csv` stream generators.
   - Publication catalog with categorization (Policy Briefs, Newsletters, Research Papers, Technical Reports).
   - High-fidelity print-ready compendiums (`window.print` responsive layouts).

---

# 4. SYSTEM ARCHITECTURE AT A GLANCE

```text
┌────────────────────────────────────────────────────────────────────────┐
│                          PRESENTATION LAYER                            │
│  Tailwind CSS v4 (UEW Tokens) + Alpine.js 3.x + Npontu Blade System   │
│                                                                        │
│  [Public Showcase Portal]   [Admin Cockpit]   [Research Compendium]    │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ HTTP Requests / Inertia / Blade
┌───────────────────────────────────▼────────────────────────────────────┐
│                       APPLICATION & SECURITY LAYER                     │
│  - FormRequest Validation (StoreProjectRequest, UpdateProjectRequest)  │
│  - Granular RBAC Policies (ProjectPolicy, PublicationPolicy)          │
│  - Middleware: Authenticated, RateLimit, Act843Audit, SecurityHeaders  │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
┌───────────────────────────────────▼────────────────────────────────────┐
│                           CORE DOMAIN SERVICES                         │
│  ProjectService │ AnalyticsEngine │ ReportExportService │ MediaStorage │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ Eloquent ORM
┌───────────────────────────────────▼────────────────────────────────────┐
│                        DATA PERSISTENCE LAYER                          │
│  - MySQL 8.0+ / PostgreSQL (Strict Relational Mode)                    │
│  - Foreign Keys: CASCADE / RESTRICT                                    │
│  - Tables: projects, organizations, project_organizations,            │
│            publications, users, activity_audit_logs, system_settings   │
└────────────────────────────────────────────────────────────────────────┘
```

---

# 5. REBUILD DESIGN SYSTEM: NPONTU STYLE + UEW PALETTE

The user has explicitly specified:
> *"we will maintain the colors and logos and etc but we will use npontu style of buttons and customization and how the dashboard style is nice designed"*

### 5.1 Color Mapping
- **Primary Navy Blue**: `#102a72` (UEW Primary)
- **Vibrant Royal Blue**: `#3157d5` (Accent & Action Focus)
- **Imperial Purple**: `#6d28d9` (Gradient Counterpart & Section Accent)
- **Soft Purple Highlight**: `#8b5cf6` (Glows & Micro-accents)
- **Academic Gold**: `#f59e0b` (Prestige Badges, Status Alerts, Npontu Active Borders)
- **Deep Cockpit Dark**: `#0A1128` / `#0F172A` (Sidebar & Control Plane Canvas)
- **Clean Surface Light**: `#F4F7FB` (Main Content Canvas)

### 5.2 Npontu UI Components
1. **Sidebar Navigation**:
   - Collapsible groups: `Control Plane`, `Project Registry`, `Research & Publications`, `Analytics & Reports`, `System & Audit`.
   - Active state: `#102a72` background with `border-l-4 border-[#f59e0b]` and glowing icon.
2. **Action Buttons**:
   - Primary: `bg-gradient-to-r from-[#102a72] to-[#6d28d9] text-white font-semibold rounded-lg shadow-sm border border-indigo-400/30 hover:shadow-indigo-500/25 hover:-translate-y-0.5 transition-all`.
   - Gold Accent / Warning: `bg-[#f59e0b] hover:bg-[#d97706] text-gray-900 font-bold border border-amber-300 shadow-sm`.
   - Ghost / Surface: `bg-white/10 dark:bg-white/5 border border-white/10 text-gray-300 hover:text-white hover:bg-white/15`.
3. **Telemetry & KPI Stat Cards**:
   - Dual-row metrics featuring JetBrains Mono font for numbers, micro-sparkline indicators, and glowing status pills.
4. **Data Tables & Year Filtering**:
   - Year tabs with live project counts (`2026 (12)`, `2025 (34)`, `No Date (3)`).
   - Instant search bar with `Cmd+K` shortcut modal.
   - Organization multi-role badge pills (`Lead Partner`, `Sponsor`, `Funder`).

---

# 6. VERIFICATION & ZERO REGRESSION RULES

1. **Database Backward Compatibility**:
   - All 28+ existing production projects, 15+ partner organizations, and uploaded report PDFs must be seamlessly seeded and accessible in the new schema.
2. **File Serving Security**:
   - PDFs in `reports/` and `publications/` must never be directly executable or accessible via raw web URLs. All requests must route through a secure controller returning `Response::file()` or `Response::download()` with `X-Content-Type-Options: nosniff`.
3. **No Fluff Verification**:
   - Never declare that tests or builds succeeded without running them. Always verify syntax with `php -l` and execute tests via `php artisan test`.
