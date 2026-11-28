# Coding Standards

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [naming-conventions](naming-conventions.md), [backend-development](backend-development.md), [frontend-development](frontend-development.md), `../04-architecture/design-patterns.md`

The rules below come from the existing code, `pint.json`, `.editorconfig` and `.ai/rules/*.md`. Where the code does not follow a rule everywhere, the page says so. New code should follow the rule.

## Enforced by tooling

| Tool | Config | What it enforces | Command |
| --- | --- | --- | --- |
| Laravel Pint | `pint.json`: `"preset": "laravel"`, excludes `legacy` and `api` | PHP style (PSR-12 based, Laravel preset) | `vendor/bin/pint` fixes, `vendor/bin/pint --test` checks. CI job "Code style" runs `vendor/bin/pint --test` |
| EditorConfig | `.editorconfig` | UTF-8, LF, 4 spaces (2 for YAML), final newline, trim trailing whitespace (not in `*.md`) | Editor plugin |
| Git attributes | `.gitattributes` | `* text=auto eol=lf` | Git |

A pull request that fails `vendor/bin/pint --test` fails CI. Run Pint before every push.

## PHP rules taken from the code

1. **Strict types.** Every file under `app/` starts with `declare(strict_types=1);` (the one exception is `app/Http/Controllers/Controller.php`). Many files in `tests/` and `database/` omit it. Match the file you edit, and use it in new application code.
2. **Thin controllers.** A controller authorises, calls a service, and returns a view or redirect. Example: `app/Http/Controllers/Workspace/AlbumController.php` calls `Gate::authorize(...)` then `$this->gallery->save(...)`.
3. **Business logic in services.** Classes in `app/Services/` are `final`, take dependencies through the constructor (`private readonly`), and wrap multi-table writes in `DB::transaction`. Example: `ProjectService::create`.
4. **Audit every state change.** Services call `AuditLogger::record('<model>.<verb>', '<sentence>', $model, $changes)`. Actor and IP come from the session, never from input.
5. **Authorise on the server.** Routes carry `can:<permission>` middleware, and controllers call `Gate::authorize('<ability>', $model)` for record-level rules. Hiding a button in Blade is not security.
6. **Enums instead of strings.** Statuses, types and roles are backed enums in `app/Enums/`. Models cast to them, and `Rule::enum(...)` validates them. Enums carry `label()`, `tone()` and `options()` helpers for the UI.
7. **Validate with FormRequests.** Classes in `app/Http/Requests/{Auth,Portal,Site,Workspace}/` hold `rules()`, `messages()` and `prepareForValidation()`. Several controllers still validate inline (for example `AlbumController::validated()`, `OrganizationController`, `ProgramController`). That is existing practice, but new code should use a FormRequest, as `.ai/rules/laravel.md` and `.ai/rules/security.md` require.
8. **Eloquent, no raw SQL.** Use relationships, scopes (`scopePublished`) and bindings. Do not concatenate values into `DB::raw`.
9. **Eager-load.** `AppServiceProvider` calls `Model::preventLazyLoading(! $this->app->isProduction())`. A lazy-loaded relationship throws `LazyLoadingViolationException` in local and test environments. Use `with()`, `load()` and `withCount()`.
10. **Casts as a method.** Models define `protected function casts(): array` (Laravel 11+ style), and set `$fillable` explicitly. Fields set only by services (such as `is_published`, `cover_photo_id` on `Album`) stay out of `$fillable`.
11. **Config, not `env()`.** Read values with `config('hub.documents_disk')`. Call `env()` only inside `config/*.php` (and `bootstrap/app.php` for `TRUSTED_PROXIES`).
12. **PHPDoc where types are not obvious.** Array shapes are documented (`@param array<string, mixed> $data`, `@return list<Permission>`). One short doc comment says why a class exists.
13. **Comments explain why.** Look at the comment on `GalleryService` (GD re-encoding drops EXIF and defeats disguised files) for the level of detail expected.
14. **No new dependencies without need.** Current runtime packages: `laravel/framework`, `laravel/tinker`, `barryvdh/laravel-dompdf`, `league/flysystem-aws-s3-v3`. See [dependency-management](dependency-management.md).

## Security-relevant rules

From `.ai/rules/security.md`, checked against the code:

- Use `@csrf` on every form that posts. The only CSRF exemption is `unsubscribe/*`, which relies on a signed URL (`bootstrap/app.php`).
- Escape output with `{{ }}`. Use `{!! !!}` only for sanitised content.
- File uploads: validate type and size server-side. Documents use `DocumentStorage` with randomised names. Gallery images are re-encoded with GD and never stored as sent.
- Rate-limit public POST routes with `throttle:` middleware. Named limiters are in `AppServiceProvider::registerRateLimiters`.
- Never put secrets in code, tests, docs or fixtures. A tester password exists in git history (commit `87df882`) and must not be reused.
- Do not put strings that look like web shells or malware into test fixtures. Windows Defender flagged one (fixed in commit `9f3bdee`). `scripts/security-scan.ps1` includes a pattern check.

## Blade, CSS and JavaScript

- Use the components in `resources/views/components/ui/` before writing new markup.
- Use Tailwind classes and the design tokens in `resources/css/app.css` (`bg-uew-navy`, `text-uew-purple`, `border-uew-gold`). Do not add ad-hoc hex colours in markup.
- Make every view handle the empty state (`<x-ui.empty>`), and every form show field errors.
- Use Alpine.js for small interactions. Keep state on the server.
- Provide `alt` text, labels linked to inputs, visible focus rings, and 44 px touch targets. The Playwright accessibility spec (`tests/e2e/a11y.spec.ts`) checks WCAG rules with axe.

See [frontend-development](frontend-development.md).

## Tests

- New business logic needs a feature test in `tests/Feature/`.
- A new `Permission` or route gated by `can:` must be covered by `PrivilegesTest`. See [adding-new-features](adding-new-features.md).
- Test method names use `test_snake_case_sentence` (`test_every_state_changing_workspace_route_enforces_its_permission`).
- Do not weaken or delete a failing test to get a green build.

## Prose in the UI and in docs

British spelling in user-visible text and documentation (programme, organisation, colour). Code identifiers use `program`, `organization`.
