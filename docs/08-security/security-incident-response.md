# Security Incident Response

> **Audience:** Hub Director, operators, developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [Security monitoring](security-monitoring.md), [Secrets management](secrets-management.md), [Rollback procedures](../09-deployment-and-operations/rollback-procedures.md), [Admin troubleshooting](../03-administration/admin-troubleshooting.md), [Data protection](data-protection.md)

A step-by-step plan for suspected security incidents. It uses only tools that exist: the workspace, the Render dashboard, the `activity_logs` table and `php artisan`. There is no on-call rota, SIEM or automatic alert in the system, so detection depends on people reading logs.

> **Requires confirmation:** who is the Hub's incident owner, who may speak to the Data Protection Commission and affected members, and the notification deadline the Hub's policy sets. Fill these in at the bottom of this page.

## Severity guide

| Level | Examples | Target response |
| --- | --- | --- |
| High | Suspected staff or Hub Director account takeover; leaked `APP_KEY`, DB or S3 credentials; personal data exposed; web shell found | Start containment at once |
| Medium | Spike of failed sign-ins, unexpected role change, suspicious uploads, one member's account abused | Same day |
| Low | Single failed attack, scanner noise, 429 responses | Note and monitor |

## 1. Detect and record

1. Write down the time, who reported it, and what was seen.
2. Review the audit log at `/workspace/audit` (Hub Director). Search `auth.login_failed`, `auth.default_password_refused`, `auth.code_failed`, `user.`, `settings.updated`.
3. Check Render **Logs** for errors, 429 floods and unusual paths.
4. Do not delete logs or rows. Copy relevant audit rows (time, actor, IP, action) into a private incident note.

## 2. Contain

| Situation | Action | Effect |
| --- | --- | --- |
| One staff account is suspected | Workspace > Users > deactivate (`user.deactivated`) | Account cannot sign in. Stored sessions and remember token are removed. The user is signed out on the next request (`UserService::setActive`, `EnsureAccountIsActive`) |
| A member account is abused | Workspace > Members > toggle | Same effect for members |
| Password may be known | Hub Director sends a reset (`workspace.users.password-reset`); do not set a password by e-mail or chat | Resets revoke other sessions and send a notice |
| Hub Director account suspected | Use another Hub Director to deactivate it. The last active Hub Director cannot be deactivated or demoted, and `hub:create-admin` does nothing while an active Hub Director exists. With only one Hub Director, first create a second one in Workspace > Users (from a session you trust), then deactivate the first. If you cannot trust any session, change the account's password through the reset flow, rotate `APP_KEY` to end all sessions, and as a last resort edit the `users` table directly in the Render database shell | Prevents lock-out |
| Credential stuffing on sign-in | Lockouts are automatic (5 per e-mail+IP, 20 per account, 15 minutes). Set `BLOCK_DEFAULT_PASSWORDS=true` if default passwords may exist | Rate limits reset only when the window ends |
| Site defaced or content altered | Unpublish or revert content in the workspace, then use the audit diff to see who changed it | |
| Active attack on the host | Suspend the Render service from the dashboard | Whole site is offline |
| Malicious upload suspected | Remove the record in the workspace. Check `storage/app` on the documents disk or the S3 bucket for files that are not PDFs or JPEGs | |

> **Warning:** suspending the service stops the queue worker as well; queued mail is delayed until it restarts.

## 3. Rotate secrets

Order matters. Steps in [secrets management](secrets-management.md#rotation-procedures). Effects:

### Rotating `APP_KEY`

1. Generate: `php artisan key:generate --show` (same command in PowerShell and bash). Enter the value in Render and redeploy. The entrypoint adds the `base64:` prefix if missing.
2. Effects, all verified from code:
   - **Sessions:** the session cookie is encrypted with `APP_KEY`, so every browser session becomes invalid. Everyone signs in again. Remember-me cookies also stop working.
   - **Signed links:** `URL::temporarySignedRoute` and `signedRoute` use the key. All e-mailed verification, reset and unsubscribe links stop working (HTTP 403, `auth.link-expired`). Members must request new codes.
   - **Encrypted settings:** `settings.mail_password` can no longer be decrypted. `MailSettings::password()` returns `null` and SMTP login fails. The Hub Director must re-enter it in Site settings > E-mail.
   - **Database sessions and codes:** `verification_codes` use bcrypt and are unaffected, but outstanding codes are 10 minutes old at most.
3. Rotate the key only if it may have leaked or an attacker had server access. It does not by itself revoke access the attacker already has to accounts.

### Rotating SMTP credentials

- If SMTP is set in the workspace: Site settings > E-mail, enter the new password, save. The app calls `queue:restart`. If set in the environment, change `MAIL_PASSWORD` in Render and redeploy. Revoke the old credential at the provider (for example, the Gmail app password) afterwards.

### Rotating S3 / R2 credentials in Render

1. Create a new access key in the provider.
2. Set `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` in Render, redeploy, test a document download and a gallery photo.
3. Delete the old key at the provider.

### Other

- Database password: rotate in Render's database settings, then redeploy.
- `SEED_PASSWORD` and `ADMIN_PASSWORD`: change in Render, then change real accounts' passwords in the app.
- The tester password in commit `87df882` is public: confirm no account uses it.

## 4. Investigate

1. Build a timeline from `activity_logs`: actor, IP, action, subject. Use the field-level `properties` diff to see exactly what changed.
2. Check `users.last_login_at` and `last_login_ip` for the suspected accounts, and the `sessions` table (`user_id`, `ip_address`, `last_activity`).
3. Check `verification_codes` for repeated `attempts` against one user.
4. Check `contact_messages` and `application_messages` for abuse content.
5. Run `powershell -File scripts/security-scan.ps1` on a clean checkout of `main` to look for web-shell patterns, executable files under `public/` and `storage/`, and secrets.
6. Compare the deployed commit with GitHub (`git log`) to detect unreviewed changes.

Limits: the audit log does not record reads or API calls (CSV and PDF exports are recorded since commit `d1296b1`), and a database administrator can edit it. If the database was compromised, treat the log as unreliable.

## 5. Recover

1. Fix the cause (patch, configuration, removed account). Rebuild from a clean commit; do not repair a suspected host in place (Render rebuilds the image on deploy).
2. If data was altered, restore from a database backup. **Requires confirmation:** a restore procedure exists in the repository only as guidance in [backup and recovery](../09-deployment-and-operations/backup-and-recovery.md); no backup job is configured in code.
3. Re-enable accounts only after a password reset.
4. Ask members to reset passwords if their accounts may be affected. A broadcast reaches only opted-in members; use a direct message or the Privacy contact for the rest.

## 6. Notify

Personal data exposure may require notice to the Data Protection Commission and to affected people under Act 843. Decisions belong to the Hub's data protection contact. Provide them with: what was exposed, how many records, the time window from the audit log, and what was done.

## 7. After the incident

- Add the cause and fix to the [technical debt register](../12-maintenance/technical-debt-register.md) if work remains.
- Add or extend a test in `tests/Feature` for the weakness.
- Update this page with anything that was unclear.

## Contacts (fill in)

| Role | Name | Contact |
| --- | --- | --- |
| Incident owner | _to be set_ | |
| Hub Director | _to be set_ | |
| Render account holder | _to be set_ | |
| Data protection contact | _to be set_ | |
