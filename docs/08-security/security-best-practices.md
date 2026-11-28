# Security Best Practices

> **Audience:** developers, administrators · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Coding standards](../05-developer-guide/coding-standards.md), [Adding new features](../05-developer-guide/adding-new-features.md), [Input validation](input-validation.md), [Authorization security](authorization-security.md), [Security testing](../10-testing-and-quality/security-testing.md)

Practical rules for people changing or running the Hub. Each rule points to an example already in the code. Items labelled "Recommended, not implemented" are advice only.

## For developers

### Adding a route

1. Put it in the right group in `routes/web.php` so it inherits `auth,active,verified.email` (and `onboarded` for members, `can:workspace.access` for staff).
2. Add `can:<permission>` when one permission covers the whole route. For record-level rules call `Gate::authorize('view', $record)` and add a policy method.
3. Add a throttle to anything that sends mail, accepts uploads or accepts free text from members (examples: `throttle:20,1` on messages).
4. Add the route to `AccessControlTest` or `PrivilegesTest` expectations. `PrivilegesTest::every_state_changing_workspace_route_enforces_its_permission` fails if a state-changing workspace route lacks a gate.
5. If the route is private, add its prefix to the `noindex` list in [`SecurityHeaders`](../../app/Http/Middleware/SecurityHeaders.php). If a CMS slug could clash, add it to the reserved list in `Workspace/PageController`.

### Handling input

- Create a `FormRequest` with explicit rules. Do not call `$request->all()` into `fill()`.
- Keep privileged attributes out of `$fillable`; set them in a service with `forceFill`, as `UserService` and `ProgramApplication` do.
- Use Eloquent or the query builder with bindings. Never build SQL from request strings. The `Searchable` trait shows how to escape `LIKE` wildcards.
- For uploads reuse `DocumentStorage` (PDF) or `GalleryService` (photos). Do not use the client file name or extension.
- Validate redirects: accept only relative paths that start with a single `/`.

### Producing output

- Use `{{ }}`. Use `{!! !!}` only for HTML you built, and escape every dynamic piece with `e()`. Markdown from users goes through `Str::markdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false])`.
- Never write secrets, one-time codes or passwords to logs. The app does not log them; do not add it.
- Neutralise formulas in CSV values that begin with `=`, `+`, `-` or `@` (see `ReportExportService` and `ApplicationService`, which prefix such values with `'`).
- Expose API data only through `app/Http/Resources/Api/V1` and `V2` Resources with an explicit field list.

### Auditing

Call `AuditLogger::record($action, $description, $subject, $properties)` for every create, update, delete, export, permission or setting change. Use `AuditLogger::diff($model)` before `save()` for updates; it hides password values. Redact secrets with the `redact` argument of `SettingsService::update`.

### Passwords, codes and tokens

- Hash with `Hash::make` (bcrypt). Compare with `Hash::check`.
- Use `random_int` for codes, `Str::random` or `Str::uuid` for file names.
- Make code and link consumption atomic with a transaction and `lockForUpdate()`, as in `VerificationService`.
- Use `URL::temporarySignedRoute` with an expiry for anything that changes state. GET pages that perform a state change are not acceptable: show a confirmation page and act on POST.

### Tests

Every security rule above has a test in `tests/Feature`. Add one with each change. See [security testing](../10-testing-and-quality/security-testing.md).

### Dependencies and CI

- Run `composer audit --locked` and `npm audit --omit=dev` before merging dependency changes (CI does the same).
- Run `vendor/bin/pint` before committing (CI runs `pint --test`).
- Do not add third-party scripts, fonts or analytics without updating the CSP in `SecurityHeaders` and recording the decision in [architectural decisions](../04-architecture/architectural-decisions.md).

## For administrators and the Hub Director

- Use a unique, long password and do not share the account. There is no MFA, so the password is the only protection.
- Give people the lowest role that does their job (see [user roles and permissions](../02-user-guide/user-roles-and-permissions.md)). Interns draft, Officers publish, Reviewers decide, Read-only Staff view.
- Deactivate accounts the day a person leaves. Deactivation signs them out on their next request.
- Review `/workspace/audit` weekly for failed sign-ins, new users and role changes ([security monitoring](security-monitoring.md)).
- Before programme questions pre-fill from gender or date of birth, decide whether reviewers should see that answer. The snapshot view hides gender and disability, answers are not hidden.
- Keep the Privacy page accurate (Pages in the workspace). It was written for new installs and needs legal review (`docs/audit` F-08).
- Export member lists only when needed, store them securely and delete them after use. The export is audited (`member.exported`), but the downloaded file is not protected.
- Use a real mail provider on any site with real users. Do not leave `MAIL_MAILER=log`.

## For operators

- Set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `APP_URL=https://...` ([secure configuration](secure-configuration.md)).
- On any hosted demo set `SEED_PASSWORD` (12+ characters) and `BLOCK_DEFAULT_PASSWORDS=true`. Remove demo data before real use (`SEED_DEMO_DATA=false`).
- Use S3 or R2 for documents on Render's free plan (`DOCUMENTS_DISK=s3`).
- Enable GitHub branch protection on `main` and Render's "Wait for CI" so a red build does not deploy.
- Back up the PostgreSQL database and the documents bucket on a schedule ([backup and recovery](../09-deployment-and-operations/backup-and-recovery.md)).
- Rotate secrets when staff with dashboard access leave ([secrets management](secrets-management.md)).
- Run `powershell -File scripts/security-scan.ps1` before releases.

## Recommended, not implemented

| Practice | Why |
| --- | --- |
| Two-factor authentication for staff | Largest single gap |
| Breached-password check (`Password::uncompromised()`) | Stops known leaked passwords |
| Secret scanning, CodeQL and Dependabot | Automates dependency and secret checks |
| Alerts on lockouts and role changes | Faster detection |
| Scheduled pruning of codes, notifications, logs | Data minimisation |
| Antivirus scan of uploaded PDFs | Defence in depth |
| Alpine CSP build to drop `unsafe-eval` | Stronger CSP |
| Audit entries for document access | Accountability |
