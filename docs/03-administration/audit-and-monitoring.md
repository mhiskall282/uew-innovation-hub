# Audit and Monitoring

> **Audience:** Hub Director · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Security monitoring](../08-security/security-monitoring.md), [Logging and monitoring](../09-deployment-and-operations/logging-and-monitoring.md), [Health checks](../09-deployment-and-operations/health-checks.md), [Logging and diagnostics](../11-troubleshooting/logging-and-diagnostics.md)

The Hub has an in-app audit log for people's actions and relies on the hosting platform for server logs and uptime. This page explains the audit log, what it records, and what the administrator can and cannot watch.

## The audit log

Route `workspace.audit.index` (`/workspace/audit`), needs `audit.view` (Hub Director only). Code: [`AuditLogController`](../../app/Http/Controllers/Workspace/AuditLogController.php), [`AuditLogger`](../../app/Services/AuditLogger.php), [`ActivityLog`](../../app/Models/ActivityLog.php), table `activity_logs`.

### What an entry holds

| Field | Source |
| --- | --- |
| `user_id` | The signed-in user, taken from the server-side session. Empty for anonymous events |
| `action` | Dotted name, such as `project.created`. The part before the dot is the "area" |
| `description` | Sentence in plain English |
| `ip_address` | `Request::ip()`. Correct behind Render only when `TRUSTED_PROXIES=*` |
| `properties` | JSON, usually a field-level `from`/`to` diff |
| `subject_type`, `subject_id` | The record affected |

`AuditLogger::diff()` never stores secrets: `password` and `remember_token` appear as `changed`. The SMTP password is redacted the same way. Entries cannot be spoofed from the browser because actor and IP are read on the server. The model is documented as append-only. The application has no code that edits or deletes entries, and no retention or pruning job exists.

### Reading the log

- 30 entries per page, newest first.
- Filters: **Area**, **User** and a free-text search over description, action and IP address.
- Entries about a project, application, grant, programme or other record also appear in that record's own history panel.
- The dashboard "Recent activity" card shows the latest 8.

### Actions recorded

Taken from calls to `AuditLogger::record()` in `app/`.

| Area | Actions |
| --- | --- |
| `auth` | `login`, `logout`, `login_failed`, `code_failed`, `password_changed`, `password_reset`, `sessions_revoked`, `default_password_refused` |
| `user` | `created`, `updated`, `deleted`, `activated`, `deactivated`, `profile_updated`, `password_rotated`, `password-reset_sent`, `verification_sent` |
| `member` | `registered`, `verified`, `onboarded`, `profile_updated`, `unsubscribed` |
| `achievement` | `awarded`, `revoked` |
| `program` | `created`, `updated`, `deleted` |
| `application` | `started`, `submitted`, `reviewed`, `withdrawn`, `message` |
| `grant` | `reviewed` (legacy grant application) |
| `report` | `reviewed` (progress report), `exported` (PDF) |
| `broadcast` | `sent` |
| `project`, `post`, `publication`, `organization`, `page` | `created`, `updated`, `deleted` |
| `album` | `created`, `updated`, `photos_added`, `arranged`, `photo_deleted`, `deleted` |
| `message` | `received`, `updated`, `deleted` (contact enquiries) |
| `settings` | `updated`, `mail_tested` |
| `template` | `updated`, `reset` |

### What is not logged

- CSV exports of members, projects and programme applications. Only the PDF report writes `report.exported`.
- Successful downloads of uploaded documents.
- Reads of any record, including member profiles.

> **Gap found:** the **Area** filter in `AuditLogController::AREAS` offers only `auth`, `project`, `grant`, `post`, `publication`, `organization` and `user`. Entries in the other areas (`program`, `application`, `album`, `broadcast`, `settings`, `template`, `page`, `message`, `member`, `achievement`, `report`) cannot be picked from the menu. Use the search box with the area name, for example `broadcast.` or `album.`, to find them.

### Questions the log can answer

| Question | How |
| --- | --- |
| Who changed this project? | Project page, history panel, or search the project title |
| Who deactivated an account? | Area `user`, search `deactivated` |
| Has someone been guessing passwords? | Area `auth`, search `login_failed` or the e-mail address; check the IP column |
| Did the Hub Director change the SMTP server? | Search `settings.updated` |
| Which tester accounts had default passwords replaced? | Search `password_rotated` |

## Server logs

| Where | Setting | What you see |
| --- | --- | --- |
| Local | `LOG_CHANNEL=stack`, `LOG_STACK=single`, file `storage/logs/laravel.log` | Application errors and, with `MAIL_MAILER=log`, full e-mail text |
| Render | `LOG_CHANNEL=stderr`, `LOG_LEVEL=warning` in `render.yaml` | Render dashboard > service > Logs. PHP errors go to stderr (`docker/php.ini`) |

The e-mail log contains one-time verification codes when `MAIL_MAILER=log`. The application itself never writes codes to logs, but the log mailer does. Treat the log as sensitive on any site that uses it.

Commands for developers and operators:

```powershell
Get-Content storage\logs\laravel.log -Tail 100
Get-Content storage\logs\laravel.log -Wait
php artisan pail
```

```bash
tail -n 100 storage/logs/laravel.log
php artisan pail
```

`php artisan pail` is part of the `composer run dev` script. More in [logging and diagnostics](../11-troubleshooting/logging-and-diagnostics.md).

## Monitoring the running system

| Check | How | Limits |
| --- | --- | --- |
| Is the app up? | `GET /up`. Laravel's built-in health route, used by Render as `healthCheckPath` | Reports that the app boots. It does not test the database or the queue |
| Environment, versions, queue and mail mode | **Site settings > System** tab | Shows configuration, not live state |
| Waiting work | Sidebar badges (applications, incubation reports, legacy grants, enquiries) and dashboard counters | Cached up to 60 seconds |
| Queue health | Query the `jobs` and `failed_jobs` tables | No admin screen. See [admin troubleshooting](admin-troubleshooting.md#e-mails-are-not-arriving) |
| Failed sign-ins | Audit log `auth.login_failed` | No alerts |

> **Recommended, not implemented:** uptime alerts, a failed-jobs screen, an error tracker and audit log retention rules. The Render free plan sleeps after 15 minutes without traffic, so an external pinger is not a good test of availability there.

Docker also defines a `HEALTHCHECK` in the `Dockerfile` (every 30 seconds, 5-second timeout, 60-second start period).

## Review routine for the Hub Director

| When | What |
| --- | --- |
| Weekly | Skim `auth.login_failed` and `user.*` entries. Check that the only people with `Hub Director` on the Roles page are expected |
| After each staff change | Confirm `user.created`, `user.deactivated` or `user.updated` appears |
| After any SMTP change | Confirm `settings.updated` and `settings.mail_tested` |
| Monthly | Check that no one still uses a demo account. Search `password_rotated`. Confirm `BLOCK_DEFAULT_PASSWORDS` is on for hosted sites |
