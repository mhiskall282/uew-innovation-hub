# Project Context & Institutional Background

> **Entity**: University of Education, Winneba (UEW) Innovation Hub  
> **Repository**: `uew-inno-hub`  
> **Location**: Winneba, Central Region, Ghana  
> **Lead Institutional Directorate**: Directorate of Research, Innovation and Development (DRID)  
> **Target Stack**: Laravel 12 Enterprise (PHP 8.4+, Blade, Tailwind CSS v4, Alpine.js, Vite)

---

## 1. Project Background

The **UEW Innovation Hub** is the central institutional vehicle for fostering innovation, entrepreneurial research, educational technology incubation, and university-community collaboration at the University of Education, Winneba.

### Historical Context & Legacy Application
The original application was constructed using procedural PHP scripts connecting to a MySQL database (`project_mysql`):
- `index.php`: The primary dashboard listing projects grouped by year, with dynamic modal windows for adding, editing, and deleting initiatives.
- `add_project.php` & `edit_project.php`: Handled project metadata, dynamic multi-organization assignments with roles (e.g. `Sponsor`, `Lead Partner`), and PDF report uploads into `reports/`.
- `delete_project.php`: Deletion routine removing records, relationships, and associated filesystem report files.
- `auth.php`, `login.php`, `logout.php`, `users.php`: Session-based authentication with simple `admin` and `viewer` roles, CSRF token validation, and Bcrypt password hashing.
- `dashboard.php`: Analytics overview displaying total projects, beneficiaries, average reach, partner organizations, year-over-year bar chart, top sponsors, and largest projects.
- `export.php`: Filtered Excel (.xlsx) / CSV generation tool.
- `print.php`: Clean printable tabular compendium of all projects.
- `publications_api.php`: Publications and Newsletters CRUD API storing documents in `publications/`.
- `file.php`: File streaming proxy preventing direct access to PDF files.
- `home.php`: Public showcase landing page highlighting hub impact, mission, vision, and featured projects.

---

## 2. Rebuild Rationale

While functional, the legacy procedural architecture faced scalability and security bottlenecks:
1. **Lack of Structure**: Monolithic files mixing SQL queries, HTML rendering, and business logic.
2. **Maintenance Overhead**: Difficult to add automated testing, CI/CD, or complex permissions.
3. **UI Inconsistency**: Vanilla CSS styles needed modernization to match world-class university hubs.
4. **Desire for Npontu Ergonomics**: The user explicitly requested upgrading the dashboard and buttons to match the sleek, high-density **Npontu SRE dashboard style** while maintaining official UEW colors and logos.

---

## 3. Assets in Repository

- **Institutional Crest / Brand Logo**: `innovation_hub_logo.jpg`  
  *Features the official UEW Innovation Hub logo with royal blue, purple, and academic gold tones.*
- **Project Report Documents**: `reports/`  
  *Contains existing project impact reports in PDF format (e.g., `project_19_1790777535.pdf`, `project_23_1790872941.pdf`, etc.).*
- **Institutional Publications & Newsletters**: `publications/`  
  *Contains official newsletters and publications (e.g., `1791276659_uew_innovation_hub_news_letter_2025_4.pdf`).*

---

## 4. Key Users & Roles

1. **Platform Administrator**:
   Full system access, user management, audit log review, database maintenance, system configuration.
2. **Hub Director / Executive**:
   Strategic overview, executive dashboard access, export generation, impact analytics, publication approvals.
3. **Project Officer / Innovation Lead**:
   Project creation, updating timelines, logging beneficiaries, attaching partner organizations, uploading impact reports.
4. **Researcher / Fellow**:
   Submitting publications, policy briefs, and research deliverables.
5. **Auditor / External Stakeholder (Viewer)**:
   Read-only access to project records, impact verification, and public reports.
6. **Public Visitor**:
   Browsing the public-facing portal (`home.php` / landing page), reading published newsletters, reviewing overall hub impact.

---

## 5. Official Brand Colors & Tokens

- **UEW Navy Blue**: `#102a72`
- **Royal Blue**: `#3157d5`
- **Imperial Purple**: `#6d28d9`
- **Purple Light**: `#8b5cf6`
- **Academic Gold**: `#f59e0b`
- **Gold Light**: `#fbbf24`
- **Cockpit Dark Surface (Npontu style)**: `#0A1128` / `#0F172A`
- **Canvas Surface Light**: `#F4F7FB`
- **Border / Divider**: `#E2E8F0` / `rgba(255,255,255,0.1)`
