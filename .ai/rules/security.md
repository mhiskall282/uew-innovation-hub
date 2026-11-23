# Security Rules

> **Platform**: UEW Innovation Hub Enterprise  

1. **Mandatory CSRF Verification**: Always verify CSRF on all `POST`, `PUT`, `PATCH`, and `DELETE` requests using `@csrf` or `X-CSRF-TOKEN`.
2. **Server-Side Validation**: Never rely on client-side constraints. Every incoming payload must pass through a dedicated `FormRequest` class.
3. **Server-Side Authorization**: Enforce permissions on every controller action via `$this->authorize('action', $model)` or `can:` middleware. Never rely on frontend UI hiding.
4. **Zero SQL Injection**: Eloquent ORM and parameterized query bindings are mandatory. Raw string concatenations in queries are forbidden.
5. **Escape Blade Outputs**: Always use standard Blade double braces `{{ $content }}`. Do not use unescaped `{!! !!}` without prior purification through an HTML sanitizer.
6. **File Upload Hardening**:
   - Strictly validate MIME types (`application/pdf` only for reports and publications).
   - Set maximum file size limit (`max:25600` KB).
   - Randomize disk filenames (`Str::random(40) . '.pdf'`) to eliminate path traversal and arbitrary code execution vectors.
7. **Secrets Isolation**: Secrets, API keys, database credentials, and signing keys must reside in `.env` and be retrieved solely through `config()`, never direct `env()` in app code.
8. **Rate Limiting**: Protect authentication (`throttle:login`), password resets, and file downloads with rate limiting middleware.
9. **No Public Debug Traces**: `APP_DEBUG` must be set to `false` in production to prevent stack trace leakage and server information disclosure.
10. **Session Hardening**: Sessions must enforce `http_only`, `same_site => lax`, and cookie encryption.
