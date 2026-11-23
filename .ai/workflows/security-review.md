# Security Review Workflow

> **Platform**: UEW Innovation Hub Enterprise

---

## 1. Authentication & Session Review
- [ ] Login route is throttled (`throttle:login`, max 5/minute).
- [ ] Sessions regenerate on authentication (`session()->regenerate()`).
- [ ] Passwords are hashed with Bcrypt (work factor ≥ 12).
- [ ] Logout properly invalidates session and regenerates CSRF token.

## 2. CSRF & Input Validation
- [ ] All state-altering routes include `@csrf` or verify `X-CSRF-TOKEN`.
- [ ] Every controller action receiving input uses a dedicated `FormRequest`.
- [ ] File uploads validate MIME type (`application/pdf`), max size, and sanitize filenames.

## 3. Authorization
- [ ] Every controller action verifies user permissions via `$this->authorize()` or `can:` middleware.
- [ ] Admin-only routes are protected by both middleware and policy checks.
- [ ] Users cannot access, modify, or delete resources they do not own or have permission for.

## 4. Data Layer
- [ ] All queries use Eloquent ORM or parameterized Query Builder. No raw concatenation.
- [ ] Blade views use `{{ }}` escaping. No unescaped `{!! !!}` without HTML purification.

## 5. File & Storage Security
- [ ] PDFs are stored in private storage (`storage/app/private/`), not in `public/`.
- [ ] File streaming controller validates filenames with strict regex and uses `basename()`.
- [ ] Response headers include `X-Content-Type-Options: nosniff`.
- [ ] `.htaccess` or server config blocks script execution in storage directories.

## 6. PHP Runtime
- [ ] `session.upload_progress.enabled` is set to `0` in `php.ini`.
- [ ] `APP_DEBUG` is `false` in production.
- [ ] PHP version is 8.4.27+ or a patched release.
