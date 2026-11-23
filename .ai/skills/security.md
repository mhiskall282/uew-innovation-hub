# Security & Hardening Skill

> **Platform**: UEW Innovation Hub Enterprise  
> **Domain**: Institutional Data Protection, RBAC, and Vulnerability Mitigation

---

## 1. Key Protections
- **Authentication**: Laravel Session Auth with `throttle:login` rate limiting (5 attempts/min) and password hashing with Bcrypt work factor 12.
- **Role-Based Access Control**: Strict role hierarchy (Super Admin > Project Officer > Academic Reviewer > Viewer) verified via Laravel Policies.
- **CSRF Defense**: Automatic CSRF token generation and validation on all `POST`, `PUT`, `PATCH`, `DELETE` routes.
- **File Security**: Reports and publications stored in `storage/app/private/`, never web-accessible directly. Streamed via `FileStreamController` with strict regex basename validation and `X-Content-Type-Options: nosniff`.
- **SQL Injection Prevention**: Eloquent ORM prepared statements across 100% of queries.
- **XSS Prevention**: Automatic Blade escaping `{{ }}` on all user-supplied text.
- **PHP Runtime**: Hardened `.user.ini` disabling `session.upload_progress.enabled` to mitigate CVE-2024-4577 and file inclusion vectors.
