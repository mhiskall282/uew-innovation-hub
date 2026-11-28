# Administrator Troubleshooting

> **Audience:** Hub Director, Programme Officers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Troubleshooting overview](../11-troubleshooting/troubleshooting-overview.md), [Authentication problems](../11-troubleshooting/authentication-problems.md), [Backend problems](../11-troubleshooting/backend-problems.md), [Audit and monitoring](audit-and-monitoring.md)

Problems that administrators meet in the workspace, with the cause and what to do. Developer-level diagnosis is in [section 11](../11-troubleshooting/troubleshooting-overview.md).

## Quick lookup

| What you see | Go to |
| --- | --- |
| A menu item is missing | [Menu item missing](#menu-item-missing-or-403) |
| "403" or "This action is unauthorized" | [Menu item missing](#menu-item-missing-or-403) |
| No e-mails arrive | [E-mails are not arriving](#e-mails-are-not-arriving) |
| Broadcast never finishes | [Broadcast stays sending](#broadcast-stays-sending) |
| Cannot change a role or deactivate | [Staff account refused](#staff-account-changes-are-refused) |
| Documents or photos missing | [Files and photos are gone](#files-and-photos-are-gone) |
| Photo upload adds fewer photos than chosen | [Photo upload](#photo-upload-adds-fewer-photos) |
| Report figures look wrong | [Reports look wrong](#reports-look-wrong) |
| Page said "expired" | [Link or page expired](#link-or-page-expired) |
| First page load takes about a minute | [Slow first load](#slow-first-load) |

## Menu item missing or 403

**Cause:** The role does not hold the permission. Menu items are hidden, and typed URLs return 403.

**Check:** Open **Roles & permissions** (Hub Director) and compare. Examples: Programme Officers have no Staff accounts, Site settings, E-mail templates or Audit log. Interns have no Reports. Read-only Staff see only Dashboard, Projects, Partners and Account settings.

**Fix:** If the person needs the access, change their role under **Staff accounts**. There is no per-person permission. See [role and permission management](role-and-permission-management.md).

Also check the account is **active**, and that the person has verified their e-mail. An unverified staff member is redirected to the verification page (`verified.email`). Accounts the Hub Director creates are already verified.

## E-mails are not arriving

Symptoms: members say the verification code or reset e-mail never came. Application status e-mails are missing. The test e-mail does not arrive.

Work through these in order.

1. **Delivery method.** **Site settings > System** shows `Mail transport`. If it says `log`, nothing is delivered. `render.yaml` sets `MAIL_MAILER=log`. Choose **SMTP server** under **E-mail delivery** and save.
2. **Send a test e-mail.** If it fails, the red message shows the SMTP error. Typical causes: wrong host or port, STARTTLS selected for port 465 (or the reverse), Gmail without an App Password, a blocked outbound port.
3. **Spam folder.** Ask the recipient to check it. Check the **From** address is one your SMTP provider allows.
4. **Which e-mails need the queue.** Verification and reset codes are sent immediately. Application confirmations and status e-mails, broadcasts, staff notices and "password changed" notices are queued and need a worker. With no worker they never leave.
5. **Queue worker.** `docker/entrypoint.sh` starts one unless `RUN_QUEUE_WORKER=false` or `QUEUE_CONNECTION=sync`. Ask your developer to check, or see [backend problems](../11-troubleshooting/backend-problems.md#queue-worker-not-running).
6. **APP_KEY changed.** The stored SMTP password cannot be decrypted. Re-enter it and save.
7. **Member preferences.** Broadcasts skip members who are unverified, suspended or unsubscribed. The audience count on the broadcast form shows how many qualify.
8. **Throttle.** Admin-sent reset and verification e-mails are limited to 10 per minute per administrator, and each address can receive one code per 60 seconds. The page shows "A code was sent recently...". Wait and try once.

## Broadcast stays sending

**Cause:** The `SendBroadcast` job has not run or it failed (it tries once, with a 600-second timeout). The status changes to `sent` only at the end.

**Fix:**

1. Wait two minutes on a freshly woken Render service. The worker starts with the container.
2. Confirm a worker exists (see above).
3. Ask a developer to check the `failed_jobs` table and logs (`php artisan queue:failed`). See [logging and diagnostics](../11-troubleshooting/logging-and-diagnostics.md).
4. A broadcast that is no longer `draft` cannot be edited, deleted or re-sent from the screen. Create a new broadcast if recipients did not receive it. Check first who already got it, to avoid duplicates.

## Staff account changes are refused

| Message | Meaning | What to do |
| --- | --- | --- |
| "You cannot change your own role. Ask another Hub Director." | Self-role guard | Ask another Hub Director, or create one first |
| "This is the only active Hub Director. Assign another Hub Director first." | Last-director guard on a role change | Promote someone else, then retry |
| "The only active Hub Director cannot be deactivated." / "...deleted." | Same guard | Same |
| No Deactivate or Delete button on your own row | You cannot act on your own account | Ask another Hub Director |
| "Reactivate the account first." | You tried to send a reset e-mail to an inactive account | Activate, then send |
| Password rule error | Production needs 12+ characters, upper and lower case, and a number | Choose a longer password |

If no active Hub Director exists at all (for example the only one was deactivated through the database), run `php artisan hub:create-admin --email=... --name=...` on the server. See [system configuration](system-configuration.md#command-line-tasks).

## Files and photos are gone

**Symptoms:** A project report or publication PDF returns "The requested document is not available." Gallery albums show fewer photos than you uploaded, or none. Database records still exist.

**Cause:** The files were on a container disk that was wiped by a deploy or restart (Render free plan).

**Fix:**

1. Re-upload the files. For PDFs that came from the old system, a developer can run `php artisan hub:import-documents` again.
2. Prevent it: set `DOCUMENTS_DISK=s3` and `GALLERY_DISK=s3` with bucket credentials. See [data management](data-management.md#storage-and-durability).

Public pages already hide gallery photos whose files are missing, so visitors see gaps, not broken images. Staff pages still list the rows.

## Photo upload adds fewer photos

**Symptoms:** You select 10 photos and only 5 appear, or the upload fails with a generic error.

**Cause:** On the Docker deployment `docker/php.ini` allows 5 files per request and a 30 MB request, while the form accepts 10 files up to 10 MB each. PHP discards extras silently.

**Fix:** Upload 3 to 5 photos per batch and keep phone photos under about 6 MB. Ask the developer to raise `max_file_uploads` and `post_max_size`. Other errors you may see: "Photos must be at least 300 × 300 pixels", "Each photo must be 10 MB or smaller", "larger than 30 megapixels", "could not be read. It may be damaged". The server needs the GD extension, which the Docker image includes.

## Reports look wrong

| Symptom | Cause |
| --- | --- |
| Numbers differ from what you expect for a custom range | The page used Last 12 months because the dates were reversed, incomplete or invalid |
| Acceptance rate is blank | No approved or rejected decisions were made in the period |
| Applications count is smaller than the programme page | Reports count by **submission date** in the period. Decisions count by **decision date** |
| Dashboard totals are a few minutes old | Metrics cache. `METRICS_CACHE_TTL` is 300 seconds by default |
| Members CSV shows "No" for Announcements | The member did not opt in |

## Link or page expired

- **"This link has expired" (HTTP 403).** E-mailed verification and reset links work once and expire in 10 minutes, and a newer e-mail replaces older ones. Send a new one from the account page.
- **"Page expired" (HTTP 419).** The form stayed open longer than the session lifetime (`SESSION_LIFETIME`, 120 minutes on Render). Refresh and submit again. Do not paste a long news article without saving first. Save drafts often.

## Slow first load

The Render free plan sleeps after 15 minutes without traffic. The next request can take about one minute while the container boots, migrates and starts the worker. Open the site and wait. See [performance problems](../11-troubleshooting/performance-problems.md).

## Cannot find a change someone made

Open the **Audit log** and search for the record's name. Unfiltered `Area` options are limited (see [audit and monitoring](audit-and-monitoring.md#what-is-not-logged)), so search `program.`, `application.` or `album.` as text instead.

## Who to contact

Keep these details ready for the developer: the page URL, the time, your role, what you clicked, the message shown, and the matching audit log entry. Do not send passwords or screenshots of the SMTP form.
