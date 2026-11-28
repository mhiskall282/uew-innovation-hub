# Quality Assurance Checklist

> **Audience:** Maintainers, release managers, QA · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [testing-strategy](testing-strategy.md), [security-testing](security-testing.md), [release management](../12-maintenance/release-management.md), [rollback procedures](../09-deployment-and-operations/rollback-procedures.md), [health checks](../09-deployment-and-operations/health-checks.md), [testing guide (commands)](../05-developer-guide/testing-guide.md)

Use this checklist before merging to `main` for a release. Render deploys `main` automatically, so a merge is a release. Every command exists in `composer.json`, `package.json`, `.github/workflows/ci.yml` or `scripts/`. Steps 1 to 8 are automated and also run in CI (except where noted). Steps 9 to 13 are manual.

Copy the list into the pull request or release note and tick it.

## Before you start

- [ ] Working tree is clean (`git status`) and you are on the commit you intend to release.
- [ ] `DB_DATABASE`, `DB_URL` and `DB_CONNECTION` are not set in your shell, so tests do not hit a real database. See the warning in the [testing guide](../05-developer-guide/testing-guide.md).

```powershell
Get-ChildItem Env:DB_*
Remove-Item Env:DB_DATABASE, Env:DB_URL, Env:DB_CONNECTION -ErrorAction SilentlyContinue
```

```bash
env | grep '^DB_'
unset DB_DATABASE DB_URL DB_CONNECTION
```

## Automated checks

| # | Check | Command | Pass condition | In CI |
| --- | --- | --- | --- | --- |
| 1 | Code style | `vendor/bin/pint --test` | Exit code 0, no files listed | Job `lint` |
| 2 | PHPUnit on SQLite | `php vendor/bin/phpunit --no-progress` | All tests pass (194 tests, 1461 assertions at commit `d1296b1`). A different count is fine if you added tests. A lower count needs an explanation | Job `tests` (sqlite) |
| 3 | PHPUnit on PostgreSQL | Same command with `DB_CONNECTION=pgsql`, `DB_URL`, `DB_DATABASE` set to a **scratch** PostgreSQL 16 database | All tests pass | Job `tests` (pgsql) |
| 4 | Front-end build | `npm run build` | Exit code 0, `public/build/manifest.json` exists | Job `assets` |
| 5 | Blade templates compile | `php artisan view:cache` then `php artisan view:clear` | No error | Job `tests` |
| 6 | Browser tests | `npm run test:e2e` (all five projects) or at least `npm run test:e2e:chromium` | All pass, no test passes only on retry (check the report) | Job `e2e` |
| 7 | PHP dependency audit | `composer audit --locked` | No advisories | Job `security` |
| 8 | npm dependency audit | `npm audit --omit=dev --audit-level=high` (CI) and `npm audit` (stricter, local) | No high or critical issue in production dependencies. Review moderate and development-only findings | Job `security` (production, high) |

```powershell
vendor/bin/pint --test
php vendor/bin/phpunit --no-progress
npm ci
npm run build
php artisan view:cache; php artisan view:clear
npm run test:e2e
composer audit --locked
npm audit --omit=dev --audit-level=high
```

```bash
vendor/bin/pint --test
php vendor/bin/phpunit --no-progress
npm ci && npm run build
php artisan view:cache && php artisan view:clear
npm run test:e2e
composer audit --locked
npm audit --omit=dev --audit-level=high
```

If Pint reports problems, run `vendor/bin/pint` (it rewrites files), review the diff, and commit the change separately.

### Migrations (when the release adds or changes a migration)

CI runs this on PostgreSQL: `migrate`, `migrate:rollback --step=1`, `migrate`, `db:seed`. Reproduce it before merging, on a scratch database only.

> **Warning:** `migrate:rollback` removes the last migration batch's tables or columns and their data. Never run it against the live database or a database you want to keep. Use `DB_CONNECTION=pgsql` with a scratch database, or a copy of `database/database.sqlite`.

```powershell
php artisan migrate --force
php artisan migrate:rollback --step=1 --force
php artisan migrate --force
php artisan db:seed --force
```

