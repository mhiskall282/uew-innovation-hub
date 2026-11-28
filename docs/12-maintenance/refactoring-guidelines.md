# Refactoring Guidelines

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [technical-debt-register.md](technical-debt-register.md), [../05-developer-guide/coding-standards.md](../05-developer-guide/coding-standards.md), [../05-developer-guide/modifying-existing-features.md](../05-developer-guide/modifying-existing-features.md), [deprecation-policy.md](deprecation-policy.md)

Refactor to remove a named problem in the [register](technical-debt-register.md), not for taste. The project rules (`AGENTS.md`, `.ai/rules/`) ask for thin controllers, FormRequests, policies, service classes, jobs and no raw SQL.

## Rules

1. **Tests first.** Make sure the area has tests. Add a characterisation test if it does not. The suite is `php artisan test` (194 tests).
2. **One kind of change per commit.** Do not mix a refactor with a feature or a dependency update.
3. **Keep behaviour.** Responses, routes, route names, audit actions and API contracts stay the same. API v1 is frozen; changes to its output are breaking.
4. **Keep the architecture.** Logic belongs in `app/Services`, authorisation in policies and `Permission` enum checks, validation in `app/Http/Requests`, data shape for API in `app/Http/Resources/Api/V*`.
5. **Style.** Run `vendor/bin/pint` before committing. CI runs `pint --test`.
6. **Database.** Never edit old migrations. Add new ones ([database-maintenance.md](database-maintenance.md)).
7. **Audit.** If you move code that writes an audit entry, keep the action name.

## Good candidates now

| Candidate | Register ID |
| --- | --- |
| Move `TRUSTED_PROXIES` into config | TD-004 |
| Allowed-transition map for application status | TD-010 |
| Remove empty view folders and `.user.ini` | TD-001, TD-002 |
| Rename Composer package | TD-003 |

## Safe workflow

```powershell
git switch -c refactor/<topic>
php artisan test
# make the change
vendor/bin/pint
php artisan test
npm run build   # if views or assets changed
```

Open a pull request when branch protection exists (TD-014). Wait for all CI jobs before merging.

## Do not

- Remove or rename an enum value stored as a string in the database without a data migration (enums are stored as strings).
- Remove a route without checking the route smoke test and the docs (`php artisan route:list` is generated).
- Delete the `legacy/` folder. It is kept for reference (see `AGENTS.md`).
