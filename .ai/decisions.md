# Architecture Decision Records (ADR)

> **Platform**: UEW Innovation Hub Enterprise Rebuild  

---

## ADR-001 — Migration from Procedural PHP to Laravel 12

### Context
The existing repository consists of standalone procedural PHP files (`index.php`, `add_project.php`, `edit_project.php`, `delete_project.php`, `auth.php`, `dashboard.php`, `export.php`, `print.php`, `publications_api.php`, `users.php`) executing direct MySQL queries and embedding HTML. While functional, it is prone to technical debt, security gaps, and lacks maintainability.

### Decision
Rebuild the platform on **Laravel 12** using modern MVC patterns, Eloquent ORM, FormRequest validation, and Blade components.

### Rationale
- Laravel provides built-in CSRF protection, secure session management, database migrations, and robust routing out of the box.
- Eloquent eliminates manual SQL query strings and provides intuitive relationship handling.
- Service and policy layers allow clear separation of concerns.

### Status
Accepted.

---

## ADR-002 — Tailwind CSS v4 + Alpine.js Frontend Architecture

### Context
The application needs rich, dynamic interactivity (modals, drawer slide-overs, live year filtering, toast notifications) without the complexity and overhead of an SPA framework (like React or Vue).

### Decision
Use **Tailwind CSS v4** with customized `@theme` tokens alongside **Alpine.js 3.x**.

### Rationale
- Tailwind v4 offers zero-config CSS-first token configuration with rapid rendering.
- Alpine.js provides declarative, lightweight DOM reactivity directly inside Blade templates.
- Eliminates the need for API endpoints or complex client-side state management for simple dashboard interactions.

### Status
Accepted.

---

## ADR-003 — Npontu Cockpit Design System with UEW Brand Continuity

### Context
The user explicitly specified: *"we will maintain the colors and logos and etc but we will use npontu style of buttons and customization and how the dashboard style is nice designed"*.

### Decision
Adopt the **Npontu SRE dashboard ergonomics**:
1. Collapsible sidebar navigation with glowing status indicators, section groupings, and active indicator borders (`border-l-4 border-[#f59e0b]`).
2. Telemetry KPI cards featuring JetBrains Mono monospace fonts for impact figures.
3. High-contrast action buttons with subtle gradients, crisp borders, and hover micro-animations.
4. Maintain official UEW colors (`#102a72`, `#3157d5`, `#6d28d9`, `#f59e0b`, `#fbbf24`) and `innovation_hub_logo.jpg`.

### Rationale
Combines the institutional prestige of UEW with the high-productivity, modern developer experience of Npontu's operational dashboards.

### Status
Accepted.

---

## ADR-004 — Normalized Relational Database with Zero-Loss Migration

### Context
The legacy schema used irregular table and column names (`project ID`, `Project Title`, `Number of Beneficiaries`, `Start Date`, `End Date`, `Project Report`). Migrating to standard Laravel conventions could risk data loss if not handled meticulously.

### Decision
1. Create normalized tables (`projects`, `organizations`, `project_organizations`, `publications`, `users`, `activity_logs`).
2. Write a dedicated `MigrateLegacyDataSeeder` that copies all records from `project_mysql` into the new schema while preserving original integer IDs and relational links.

### Status
Accepted.

---

## ADR-005 — Private Storage Isolation & Secure File Streaming

### Context
PDF files in the legacy project resided directly in web-accessible folders (`reports/` and `publications/`), relying on `.htaccess` rules for security.

### Decision
Store all uploaded documents in Laravel's private disk (`storage/app/private/`) and stream files via an authenticated, rate-limited controller with `X-Content-Type-Options: nosniff`.

### Status
Accepted.

---

## ADR-006 — Comprehensive Audit Logging

### Context
Institutional donor accountability requires recording who altered project details, registered partners, or exported data.

### Decision
Implement an immutable `activity_logs` table tracking user ID, action verb, entity type, entity ID, metadata, and IP address.

### Status
Accepted.
