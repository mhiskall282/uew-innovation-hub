# Security Maintenance

> **Audience:** Maintainers, Hub Director · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [../08-security/security-overview.md](../08-security/security-overview.md), [../08-security/secrets-management.md](../08-security/secrets-management.md), [dependency-updates.md](dependency-updates.md), [technical-debt-register.md](technical-debt-register.md)

This page lists recurring security tasks. Design details are in section 08.

## Automated checks (CI)

| Check | Where |
| --- | --- |
| `composer audit --locked --no-interaction` | Dependency audit job, `.github/workflows/ci.yml` |
| `npm audit --omit=dev --audit-level=high` | same job |
| Repository security scan (added in commit `9f3bdee`) | Look in `tests/` and CI for the scan |
| Tests for IDOR, open redirect and privacy | `EngagementTest`, route smoke tests |

> **Requires confirmation:** the exact location of the repository security scan introduced in `9f3bdee`.

## Recurring tasks

| Frequency | Task |
| --- | --- |
| Weekly | Look at the audit log (workspace) for failed or unusual sign-ins and exports. Exports are audited: `member.exported`, `report.projects_exported`, `application.exported`, `report.exported`. |
| Monthly | Apply security patches ([dependency-updates.md](dependency-updates.md)). |
| Quarterly | Review staff accounts and roles. Disable accounts of people who left. Rotate SMTP and storage keys if anyone with access left. |
| After any staff change | Check no shared password is in use. |
| Yearly | Review Privacy and Terms pages with legal advice. |

## Account hygiene

- Set a strong `SEED_PASSWORD` and `BLOCK_DEFAULT_PASSWORDS=true` in production (`config/hub.php`, `.env.example`). With the block on, accounts that still use the default password are refused.
- Run `php artisan hub:secure-demo-accounts --dry-run` to see affected accounts, then without `--dry-run` to secure them. Create real administrators with `php artisan hub:create-admin`.
- There is no forced password change (TD-008).
- A tester password exists in commit `87df882` (TD-015). Confirm it was changed everywhere.

## Secrets

- Never commit `.env`. It is in `.gitignore`.
- Store production secrets in the Render dashboard. `render.yaml` should reference them, not hold them.
- If a secret leaks, change it first, then clean up history only with the owner's approval.

## Headers and runtime

The Apache image hides version details (`ServerTokens Prod`, `ServerSignature Off` in the `Dockerfile`). Behind Render, set `TRUSTED_PROXIES` so HTTPS and client IPs are right (see TD-004 for the `env()` caveat).

## Gaps

See TD-008, TD-009, TD-014, TD-015 and TD-016 in the [register](technical-debt-register.md).

## Recommended, not implemented

- Dependabot alerts and security updates.
- Static analysis in CI.
- Alerting on repeated failed sign-ins.
