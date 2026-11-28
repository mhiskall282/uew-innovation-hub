# Administrative Workflows

> **Audience:** Hub Director, Programme Officers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [User management](user-management.md), [Content management](content-management.md), [System configuration](system-configuration.md), [Step-by-step workflows (users)](../02-user-guide/step-by-step-workflows.md), [Business workflows](../13-product-and-business/business-workflows.md)

Step-by-step procedures for recurring administrator work. Each one lists who may do it, the steps, and what to check afterwards.

## 1. First-day setup for a new Hub Director

**Who:** the person with server access, then the Hub Director.

1. Confirm `ADMIN_EMAIL` and `ADMIN_PASSWORD` are set (Render: environment tab). On boot `docker/entrypoint.sh` runs `php artisan hub:create-admin`. Locally run it yourself ([system configuration](system-configuration.md#command-line-tasks)).
2. Sign in at `/sign-in`. Open **Account settings** and change the password.
3. Open **Site settings > E-mail delivery**. Choose SMTP, enter details, save, press **Send test e-mail**. Confirm it arrives.
4. Open **Site settings > General & contact**. Enter the real phone number, check the address, add social links.
5. Create a second Hub Director under **Staff accounts** so the system never depends on one login.
6. Create Programme Officer, Reviewer and Intern accounts as needed.
7. On a test site, confirm demo accounts are secured: set a strong `SEED_PASSWORD` and `BLOCK_DEFAULT_PASSWORDS=true`.
8. Read the **Roles & permissions** page.

Check: Audit log shows `user.created` and `settings.updated`.

## 2. Add a staff member

**Who:** Hub Director.

1. **Staff accounts** > **New account**. Choose the lowest role that covers their work (see [roles](role-and-permission-management.md)).
2. Set a temporary password that meets the production rule (12+ characters, mixed case, number).
3. Give them the password through a private channel, not by e-mail to a shared inbox. Ask them to change it at once.
4. Check **Staff accounts** lists them as active.

## 3. A staff member leaves or changes job

**Who:** Hub Director.

1. Role change: **Edit**, choose the new role, save. It takes effect on their next request.
2. Leaving: **Deactivate** (do not delete). They are signed out everywhere immediately.
3. If they were the only Hub Director, first promote another person. The system refuses to deactivate or demote the last one.
4. If they authored posts or reviewed applications, nothing else needs doing. Records stay attributed.
5. Check the audit log for `user.deactivated`.

## 4. A person cannot sign in

**Who:** Hub Director (staff), Programme Officer (members).

1. Find the account. Staff: **Staff accounts**. Member: **Members** and search.
2. Check status: **deactivated** or **suspended** means re-activate first.
3. Check verification: members with `unverified` state need **Resend verification**.
4. Send a **password reset** e-mail (the button is on the account page).
5. If they report "too many sign-in attempts", wait 15 minutes. There is no unlock button.
6. If the e-mail does not arrive, see [admin troubleshooting](admin-troubleshooting.md#e-mails-are-not-arriving).

## 5. Open a new programme

**Who:** Programme Officer, Hub Director (`programs.manage`).

1. **Programmes** > **New programme**. Set type, dates, capacity, funding and eligibility.
2. Build the form. Keep it short. Mark only essential fields required. Use **PDF upload** only when needed.
3. Save as **draft**. Preview the public page and the application as a test member.
4. Set the status to **published**. Tick **notify members** to send an announcement to opted-in members with a matching interest.
5. Check the programme appears at `/programmes` on the public website, and a broadcast row appears under **Broadcasts** as `sending` then `sent`.

## 6. Review applications and announce results

**Who:** Reviewer, Programme Officer, Hub Director (`applications.review`).

1. After the closing date, set the programme to **closed** (**Programmes** > Edit).
2. Open the programme's page. Read each application. Set **under review** or **shortlisted**, add internal notes and a score.
3. Export the CSV (`workspace.programs.export`) if the panel wants to score offline.
4. Decide. For a few, use each application page. For many, tick them on the programme page and use the bulk action. Tick **notify** if applicants should get an e-mail.
5. Write a decision message on individual applications when applicants need feedback. Bulk changes keep the current message.
6. Optionally send a broadcast to thank applicants.
7. Archive the programme when the cycle ends.

> **Warning:** Check the filter before a bulk decision. Rejected and approved states are final from the member's point of view, and the e-mail cannot be recalled.

Check: the counts on the programme page match your decisions. Audit has one `application.reviewed` per application.

## 7. Follow up funded teams

**Who:** Reviewer, Programme Officer, Hub Director.

1. Approved members see a **Progress reports** option in their portal.
2. Watch the **Incubation board** badge for reports awaiting review.
3. Open each report, compare with earlier ones, and choose **Accepted** or **Changes requested** with feedback.
4. Use the budget table (funding versus reported spending) to spot programmes spending faster than they report.

## 8. Send an announcement

**Who:** Programme Officer, Hub Director (`broadcasts.send`).

1. **Broadcasts** > **New**. Write the subject and body. Keep `{{first_name}}`.
2. Choose interests or affiliations. Read the live recipient count.
3. **Save draft**. Review it, then open the draft and **Send**.
4. Confirm the status reaches `sent` within a few minutes. Stuck on `sending`: see [admin troubleshooting](admin-troubleshooting.md#broadcast-stays-sending).

Members can unsubscribe with one click. Each unsubscribe is audited as `member.unsubscribed`.

## 9. Publish news

**Who:** Intern drafts, Programme Officer publishes.

1. Intern: **News & posts** > **New post**. Save as draft.
2. Officer: open the draft, check the text and image, choose **Publish now** or **Schedule** with a date and time.
3. Check the post on `/news`. A scheduled post becomes visible by itself when its time passes. No job is involved.

## 10. Publish a photo album

**Who:** Intern uploads, Programme Officer publishes.

1. **Gallery** > **New album**. Add title, date, location. Save.
2. Upload photos in batches of 3 to 5 (see the upload-limit issue in [content management](content-management.md#gallery-albums)).
3. Set captions, order and cover. Save.
4. Officer: tick **Published** (and **Featured** if wanted), save.
5. Check `/gallery`. If photos are missing after a redeploy, the disk was wiped.

## 11. Change the wording of an automatic e-mail

**Who:** Hub Director.

1. **E-mail templates**, open the template, edit subject and body, keep placeholders.
2. **Preview**. Save. Press **Send test e-mail** under Site settings if you changed `test_email` or want to see delivery.
3. To undo, **Reset to default**.

## 12. Close registration or show a banner

**Who:** Hub Director.

1. **Site settings > General & contact**.
2. Untick **Allow new member sign-ups** to close registration. Existing members are unaffected.
3. Tick **Show the banner**, enter text and an optional link. Save. The banner shows on the public site at once.

## 13. Prepare a funder report

**Who:** Hub Director, Programme Officer, Reviewer (`reports.view`).

1. **Reports**. Choose the reporting period.
2. Read the figures. Keep in mind the period rules in [admin dashboard](admin-dashboard.md#what-each-figure-means).
3. Select sections and press the PDF button.
4. Export the project portfolio CSV from the Projects page if the funder wants detail.

## 14. Rotate default passwords on a test site

**Who:** person with shell access.

```bash
php artisan hub:secure-demo-accounts --dry-run
php artisan hub:secure-demo-accounts
```

Set `SEED_PASSWORD` to a strong value first, otherwise the command refuses.

## 15. Quarterly housekeeping checklist

- Confirm every active staff account is still needed.
- Review members flagged `suspended`.
- Read the audit log for unusual `auth.login_failed` bursts.
- Check disks are on durable storage and a recent database backup exists.
- Send a test e-mail.
- Archive finished programmes. Remove stale drafts.
