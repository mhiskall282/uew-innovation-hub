# Security Protocols & Data Protection Specification

> **Platform**: UEW Innovation Hub Enterprise  
> **Standards**: OWASP Top 10, Republic of Ghana Data Protection Act (Act 843), Academic Governance Protocols  

---

## 1. Security Philosophy

Institutional research records, donor partnerships, and grant metrics require uncompromising data integrity and defensive software engineering. Security controls are enforced at every architectural layer: transport, application, framework, database, and storage.

---

## 2. Core Security Protocols

### 2.1 CSRF Protection
- All state-altering HTTP verbs (`POST`, `PUT`, `PATCH`, `DELETE`) must verify CSRF tokens via `@csrf` or `X-CSRF-TOKEN` headers.
- Never disable or bypass CSRF verification for internal endpoints.

### 2.2 Server-Side FormRequest Validation
- Never trust client-side inputs or JavaScript validation.
- All incoming requests must be handled by dedicated `FormRequest` classes:
  - `StoreProjectRequest`: Validates title, dates, positive beneficiaries, role arrays, and PDF files.
  - `UpdateProjectRequest`: Validates project modifications and partner updates.
  - `StorePublicationRequest`: Validates title, publication type enum, and document MIME type.
- File upload constraints:
  - Allowed MIME: strictly `application/pdf`.
  - Max size: `25M` (or 25,600 KB).
  - Storage filenames are cryptographically randomized (`Str::random(40) . '.pdf'`) to eliminate path traversal, character injection, and user-supplied naming collisions.

### 2.3 SQL Injection Prevention
- Zero raw query concatenations. All queries must utilize Eloquent ORM or parameterized Query Builder methods.
- `DB::raw()` is forbidden whenever user input is involved.

### 2.4 Cross-Site Scripting (XSS) Prevention
- Always use standard Blade double-brace escaping `{{ $variable }}` which invokes `htmlspecialchars()`.
- The unescaped syntax `{!! $variable !!}` is strictly prohibited unless rendering sanitized HTML through an audited purifier.
- Content Security Policy (CSP) headers must prohibit inline execution of untrusted scripts.

### 2.5 Authentication & Password Management
- Passwords are encrypted using Bcrypt with a work factor of 12.
- Minimum password length: 8 characters with complexity requirements on administrative roles.
- Login route throttled via `throttle:login` (maximum 5 attempts per minute per IP/username combination).
- Session fixation prevention: `session()->regenerate()` invoked upon every successful login.

### 2.6 Granular Server-Side Authorization (RBAC)
- Never rely on UI hiding (e.g. hiding an edit button) as an authorization mechanism.
- Every controller action must invoke `$this->authorize('action', $model)` or pass through the `can:` route middleware.
- Only users with the `admin` role may delete projects, manage system users, or inspect raw audit logs.

### 2.7 Document Streaming & Directory Traversal Protection
- Project reports and publication PDFs must reside in private storage (`storage/app/private/reports` and `storage/app/private/publications`), completely outside the public web root.
- The file streaming controller validates file existence using basename sanitation:
  ```php
  $cleanName = basename($filename);
  if (!preg_match('/^[A-Za-z0-9._-]+\.pdf$/i', $cleanName)) {
      abort(400, 'Invalid file request.');
  }
  ```
- Responses are served with:
  - `Content-Type: application/pdf`
  - `X-Content-Type-Options: nosniff`
  - `Content-Disposition: inline; filename="..."`
  - `Cache-Control: private, max-age=3600`

### 2.8 PHP Runtime Core Hardening
- **Session Upload Progress**: Set `session.upload_progress.enabled = 0` in `php.ini` to mitigate state corruption and use-after-free RCE vectors.
- **Execution Denial in Storage**: Ensure `.htaccess` or web server config blocks execution of any script (`.php`, `.phtml`, `.sh`, `.cgi`) inside public asset or storage folders.

---

## 3. Ghana Data Protection Act (Act 843) Compliance

1. **Beneficiary Data Privacy**: Beneficiary counts are aggregated and anonymized; no personally identifiable student or citizen records are exposed without explicit consent.
2. **Audit Logging**: Every project modification, user creation, or document deletion is immutably logged in `activity_logs` with timestamps and originating IP addresses.
3. **Institutional Accountability**: Grant funding figures and donor partner associations are auditable by authorized directorate personnel.
