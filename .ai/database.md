# Database Schema & Data Migration Specification

> **Platform**: UEW Innovation Hub Enterprise  
> **Source Database**: Legacy `project_mysql` (procedural schema)  
> **Target Database Engine**: MySQL 8.0+ / PostgreSQL 16+ (Strict InnoDB, UTF8mb4)  
> **ORM**: Laravel Eloquent with Strict Type Casting & Foreign Key Constraints  

---

## 1. Database Philosophy & Migration Strategy

The legacy schema stored critical data with inconsistent naming conventions (spaces in column names like `project ID`, `Project Title`, `Number of Beneficiaries`, `Start Date`, `End Date`, `Project Report`).

The new Laravel architecture:
1. **Normalizes Field Names**: Strictly follows Laravel conventions (`snake_case` column names, plural table names).
2. **Guarantees Zero Data Loss**: Provides an automated artisan import seeder (`MigrateLegacyDataSeeder`) that maps 100% of the data from the legacy tables into the new schema.
3. **Enforces Referential Integrity**: Proper foreign keys with explicit constraints (`onDelete('cascade')` for pivot rows, `onDelete('restrict')` for organizations with active projects).
4. **Optimizes with Strategic Indexes**: Composite indexes on `[status, start_date]`, fulltext indexes on project titles and abstracts, unique index on slugs.

---

## 2. Entity Relationship Overview

```text
┌────────────────────────┐         ┌────────────────────────┐
│     organizations      │◀────────│ project_organizations  │
│────────────────────────│   1:N   │────────────────────────│
│ id (PK)                │         │ project_id (FK)        │
│ name (unique)          │         │ organization_id (FK)   │
│ slug (unique)          │         │ role (e.g. Sponsor)    │
│ type (Partner/Funder)  │         │ is_lead (boolean)      │
└────────────────────────┘         └───────────┬────────────┘
                                               │ N:1
                                               ▼
┌────────────────────────┐         ┌────────────────────────┐
│         users          │         │        projects        │
│────────────────────────│         │────────────────────────│
│ id (PK)                │◀────────│ id (PK)                │
│ username (unique)      │  1:N    │ title                  │
│ role (admin/director)  │         │ slug (unique)          │
│ password (hashed)      │         │ beneficiaries_count    │
└───────────┬────────────┘         │ start_date / end_date  │
            │                      │ status (enum)          │
            │ 1:N                  │ report_file_path       │
            ▼                      │ softDeletes            │
┌────────────────────────┐         └────────────────────────┘
│     activity_logs      │
│────────────────────────│         ┌────────────────────────┐
│ id (PK)                │         │      publications      │
│ user_id (FK)           │         │────────────────────────│
│ action (create/update) │         │ id (PK)                │
│ entity_type/entity_id  │         │ title / type           │
│ ip_address             │         │ file_path / date       │
└────────────────────────┘         └────────────────────────┘
```

---

## 3. Schema Definitions

### 3.1 `projects` (Normalized from `innovation_hub`)
```php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->string('title', 300);
    $table->string('slug', 350)->unique();
    $table->text('abstract')->nullable();
    $table->string('pillar', 100)->default('General Innovation'); // EdTech, STEM, Community, AgTech
    $table->unsignedInteger('beneficiaries_count')->default(0)->index();
    $table->date('start_date')->nullable()->index();
    $table->date('end_date')->nullable();
    $table->string('status', 50)->default('active')->index(); // planned, active, completed, on_hold, archived
    $table->string('report_file_path', 255)->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->softDeletes();
    $table->timestamps();

    $table->index(['status', 'start_date']);
});
```

### 3.2 `organizations` (Normalized from `organization`)
```php
Schema::create('organizations', function (Blueprint $table) {
    $table->id();
    $table->string('name', 200)->unique();
    $table->string('slug', 220)->unique();
    $table->string('type', 100)->default('Partner'); // Funder, Sponsor, Industry, NGO, Academic
    $table->string('contact_email', 150)->nullable();
    $table->string('website', 255)->nullable();
    $table->timestamps();
});
```

