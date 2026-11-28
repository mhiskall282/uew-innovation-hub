# Project Handover

> **Audience:** Hub Director, outgoing and incoming maintainers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [maintenance-overview.md](maintenance-overview.md), [technical-debt-register.md](technical-debt-register.md), [security-maintenance.md](security-maintenance.md), [../09-deployment-and-operations/environment-configuration.md](../09-deployment-and-operations/environment-configuration.md)

Use this checklist when ownership of the Hub moves to a new person or team. Tick each item and record the date and the person. Never write a secret in this file.

## 1. Ownership of accounts and services

| Item | Where | New owner | Done |
| --- | --- | --- | --- |
| GitHub repository `mhiskall282/uew-innovation-hub` (admin rights, transfer to a UEW organisation if wanted) | GitHub | | ☐ |
| Render account, web service `uew-innovation-hub` and database `uew-innovation-hub-db` | Render dashboard | | ☐ |
| Render environment variables and secrets | Render dashboard | | ☐ |
| DNS and domain (live URL is https://uew-innovation-hub.onrender.com; a UEW domain is not recorded in the repository) | Domain registrar or UEW IT | | ☐ |
| SMTP provider and credentials (entered in Site settings, E-mail; `MAIL_MAILER=log` until set) | Provider and workspace | | ☐ |
| S3-compatible storage (R2 or S3) bucket and keys, for documents and gallery | Storage provider | | ☐ |
| Password manager vault holding the above | Team | | ☐ |
| Hub Director account (`super_admin`) and at least one backup administrator (`php artisan hub:create-admin`) | Workspace | | ☐ |

> **Requires confirmation:** who currently owns the DNS record, the SMTP account and the storage account. The repository does not say.

## 2. Owner-only open actions (from `.ai/state.md`)

| Action | How | Done |
| --- | --- | --- |
| Set a strong `SEED_PASSWORD` | Render environment | ☐ |
| Set `BLOCK_DEFAULT_PASSWORDS=true`, then run `php artisan hub:secure-demo-accounts` | Render environment and shell | ☐ |
| Enter real SMTP settings | Site settings, E-mail | ☐ |
| Set `GALLERY_DISK=s3` and `DOCUMENTS_DISK=s3` with the matching S3 variables (see `.env.example`) | Render environment | ☐ |
| Move to a paid database (or a host without expiry) | Render or other | ☐ |
| Set `SEARCH_INDEXING=true` when the site is ready to be indexed | Render environment | ☐ |
| Legal review of the Privacy and Terms pages | UEW legal office | ☐ |
| Optional: rewrite git history to remove the tester password in commit `87df882`. This needs the owner's approval, rewrites every commit hash, and everyone must re-clone. | Owner decision | ☐ |

> **Warning:** A history rewrite (force push) is destructive. Do it only with written owner approval and a backup of the repository. Changing the password matters more than the rewrite.

## 3. Knowledge transfer

- Walk through [maintenance-strategy.md](maintenance-strategy.md) and the [register](technical-debt-register.md).
- Show a deploy, a rollback ([../09-deployment-and-operations/rollback-procedures.md](../09-deployment-and-operations/rollback-procedures.md)) and a database restore ([database-maintenance.md](database-maintenance.md)).
- Confirm the new owner can run `php artisan test` locally and CI is green on `main`.
- Review open items TD-008, TD-014, TD-015 and TD-016.

## 4. Sign-off

| Name | Role | Date |
| --- | --- | --- |
| | Outgoing | |
| | Incoming | |
| | Hub Director | |
