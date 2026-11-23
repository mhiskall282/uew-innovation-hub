# Testing Strategy & Quality Assurance

> **Platform**: UEW Innovation Hub Enterprise  
> **Testing Frameworks**: PHPUnit 11 / Pest 3 with Laravel Testing Tools  

---

## 1. Testing Philosophy

Automated testing guarantees that institutional research records, partner contributions, and grant metrics remain intact throughout code refactoring and feature additions.

- **Fast & Isolated Unit Tests**: Test business rules (e.g. beneficiary calculations, date comparisons, slug generation) in pure PHP without DB overhead.
- **Realistic Feature Tests**: Test HTTP endpoints using Laravel's `RefreshDatabase` trait against SQLite in-memory or a dedicated test MySQL database.
- **Strict Verification Protocol**: Never claim tests passed unless executed directly in the terminal via `php artisan test`.

---

## 2. Test Architecture & Pyramids

```text
       /\
      /  \      E2E / Smoke Tests (Critical export & print compendium workflows)
     /────\
    /      \    Feature Tests (HTTP routes, FormRequests, Policies, CRUD)
   /────────\
  /          \  Unit Tests (Services, Models, Enums, Query Scopes)
 /────────────\
```

### Test Directory Layout
```text
tests/
├── Feature/
│   ├── Admin/
│   │   ├── UserManagementTest.php
│   │   └── AuditLogTest.php
│   ├── Auth/
│   │   ├── AuthenticationTest.php
│   │   └── PasswordResetTest.php
│   ├── Exports/
│   │   ├── ProjectExcelExportTest.php
│   │   └── ProjectCsvExportTest.php
│   ├── Media/
│   │   ├── DocumentStreamTest.php
│   │   └── FileUploadValidationTest.php
│   ├── Projects/
│   │   ├── ProjectCreationTest.php
│   │   ├── ProjectModificationTest.php
│   │   ├── PartnerMatrixTest.php
│   │   └── ProjectYearFilterTest.php
│   └── Publications/
│       ├── PublicationCrudTest.php
│       └── PublicationDownloadTest.php
└── Unit/
    ├── AnalyticsServiceTest.php
    ├── ProjectModelTest.php
    └── ReportExportServiceTest.php
```

---

## 3. Mandatory Test Coverage Areas

### 3.1 Project Lifecycle & Consortium Management
- Creating a project with single and multiple partner organizations and distinct roles (`Sponsor`, `Funder`, `Lead Partner`).
- Attempting to register a project without a title or with invalid dates (e.g. end date before start date).
- Verifying inline creation of new partner organizations.
- Editing an existing project: ensuring updated partner roles synchronize cleanly without orphaned records.
- Soft-deleting a project and confirming that associated relationships remain queryable for audit purposes.

### 3.2 File Upload & Streaming Isolation
- Uploading a valid PDF document and verifying it is saved to the private disk with a sanitized, randomized hash name.
- Uploading non-PDF files (e.g. `.php`, `.exe`, `.jpg`) and verifying immediate HTTP 422 rejection.
- Attempting path traversal attacks (e.g. `../../etc/passwd` or `..\..\..\windows\win.ini`) against `FileStreamController` and verifying HTTP 400 rejection.
- Confirming that file streaming sends `X-Content-Type-Options: nosniff` and appropriate cache headers.

### 3.3 Analytics & Reporting Precision
- Calculating total beneficiaries, average reach, and year-over-year counts against known seed data.
- Testing Excel (.xlsx) and CSV export endpoints with different year parameters (`all`, `2026`, `nodate`) to ensure returned row counts and totals match database records.