### 3.3 `project_organizations` (Pivot Table with Granular Roles)
```php
Schema::create('project_organizations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
    $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
    $table->string('role', 100)->default('Partner'); // Sponsor, Funder, Implementing Partner, Lead Partner
    $table->boolean('is_lead')->default(false);
    $table->timestamps();

    $table->unique(['project_id', 'organization_id', 'role'], 'proj_org_role_unique');
    $table->index(['project_id', 'role']);
});
```

### 3.4 `publications` (Normalized from `publications`)
```php
Schema::create('publications', function (Blueprint $table) {
    $table->id();
    $table->string('title', 300);
    $table->string('slug', 350)->unique();
    $table->string('type', 80)->default('Publication'); // Publication, Newsletter, Policy Brief, Report
    $table->text('description')->nullable();
    $table->date('publication_date')->nullable()->index();
    $table->string('file_name', 255);
    $table->unsignedBigInteger('file_size_bytes')->nullable();
    $table->unsignedInteger('downloads_count')->default(0);
    $table->string('authors', 255)->nullable();
    $table->boolean('is_published')->default(true)->index();
    $table->timestamps();
});
```

### 3.5 `users` (Normalized with Multi-Role Capability)
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name', 150);
    $table->string('username', 100)->unique();
    $table->string('email', 150)->unique()->nullable();
    $table->string('password');
    $table->string('role', 50)->default('viewer'); // admin, director, officer, researcher, viewer
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_login_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
});
```

### 3.6 `activity_logs` (Audit Trail)
```php
Schema::create('activity_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('action', 100); // create, update, delete, export, login
    $table->string('entity_type', 100)->nullable();
    $table->unsignedBigInteger('entity_id')->nullable();
    $table->text('details')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->timestamp('created_at')->useCurrent();

    $table->index(['entity_type', 'entity_id']);
    $table->index('created_at');
});
```

---

## 4. Legacy Data Migration Mapping

| Legacy MySQL Source Table & Column | Target Laravel Table & Column | Transformation / Rule |
| :--- | :--- | :--- |
| `innovation_hub`.`project ID` | `projects`.`id` | Retain original integer primary key to avoid link breakage |
| `innovation_hub`.`Project Title` | `projects`.`title` | Trimmed string, generates `slug` automatically |
| `innovation_hub`.`Number of Beneficiaries` | `projects`.`beneficiaries_count` | Cast to integer (defaults to 0) |
| `innovation_hub`.`Start Date` | `projects`.`start_date` | Date format `YYYY-MM-DD` |
| `innovation_hub`.`End Date` | `projects`.`end_date` | Date format `YYYY-MM-DD` |
| `innovation_hub`.`Project Report` | `projects`.`report_file_path` | Filename referencing `storage/app/reports/` |
| `organization`.`organization_id` | `organizations`.`id` | Retain original primary key |
| `organization`.`organization_name`| `organizations`.`name` | Unique, trimmed, slug auto-generated |
| `project_organisations`.`project_id` | `project_organizations`.`project_id` | Foreign key reference |
| `project_organisations`.`organization_id` | `project_organizations`.`organization_id` | Foreign key reference |
| `project_organisations`.`role` | `project_organizations`.`role` | E.g. 'Sponsor', 'Funder', 'Lead Partner' |
| `publications`.`id` | `publications`.`id` | Retain primary key |
| `publications`.`file_name` | `publications`.`file_name` | Stored under `storage/app/publications/` |
| `users`.`id` | `users`.`id` | Retain primary key |
| `users`.`password_hash` | `users`.`password` | Native Bcrypt hashes are directly compatible with Laravel `Hash::check()` |
| `users`.`role` | `users`.`role` | Map 'admin' ➔ 'admin', others ➔ 'viewer' or designated role |
