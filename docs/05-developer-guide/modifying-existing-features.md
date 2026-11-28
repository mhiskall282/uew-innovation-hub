# Modifying Existing Features

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [adding-new-features](adding-new-features.md), [development-workflow](development-workflow.md), [testing-guide](testing-guide.md), `../12-maintenance/refactoring-guidelines.md`

A safe procedure for changing something that already works, and the places where a change in one file has side effects somewhere else.

## Procedure

1. **Find everything the feature touches.** Start from the route name:

```powershell
php artisan route:list --name=workspace.albums
Get-ChildItem app, routes, resources, tests -Recurse -Include *.php | Select-String -Pattern 'albums' -SimpleMatch
```

```bash
php artisan route:list --name=workspace.albums
grep -rn "albums" app routes resources tests --include='*.php'
```

2. **Read the existing test.** If none covers the behaviour you change, write one first and watch it pass on the current code (a characterisation test).
3. **Change the smallest thing.** Keep unrelated clean-up for another commit.
4. **Run the focused test, then the full suite.** `php artisan test --filter <Class>`, then `php artisan test`.
5. **Run Pint and the asset build** ([development-workflow](development-workflow.md)).
6. **Check production impact** (below), then update docs.

## Knock-on effects to check

| You change | Also check |
| --- | --- |
| A `Permission` case or `Role::permissions()` | Both `match` blocks in `Permission` (`label()`, `group()`), `PrivilegesTest`, `AccessControlTest`, `UserManagementTest`, the Roles page, `../04-architecture/authorization-architecture.md`, `../02-user-guide/user-roles-and-permissions.md`. Who gains or loses access takes effect on deploy |
| A route name | Every `route('...')` call in views and controllers, e-mail templates, `RouteSmokeTest`, Playwright specs, `docs/api.md` for API routes |
| A route URI that is public | Links in already-sent e-mails (verification, unsubscribe, password reset) and bookmarks. Add a redirect. `routes/web.php` already redirects old workspace sign-in URLs |
| An enum case value (for example `ProjectStatus::Active = 'active'`) | Stored rows hold the old value. Add a data migration. Also Tailwind class lists in `app/Enums/*.php`, API resources, filters, seeders |
| A model's `$fillable` or casts | Factories, seeders, FormRequests, services that call `fill()`, API resources |
| A table or column | Migration (additive), model, factory, seeders, `../07-database/tables-and-columns.md`, API resources, exports in `ReportExportService`, `legacy_id` import logic |
| A service signature | All callers (`Select-String -Pattern 'GalleryService'`), controllers, commands, tests |
| An e-mail template key | `App\Support\EmailTemplates`, the templates editor, `TemplatedMail` callers, tests that assert `templateKey` |
| A config key in `config/hub.php` | `.env.example`, `../09-deployment-and-operations/environment-configuration.md`, `render.yaml`, tests that set config |
| A validation rule | The matching test, the form view's `maxlength`/hints, API docs if exposed, `docker/php.ini` upload limits for file rules |
| A layout or component in `components/ui/` | Every page that uses it. Run `npm run test:e2e:chromium` (axe and overflow checks) |
| Security headers (`SecurityHeaders`) | `SecurityHeadersTest`, Playwright console watcher (`watchConsole` fails on CSP errors) |
| Cached values (`MetricsService`, `SettingsService`) | Cache invalidation (`FlushesMetrics`, settings cache key) and the `METRICS_CACHE_TTL` setting |

## Changing behaviour that has stored data

- **Never rewrite history in a migration without a backup.** Add a migration that converts old values, make it reversible if possible, and test it on a copy of realistic data. Production migrates on deploy ([database-development](database-development.md)).
- **Soft-deleted models** (`Project`, `Publication`, `Post`, `Program`, `GrantApplication`): queries exclude trashed rows by default. `Album::program()` uses `withTrashed()` on purpose. Decide deliberately for new relations.
- **Legacy files**: rows may store only a filename. `DocumentStorage` resolves those against the folder. Keep that fallback when you change storage code.
- **Audit log**: keep action names stable (`album.created`). The audit view and any reports filter on them.

## Changing the UI

- Keep the empty, error and success states.
- Check 320 px, 768 px and 1280 px wide. `tests/e2e/find-overflow.mjs` finds overflow.
- Keep labels, alt text and focus styles. Run the accessibility spec.
- Change tokens in `resources/css/app.css`, not in individual views.

## Removing a feature

1. Remove routes, controller, views, policy, service, tests and navigation entries.
2. Keep the table until a later release. Add a migration to drop it only after confirming the data is no longer needed, and after a backup.
3. Remove permissions only when no role or route uses them (search for the value string).
4. Update docs and `.ai/changelog.md`.

## Hot spots (read before touching)

| File | Why |
| --- | --- |
| `app/Enums/Role.php`, `Permission.php` | The only definition of access |
| `app/Providers/AppServiceProvider.php` | Gates, rate limits, password rules, lazy-loading guard |
| `bootstrap/app.php` | Middleware aliases, trusted proxies, error envelope |
| `app/Http/Middleware/SecurityHeaders.php` | CSP applies to every page |
| `routes/web.php` | Order and grouping decide middleware |
| `docker/entrypoint.sh` | Runs on every production boot: migrations, seeders, commands, queue worker |
| `app/Services/DocumentStorage.php`, `GalleryService.php` | File handling and security |
