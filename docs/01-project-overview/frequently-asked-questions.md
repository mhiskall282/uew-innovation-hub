# Frequently asked questions

> **Audience:** Non-technical readers, project sponsors · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [User FAQ](../02-user-guide/user-faq.md), [System scope](system-scope.md), [Deployment overview](../09-deployment-and-operations/deployment-overview.md)

Questions about the platform as a whole. Questions about using the site are in the [User FAQ](../02-user-guide/user-faq.md).

## About the platform

**What is it?**
A website, member portal and staff workspace for the UEW Innovation Hub. See [Project overview](project-overview.md).

**Who runs it?**
The Hub Director has full control. Programme Officers run day-to-day work. See [User roles and permissions](../02-user-guide/user-roles-and-permissions.md).

**Is it live?**
A test site is at https://uew-innovation-hub.onrender.com. The handover report says it is ready for user acceptance testing, not yet approved for production.

**What replaced what?**
It replaced the procedural PHP system in `legacy/`. See [Problem statement](problem-statement.md).

**Does it cost money to run?**
The current setup uses Render's free web service and free PostgreSQL (`render.yaml`). Real use needs a paid or alternative database and file storage. Prices are not in the repository.

## Using the site

**Do I need an account to apply?**
Yes. Create a free account and confirm your e-mail. Browsing needs no account.

**Why did I not receive my verification code?**
On the test site e-mail is set to `log`, so no e-mail leaves the server until the Hub Director enters SMTP details (Site settings, E-mail). This is the most likely cause today.

**Can I edit an application after submitting it?**
No. Only drafts are editable (`ProgramApplication::isEditable()`). You can withdraw while it is Draft, Submitted or Under review and write to the Hub through the message thread.

**Who can see my application?**
You, and staff who can review applications (Programme Officers, Reviewers, the Hub Director). Reviewers do not see gender or disability details (`EngagementTest::test_reviewers_do_not_see_gender_or_disability`).

**Who can see my profile publicly?**
Nobody, unless you turn on a public profile. Then you can appear on the community page with your name, level and badges.

**Can I stop announcement e-mails?**
Yes. Every announcement has a one-click unsubscribe link, and the setting is in your profile.

## Staff and administration

**Can an intern publish news?**
No. Interns save drafts. Even if an intern asks to publish, the post stays a draft (`PostService`, `ContentPublishingTest`). A Programme Officer or the Hub Director publishes.

**Can staff change their own role?**
No (`PrivilegesTest::test_nobody_can_change_their_own_role`). The last active Hub Director cannot be demoted.

**Are scheduled posts published by a timer?**
No timer exists. A scheduled post becomes visible when its date passes, because visibility is calculated when a page loads.

**Where is the history of who did what?**
The audit log at `/workspace/audit`, visible to the Hub Director.

## Technical

**Why does the first page load take a long time?**
Render's free plan puts the service to sleep after 15 minutes without traffic. The first request after that takes about a minute.

**Why did uploaded files disappear after a deploy?**
The free plan has no persistent disk. Set `DOCUMENTS_DISK=s3` and `GALLERY_DISK=s3` with an S3 or R2 bucket. See [Environment configuration](../09-deployment-and-operations/environment-configuration.md).

**Which database does it use?**
PostgreSQL when hosted, SQLite for local work by default. Tests run on both in CI.

**Is there an API?**
Yes, read-only and public at `/api/v1`. There are no API keys, no write endpoints and no webhooks. See [API overview](../06-api-reference/api-overview.md).

**Can I export data?**
Yes. CSV exports exist for projects, members and a programme's applications. A PDF report is available at `/workspace/reports/pdf`. There is no Excel export.

**Are there scheduled jobs or backups?**
No scheduler exists in the code. Database backups depend on the hosting plan. See [Backup and recovery](../09-deployment-and-operations/backup-and-recovery.md).

**Is it secure?**
Many controls are in place and tested (see [Security overview](../08-security/security-overview.md)). Two open items matter: demo accounts on the test site must be moved to a strong `SEED_PASSWORD`, and a tester password remains in git history (commit `87df882`). Staff multi-factor authentication does not exist.

**Where do I report a problem or ask for a feature?**
Use the GitHub repository's issue tracker at https://github.com/mhiskall282/uew-innovation-hub. **Requires confirmation:** whether issues are enabled and who triages them.