```bash
php artisan migrate --force
php artisan migrate:rollback --step=1 --force
php artisan migrate --force
php artisan db:seed --force
```

- [ ] Every new migration has a working `down()` method, or the release note says it cannot be reversed.
- [ ] A migration that changes existing data was tested on a copy of realistic data and a backup of the live database exists first ([backup and recovery](../09-deployment-and-operations/backup-and-recovery.md)).
- [ ] New columns used by the API or exports are covered by a test.

### Security scan (not in CI)

- [ ] `powershell -ExecutionPolicy Bypass -File scripts/security-scan.ps1` and read every section. Expected findings: none, except the known tester password in commit `87df882` ([security-testing](security-testing.md)).
- [ ] No new file under `tests/`, `app/` or `database/` contains a real password, token or key.

### Docker image (CI job `docker`)

- [ ] `docker build .` succeeds locally, or the `docker` job is green. Changes to `Dockerfile`, `docker/php.ini`, `docker/entrypoint.sh` or `render.yaml` get extra attention because they only run on Render.

## Manual checks

Do these on a local copy seeded with demo data (`php artisan migrate:fresh --seed` on a scratch SQLite file, then `php artisan serve`) or on a staging deployment. The demo account list is in `database/seeders/DemoContentSeeder.php`; the password is `SEED_PASSWORD`.

### 9. Manual check per role

Sign in as each role, and confirm the allowed pages work and the others return 403 (`AccessControlTest` automates the page list, but not the look and feel).

| Role | Open and use | Must not see or reach |
| --- | --- | --- |
| Hub Director (`super_admin`) | Dashboard, reports and PDF export, programmes, applications, members, broadcasts, projects, partners, publications, grants, posts, pages, enquiries, users, roles page, settings, e-mail templates, audit log, gallery | Nothing is refused on a `workspace.*` page |
| Programme Officer (`officer`) | Reports, programmes (create and publish), applications, members, broadcasts, new project, partners, publications, grants, posts, pages, enquiries, gallery | Staff accounts, roles, settings, e-mail templates, audit log, archiving a project (Hub Director only) |
| Reviewer (`reviewer`) | Reports, applications (decide, message), project export, grants (decide) | Programmes, members, users, settings, posts. Gender and disability are not shown on applications |
| Communications Intern (`intern`) | Posts (drafts only, cannot publish), gallery (upload, cannot publish or delete) | Everything else, including publications and members |
| Read-only Staff (`viewer`) | Dashboard, projects, partners, own account (read only) | Any create or edit button; member export (403); gallery (403) |
| Member (`member`) | `/portal`, applications (draft, submit, message), progress reports (when accepted), profile, notifications, community opt-in | `/workspace` (403), other members' applications and reports |
| Guest | Public website pages, sign-up, sign-in, contact form | `/portal`, `/workspace`, documents of internal projects, proposals |

- [ ] Sidebar for each role lists only pages the role can open.
- [ ] A deactivated account is signed out on its next request.
- [ ] The last active Hub Director cannot be demoted or deleted.
- [ ] Sign-out works from the portal and the workspace.

### 10. Key workflows

- [ ] Sign up a new member, verify the e-mail (check the mail log or `MAIL_MAILER=log` output), finish onboarding.
- [ ] Apply to a programme, save a draft, submit. A reviewer sees it, decides, and the member receives a notification.
- [ ] Accepted member submits a progress report. Staff request changes. Member resubmits.
- [ ] Officer creates a programme with a form, publishes, and it appears on the public site and in the sitemap (when indexing is on).
- [ ] Upload a photo to an album. Publish it. It appears on the public gallery. A draft album is hidden.
- [ ] Send a broadcast to a test audience. Check the unsubscribe link works and a failed send shows "Failed".
- [ ] Export members, projects and an application list (CSV) and a report (PDF). Confirm entries in the audit log.
- [ ] Contact form submits and appears in enquiries.

### 11. Front end

