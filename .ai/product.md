# Product Specification & Requirements

> **Platform**: UEW Innovation Hub Enterprise  
> **Mandate**: Research, Incubation, Grant Management, and Innovation Dissemination  
> **Target Audience**: University Executives, Project Officers, Researchers, International Partners, General Public  

---

## 1. Product Vision

The **UEW Innovation Hub Management System** is the digital backbone for all educational innovation, grants, community impact initiatives, and technological incubation projects originating from the University of Education, Winneba.

The platform provides:
1. **Institutional Showcase**: A premier public face communicating research milestones, impact numbers, and downloadable publications.
2. **Operations & Registry Cockpit**: An information-rich, Npontu-styled interface for tracking multi-partner projects, grant lifecycles, and beneficiary impact.
3. **Accountability & Reporting**: Real-time KPI telemetry, automated spreadsheet generation (Excel/CSV), and high-resolution print compendiums for institutional donors and university councils.

---

## 2. User Personas & Journeys

### 2.1 Project Officer / Innovation Lead
- **Goal**: Quickly record new innovation initiatives, attach partnering institutions with their specific roles, log beneficiary metrics, and upload official PDF completion reports.
- **Journey**:
  1. Accesses authenticated cockpit (`/dashboard` or `/projects`).
  2. Clicks the high-contrast Npontu "+ Register Project" button.
  3. Fills in project title, start/end dates, estimated beneficiaries, and selects strategic innovation pillar.
  4. Dynamically attaches partner organizations (e.g. selects existing organization from searchable dropdown or types a new organization name) and assigns roles (`Funder`, `Sponsor`, `Lead Partner`).
  5. Attaches the validated PDF report.
  6. Submits; system performs atomic transaction, generates clean slug, stores file in protected storage, logs audit trail, and flashes success toast.

### 2.2 Hub Director / Executive
- **Goal**: Gain immediate clarity on hub performance, funding distribution, top institutional sponsors, and generate council reports.
- **Journey**:
  1. Opens executive cockpit dashboard.
  2. Inspects real-time telemetry metrics: Total Projects, Total Beneficiaries Reached, Active Partner Organizations, Average Beneficiary Reach per Initiative.
  3. Uses year tabs (`All`, `2026`, `2025`, `2024`, `No Date`) to slice data instantly.
  4. Reviews Sponsor Ranking table to identify leading external supporters.
  5. Clicks "Export Compendium" to trigger a streaming download of formatted Excel (.xlsx) or CSV reports.
  6. Opens "Print Compendium" for a clean, paginated, university-crested physical report.

### 2.3 Researcher / Academic Fellow
- **Goal**: Access and publish newsletters, policy briefs, and innovation documents.
- **Journey**:
  1. Navigates to `/publications`.
  2. Searches by title or filters by type (`Publication`, `Newsletter`, `Policy Brief`).
  3. Previews document inline or downloads directly with zero latency.

### 2.4 External Auditor / Public Visitor
- **Goal**: Verify UEW's community impact and educational technology footprints.
- **Journey**:
  1. Lands on public portal (`/`).
  2. Explores high-impact statistics counter, featured projects, and recent newsletters.
  3. Experiences fast, responsive, mobile-first design.

---

## 3. Functional Requirements Matrix

| ID | Feature Module | Requirement Description | User Role |
| :--- | :--- | :--- | :--- |
| **FR-01** | **Project Registry** | View all projects grouped by year with pagination and multi-column sorting | All authenticated users |
| **FR-02** | **Project Creation** | Modal or dedicated page to register project title, dates, beneficiaries, pillar, and PDF report | Project Officer, Admin |
| **FR-03** | **Dynamic Partner Matrix** | Add multiple organizations per project with distinct roles; supports inline organization creation | Project Officer, Admin |
| **FR-04** | **Project Modification** | Edit all project attributes, add/remove partners, replace or retain existing report PDF | Project Officer, Admin |
| **FR-05** | **Safe Deletion** | Soft-delete projects with confirmation dialog, preserving audit trail and archiving reports | Admin |
| **FR-06** | **Telemetry Cockpit** | Dashboard with JetBrains Mono KPI counters, year-over-year distribution, sponsor ranking, and largest projects | Director, Officer, Admin |
| **FR-07** | **Interactive Filters** | Filter projects instantly by Year pills, Status, Organization, and Keyword search | All users |
| **FR-08** | **Quick Command Search** | Global `Cmd+K` keyboard shortcut modal for rapid project and partner lookup | Authenticated users |
| **FR-09** | **Excel & CSV Export** | Generate filtered `.xlsx` and `.csv` files matching active year/search filters | Director, Officer, Admin |
| **FR-10** | **Print Compendium** | Clean print CSS media layout with UEW crest, project matrix, and partner summary | All users |
| **FR-11** | **Publications Engine** | Upload, categorize, and manage newsletters and research briefs with PDF delivery | Admin, Officer |
| **FR-12** | **Secure Document Streaming** | Stream PDFs via authenticated route with MIME validation, anti-traversal, and nosniff headers | All authorized |
| **FR-13** | **User Management** | Manage user accounts, assign roles (`admin`, `director`, `officer`, `researcher`, `viewer`), reset passwords | Platform Admin |
| **FR-14** | **Activity Audit Trail** | Log critical mutations (create, edit, delete, export) with user ID, timestamp, and IP | Admin |

---

## 4. Non-Functional Requirements

- **Performance**: Dashboard loads in under 350ms; Excel exports for 1,000+ rows stream in under 1 second.
- **Accessibility**: WCAG 2.1 AA compliant color contrast, focus rings on all interactive elements, keyboard-accessible modals.
- **Mobile Responsiveness**: Structural layouts seamlessly adapt across 320px, 375px, 640px, 768px, 1024px, and 1440px+.
- **Security**: 100% parameter-bound queries, CSRF on all mutation verbs, server-side MIME verification, password hashing via Bcrypt.
