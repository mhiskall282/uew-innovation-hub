# Staging Deployment

> **Audience:** Operators, developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Production deployment](production-deployment.md), [Environment configuration](environment-configuration.md), [Test data management](../10-testing-and-quality/test-data-management.md)

**No separate staging environment exists.** The only hosted instance is https://uew-innovation-hub.onrender.com. It is configured as a test and demo site, not as a production site. This page explains that state and recommends how to create a real staging service.

## Current state: the live site is a test/demo site

Evidence in [`render.yaml`](../../render.yaml):

| Setting | Value | Meaning |
| --- | --- | --- |
| `SEED_DEMO_DATA` | `"true"` | First boot creates demo accounts and sample content ("TEST SITE" comment in the file) |
| `SEED_PASSWORD` | `sync: false` | Shared password for demo accounts, supplied in the Render dashboard |
| `MAIL_MAILER` | `log` | No real e-mail is sent until SMTP is configured in **Site settings > E-mail delivery** |
| `DOCUMENTS_DISK` | `private` | Uploaded files vanish on redeploy |
| `plan` (web and database) | `free` | Sleeps after 15 minutes idle; Postgres expires after 30 days |
| `SEARCH_INDEXING` | not set, so `false` | Site serves `Disallow: /` in `robots.txt` |
| `autoDeploy` | `true` | Every push to the tracked branch (`main`) deploys |

Testers work against real code from `main`. A bad commit therefore reaches the only hosted site. Do not put real member data on this instance. Demo accounts are listed in `database/seeders/DemoContentSeeder.php`; sign-in uses the `SEED_PASSWORD` value.

## Recommended: create a staging service from `render.yaml`

Recommended, not implemented. The goal is a second, disposable copy that mirrors production settings but sends no real mail.

1. In Render choose **New > Blueprint** and select the repository again. Render creates a second web service and database from `render.yaml`. The service and database names in the file (`uew-innovation-hub`, `uew-innovation-hub-db`) are already used, so rename them in a staging branch or a copy of the file (for example `uew-innovation-hub-staging` and `uew-innovation-hub-staging-db`). Render requires unique names per account.
2. Point the staging service at a long-lived branch such as `staging` (Render **Settings > Branch**), so production follows `main` only.
3. Set these environment variables on the staging service:

| Variable | Staging value | Reason |
| --- | --- | --- |
| `APP_URL` | the staging URL | Links in e-mail |
| `APP_ENV` | `staging` | `MAIL_ALWAYS_TO` is ignored when `APP_ENV=production` (`AppServiceProvider`) |
| `APP_DEBUG` | `false` | Never show stack traces |
| `MAIL_MAILER` | `smtp` | Test real delivery |
| `MAIL_ALWAYS_TO` | one test inbox | All mail goes there, never to real recipients |
| `MAIL_ASSET_URL` | the staging URL | Images in e-mail |
| `SEED_DEMO_DATA` | `true` | Realistic content |
| `SEED_PASSWORD` | strong, 12+ characters, mixed case, a number | Required for the production-mode seeder |
| `BLOCK_DEFAULT_PASSWORDS` | `true` | Match production behaviour |
| `SEARCH_INDEXING` | `false` | Keep staging out of search engines |
| `DOCUMENTS_DISK`, `GALLERY_DISK` | `s3` with a separate bucket or prefix | Test the storage path production will use |
| `DB_URL` | staging database | Never share the production database |

   With `APP_ENV=staging` the app is not "production": `URL::forceHttps()` is skipped, strong-password rules drop to 8 characters, and lazy-loading violations throw. This is a trade-off. If you must test exact production behaviour, keep `APP_ENV=production` and instead leave `MAIL_MAILER=log`.
4. Add `RUN_QUEUE_WORKER=true` (default) so queued mail is processed.
5. Deploy and open `/up` to confirm the health check passes.

## Promoting changes

Recommended workflow (not enforced by tooling): merge a pull request into `staging`, wait for CI, test on the staging service, then merge `staging` into `main`. See [Git workflow](../05-developer-guide/git-workflow.md) and [Branching strategy](../05-developer-guide/branching-strategy.md) for the repository's branch rules.

## Resetting staging

> **Warning:** Dropping the staging database deletes all staging data. Confirm you are in the staging service before you do it.

Create a new empty database (or reset the existing one in the Render dashboard) and redeploy. The entrypoint runs migrations and seeders. Demo seeding does not re-run if `admin@uew.edu.gh` already exists (`DatabaseSeeder`), so a fresh database is the only way to re-seed.

## Limits of the free plan on staging

The same limits as production apply: sleep after 15 minutes, 30-day Postgres expiry, no persistent disk. See [Production deployment](production-deployment.md#render-free-plan-limits).