- [ ] Home, a programme page, the apply form and the workspace dashboard at 320, 768 and 1440 px: no sideways scroll, no clipped text.
- [ ] Keyboard: skip link, visible focus, forms can be completed without a mouse.
- [ ] No console errors or CSP violations in the browser developer tools on the pages you changed.

### 12. API v1 unchanged

v1 is frozen. Its contract is: envelope `{success, data, meta, links}`, page-number pagination, errors `{success:false, message, errors}`. The only additive change is the weak ETag with 304.

- [ ] `tests/Feature/Api/PublicApiTest.php` passes without edits to its assertions. A diff that changes it needs an explicit reason in the release note.
- [ ] Compare the responses of the changed commit with the previous release for each v1 URL (same demo data). Any difference in keys or types is a breaking change:

```powershell
php artisan serve
$base = 'http://127.0.0.1:8000/api/v1'
'stats','projects','publications','news','partners','programmes','community' | ForEach-Object {
    Invoke-RestMethod "$base/$_" | ConvertTo-Json -Depth 10 | Out-File "v1-$_.json"
}
# repeat on the previous commit, then: git diff --no-index old\v1-stats.json new\v1-stats.json
```

```bash
for e in stats projects publications news partners programmes community; do
  curl -s "http://127.0.0.1:8000/api/v1/$e" | python -m json.tool > "v1-$e.json"
done
```

- [ ] v1 still sends `API-Version: v1`. If `API_V1_DEPRECATED_AT` or `API_V1_SUNSET_AT` are set, the `Deprecation`, `Sunset` and `Link` headers are present, and v2 does not send them.
- [ ] v2 changes: errors use `{error:{status,code,message,details}}`, lists use cursor pagination, ids are strings.

(Recommended, not implemented: automated v1 contract tests. See [api-testing](api-testing.md).)

### 13. Configuration and release notes

- [ ] New environment variables are in `.env.example` and in [environment configuration](../09-deployment-and-operations/environment-configuration.md), and set in the Render dashboard before deploy.
- [ ] Production settings: `APP_DEBUG=false`, `SEARCH_INDEXING` as intended, `BLOCK_DEFAULT_PASSWORDS=true` on a hosted site, `SEED_PASSWORD` strong.
- [ ] The release note says what changed, whether a migration runs, and how to roll back ([rollback procedures](../09-deployment-and-operations/rollback-procedures.md)).

## After deploy

| # | Check | How | Pass condition |
| --- | --- | --- | --- |
| 14 | Deploy finished | Render dashboard shows the new commit as live | Status "Live" |
| 15 | Health check | `GET /up` on the live URL | HTTP 200 |
| 16 | Home page and sign-in | Open in a browser | Pages load with styles and images |
| 17 | Version list | `GET /api` | Lists v1 and v2, `current` is `v2` |
| 18 | One API read | `GET /api/v2/projects?limit=1` | HTTP 200, `ETag` header present |
| 19 | Sign in as a staff account you own | Browser | Workspace loads. Do not use or publish demo credentials on the live site |
| 20 | Logs | Render log view | No new errors after boot. The entrypoint runs migrations and the queue worker; check both started |

```powershell
Invoke-WebRequest https://uew-innovation-hub.onrender.com/up -UseBasicParsing | Select-Object StatusCode
Invoke-RestMethod https://uew-innovation-hub.onrender.com/api | ConvertTo-Json -Depth 5
```

```bash
curl -s -o /dev/null -w '%{http_code}\n' https://uew-innovation-hub.onrender.com/up
curl -s https://uew-innovation-hub.onrender.com/api
```

The live site is on the Render free plan. The first request after idle time can take a long time or time out. Retry once before treating it as a failure.

If a check fails, roll back first and investigate second ([rollback procedures](../09-deployment-and-operations/rollback-procedures.md)).

## Known limits of this checklist

- CI does not run `scripts/security-scan.ps1`, the migration check on SQLite, or any manual step.
- Nothing in the repository enforces the checklist. Branch protection and required checks are not visible in the repository files. **Requires confirmation:** GitHub settings for `main`.
- The e2e suite does not cover most staff write actions, so step 10 matters.
- No coverage report or static analysis exists. Recommended, not implemented.
