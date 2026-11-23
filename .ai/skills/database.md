# Database Engineering & Legacy Migration Skill

> **Platform**: UEW Innovation Hub Enterprise  
> **Domain**: Data Persistence, Migrations, and Relational Integrity

---

## 1. Normalized Relational Design
- `projects`: Primary research and innovation initiatives with status, dates, grants, and file metadata.
- `organizations`: Partner organizations, funding agencies, and external collaborators.
- `project_organizations`: Normalized many-to-many junction table with role (Funder, Implementer, Research Partner).
- `publications`: Research papers, policy briefs, and innovation newsletter issues.
- `users`: Institutional staff with roles (Super Admin, Project Officer, Academic Reviewer, Viewer).
- `activity_logs`: Audit trail recording actor, action, IP address, and payload diff.

---

## 2. Migration Seeder for Legacy Data
A dedicated Artisan command/seeder `MigrateLegacyDataSeeder` handles zero-data-loss extraction:
1. Connects to the legacy MySQL database (`project_mysql`).
2. Reads legacy `innovation_hub` table and parses comma-separated `organisations` into normalized `organizations` and `project_organizations` entries.
3. Maps legacy status text (`Active`, `Completed`, `Suspended`) to strongly typed enum values.
4. Moves physical report files from legacy `reports/` and `publications/` to Laravel private storage `storage/app/private/`.
5. Verifies record counts match 100% prior to concluding.
