# Maintenance Strategy

> **Audience:** Maintainers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [maintenance-overview.md](maintenance-overview.md), [technical-debt-register.md](technical-debt-register.md), [security-maintenance.md](security-maintenance.md), [../09-deployment-and-operations/health-checks.md](../09-deployment-and-operations/health-checks.md)

This page sets the routine. Because the application has no scheduler (TD-005), every task below is manual unless it says CI does it.

## Principles

1. Fix security and data-loss risks first, then correctness, then speed, then style.
2. Keep changes small and deploy them one at a time, so a rollback is simple.
3. Every fix gets a test. The suite runs in CI on SQLite and PostgreSQL.
4. Record decisions in `.ai/decisions.md` and progress in `.ai/state.md` and `.ai/changelog.md`.
5. Track debt in the [register](technical-debt-register.md), not in code comments.

## Calendar

| Frequency | Task | Owner | Reference |
| --- | --- | --- | --- |
| Daily (while launching) | Open `/up` and the home page. Look at Render logs for errors. | Maintainer | [../11-troubleshooting/logging-and-diagnostics.md](../11-troubleshooting/logging-and-diagnostics.md) |
| Weekly | Check CI on `main`. Check failed jobs and stuck broadcasts (status `failed`). Review audit log for odd sign-ins. | Maintainer | [security-maintenance.md](security-maintenance.md) |
| Monthly | `composer outdated --direct`, `npm outdated`, apply patch updates. Test a database restore. | Maintainer | [dependency-updates.md](dependency-updates.md), [database-maintenance.md](database-maintenance.md) |
| Quarterly | Review the debt register. Review staff accounts and roles. Rotate `SEED_PASSWORD`-style shared secrets and SMTP credentials if people left. | Maintainer, Hub Director | [security-maintenance.md](security-maintenance.md) |
| Yearly | Plan the Laravel major upgrade. Review PHP and Node support dates. Review Privacy and Terms pages. | Maintainer, Hub Director | [long-term-maintenance-roadmap.md](long-term-maintenance-roadmap.md) |
| On demand | Staff leave: disable their account. Incident: follow the security incident response page. | Hub Director | [../08-security/security-incident-response.md](../08-security/security-incident-response.md) |

## Change types

| Type | Process |
| --- | --- |
| Hotfix | Fix, test, push to `main`, verify `/up`. Add a register row if it reveals a gap. |
| Routine update | Branch or `main`, run the update procedure, wait for CI. |
| Schema change | Follow [database-maintenance.md](database-maintenance.md). Back up first. |
| Content change | Staff use the workspace (pages, posts, templates). No deploy needed. |

## Recommended, not implemented

- Uptime monitoring on `/up` with e-mail alerts.
- A scheduler and a Render cron job for pruning (see [database-maintenance.md](database-maintenance.md)).
- An error tracker (Sentry or similar). Logs go to the `single` channel (`LOG_STACK=single` in `.env.example`).
