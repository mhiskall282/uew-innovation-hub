# Deprecation Policy

> **Audience:** Developers, API maintainers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [versioning-strategy.md](versioning-strategy.md), [../06-api-reference/api-overview.md](../06-api-reference/api-overview.md), [technical-debt-register.md](technical-debt-register.md)

This page describes how the open data API retires a version, exactly as implemented, and how to deprecate features and code.

## API versions

The API has two versions (`config/api.php`): `v1` (status `supported`, contract frozen) and `v2` (status `current`). `GET /api` lists them. Breaking changes ship as a new version. Additive changes (new fields, endpoints) may land in an existing version.

## Retiring an API version

Source: the comment block in [`config/api.php`](../../config/api.php) and [`ApiVersion`](../../app/Http/Middleware/ApiVersion.php) (alias `api.version`).

| Step | Action | Effect |
| --- | --- | --- |
| 1 | Set `API_V1_DEPRECATED_AT` (date) in the environment. | Every v1 response carries `Deprecation` (RFC 9745) and `Link: <docs_url>; rel="deprecation"`. |
| 2 | Set `API_V1_SUNSET_AT` at least **six months** after the deprecation date. | v1 responses also carry `Sunset` (RFC 8594). |
| 3 | After the sunset date, remove the version's routes (`routes/api/v1.php`, `app/Http/Controllers/Api/V1`, `app/Http/Resources/Api/V1`) in a release. | Requests return the standard 404 error. |

The same variables exist for v2: `API_V2_DEPRECATED_AT`, `API_V2_SUNSET_AT`. Every API response also has an `API-Version` header. Variables are listed in [../09-deployment-and-operations/environment-configuration.md](../09-deployment-and-operations/environment-configuration.md).

Before step 1, a new version must exist and be documented, and the tests for both versions (`tests/Feature/Api/PublicApiTest.php`, `ApiV2Test.php`) must pass. Announce the change in the docs and by e-mail or notice to known consumers. Known consumers are not tracked in the repository.

> **Requires confirmation:** `config/api.php` does not change the `status` text automatically. When you set a deprecation date, also edit `status` to `deprecated` in the config so `/api` shows it.

## Deprecating a feature (web)

1. Record it in the [technical debt register](technical-debt-register.md) with an ID.
2. Mark it in the UI or docs ("Will be removed after <date>") at least one release before removal.
3. For members' data, check [../07-database/data-retention.md](../07-database/data-retention.md) first. Do not drop columns that hold records without a migration that keeps or exports the data.
4. Remove the code, routes, views, tests and docs in one commit. Add a migration only if the schema changes.
5. Note it in `.ai/changelog.md` and `.ai/state.md`.

## Deprecating code

- Keep a deprecated class for one release with a `@deprecated` docblock that names the replacement.
- Remove it in the next release once nothing calls it (`grep` the repository).
- Never edit an existing migration that has run in production. Add a new one.

## Recommended, not implemented

- A deprecation notice page for API consumers.
- A tracked list of API consumers and their contacts.
