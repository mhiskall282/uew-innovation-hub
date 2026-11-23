# Changelog

All notable changes to the UEW Innovation Hub Enterprise platform will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [1.0.0] - 2026-11-23

### Added
- **Full Laravel 12 Architecture**: Modern MVC skeleton with separation of concerns across Models, FormRequests, Policies, Controllers, and Domain Services.
- **Npontu SRE Cockpit Design**: Reusable Blade component primitives (`<x-npontu.button>`, `<x-npontu.badge>`, `<x-npontu.card>`, `<x-npontu.stat-card>`), collapsible dark navy sidebar, glowing telemetry indicators, and JetBrains Mono metric counters.
- **Institutional UEW Brand Tokens**: Embedded palette featuring UEW Deep Navy (`#0a1b4d`, `#102a72`), Royal Blue (`#3157d5`), Imperial Purple (`#6d28d9`), Academic Gold (`#f59e0b` / `#fbbf24`), and official UEW Crest.
- **Public Institutional Showcase**: Interactive landing page (`/`) featuring research pillars, live impact counters, featured initiatives, strategic partner directory, and call to action.
- **Dynamic CMS Subsystem**: Engine for publishing and editing News, Research Blogs, and Announcements (`/news-and-blogs` and `/admin/posts`), allowing staff to update content without modifying code.
- **Grant & Incubation Intake Subsystem**: Public proposal application portal (`/apply`) with tracking reference number generation (`UEW-GR-XXXXXX-YYYY`), PDF proposal upload, and committee review dashboard (`/admin/grants`).
- **Granular Multi-Role RBAC**:
  - `super_admin`: Full Hub Director privileges (user provisioning, project deletion, audit log access).
  - `officer`: Research Project Officer (initiative CRUD, grant evaluation, content publishing).
  - `reviewer`: Academic Dean (grant proposal review, evaluation notes).
  - `intern`: Student Intern / NSS Personnel (CMS news/blog drafting, documentation).
  - `viewer`: Public / Academic affiliate (read-only access).
- **Secure File Streaming**: Dedicated `FileStreamController` serving private storage PDFs with strict basename validation, traversal prevention, and `X-Content-Type-Options: nosniff`.
- **Automated Test Suite**: 22 automated feature and unit tests passing across all subsystems with 54 assertions.

### Changed
- Migrated legacy procedural scripts into organized `legacy/` directory for historical preservation.
- Replaced insecure direct web access to `reports/` and `publications/` with protected private storage streaming.
- Replaced raw procedural MySQL queries with Eloquent ORM prepared statements and migration seeders.
