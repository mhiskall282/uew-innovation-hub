# System Configuration

> **Audience:** Hub Director · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Environment configuration](../09-deployment-and-operations/environment-configuration.md), [Secrets management](../08-security/secrets-management.md), [Content management](content-management.md), [Installation problems](../11-troubleshooting/installation-problems.md)

Configuration lives in two places. Day-to-day wording, contact details and e-mail settings are edited in the workspace and stored in the database. Server behaviour (database, storage, queue, keys) is set with environment variables and cannot be changed from the browser. This page covers both, plus the three command-line tasks. The full variable list is owned by [environment configuration](../09-deployment-and-operations/environment-configuration.md).

## Site settings

Route `workspace.settings.edit` (`/workspace/settings`), needs `settings.manage` (Hub Director only). Code: [`SettingsController`](../../app/Http/Controllers/Workspace/SettingsController.php), [`SettingsService`](../../app/Services/SettingsService.php).

The page has four tabs.

| Tab | What you set |
| --- | --- |
| General & contact | Public e-mail, phone, address, office hours, map link, five social links (Facebook, X, LinkedIn, YouTube, Instagram), **Allow new member sign-ups** (`registration_open`), and the announcement banner (on/off, text up to 200 characters, optional link) |
| Homepage | Hero small heading, headline, introduction, call-to-action title and text, footer "About the Hub" |
| E-mail delivery | Sending method and SMTP details (next section) |
| System | Read-only: environment, Laravel and PHP versions, database, cache store, session driver, queue, mail transport, documents disk |

Behaviour:

- Settings are stored as key/value rows in `settings`, merged over the defaults in `SettingsService::defaults()`, and cached forever until you save. Saving clears the cache, so the public site changes at once.
- Every change is written to the audit log as `settings.updated`, listing the changed keys with old and new values. The SMTP password is logged as `changed` only.
- The phone number stays hidden on the public site until you enter one.
- Turning off **Allow new member sign-ups** closes registration without a deploy.
- If the database is unreachable or not migrated, the site falls back to the defaults instead of crashing (`SettingsService::all()`).

The **System** tab is the quickest way to confirm what a running server is using. If it shows `Mail transport: log`, no e-mail is leaving the server.

## SMTP and e-mail delivery

Code: [`MailSettings`](../../app/Services/MailSettings.php), `SettingsController::updateMail` and `testMail`.

### Delivery methods

| Option | Key | Effect |
| --- | --- | --- |
| Use server configuration (.env) | `env` | Uses `MAIL_*` environment variables unchanged. Default |
| SMTP server | `smtp` | Uses the host, port, encryption, user name and password you enter |
| Log only (no e-mails sent) | `log` | Writes e-mails to the log. Nothing is delivered |

### Set up SMTP

1. Open **Site settings > E-mail delivery**.
2. Choose **SMTP server**. Enter host, port, encryption (`STARTTLS (port 587)`, `SSL/TLS (port 465)` or `None`), user name and password.
3. Enter a **From** address and name. These override `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME`.
4. Save. Then press **Send test e-mail**. It sends to your own address, is limited to 5 per minute, and shows the error text (first 300 characters) if it fails. Check the spam folder.

Rules:

- The password is encrypted with `APP_KEY` before storage and never sent back to the browser. Leave the box empty to keep the current password, or tick **clear password** to remove it. **Warning:** if `APP_KEY` changes, the stored SMTP password can no longer be decrypted and mail will fail. Enter it again.
- Saving runs `queue:restart` so running workers pick up the new settings. A worker that was not running will start with the new values anyway.
- A Gmail account needs an App Password, not the Google account password (`.env.example` has an example block).
- With `MAIL_MAILER=log` set in the environment (the default in `render.yaml`) and the method left on `env`, no mail is delivered. Switch to SMTP here or set the `MAIL_*` variables.
- Development and staging only: `MAIL_ALWAYS_TO` redirects every e-mail to one inbox. It is ignored when `APP_ENV=production`.

## E-mail templates

Route `workspace.templates.index`, needs `settings.manage`. Code: [`EmailTemplateController`](../../app/Http/Controllers/Workspace/EmailTemplateController.php), [`EmailTemplates`](../../app/Support/EmailTemplates.php).

Eight templates exist.

| Key | Group | Sent when |
| --- | --- | --- |
| `verification_code` | Accounts | Member or staff must verify their e-mail |
| `password_reset_code` | Accounts | Password reset requested, or sent by an administrator |
| `welcome` | Accounts | After a member finishes onboarding |
| `application_submitted` | Applications | A member submits an application |
| `application_status` | Applications | A reviewer changes an application status and ticks "notify" |
| `program_launched` | Announcements | Programme announcements and every broadcast |
| `staff_new_application` | Staff | A new application arrives for staff who opted in |
| `test_email` | Staff | You press **Send test e-mail** |

Steps:

1. Open a template. Edit the subject (max 200 characters) and body (max 20,000).
2. Use the placeholders listed on the page, written like `{{first_name}}`.
3. Use **Preview** to see it with sample data in a frame.
4. Save. New e-mails use it straight away (`EmailTemplates::flush()` clears the cache). Audit: `template.updated`.
5. **Reset to default** deletes your version. Audit: `template.reset`.

A customised template shows a marker in the list. The "password changed" notice is a separate mail class (`PasswordChangedMail`), not one of the eight templates.

## Environment variables the administrator should know

Only the ones that change administrator-visible behaviour are listed. See [environment configuration](../09-deployment-and-operations/environment-configuration.md) for all of them.

| Variable | Effect | Default |
| --- | --- | --- |
| `ADMIN_EMAIL`, `ADMIN_NAME`, `ADMIN_PASSWORD` | Used by `hub:create-admin` on boot | none, "Hub Director", none |
| `SEED_DEMO_DATA` | Create demo accounts and content on first boot | `false` (`render.yaml` sets `true`) |
| `SEED_PASSWORD` | Password for demo accounts and for `hub:secure-demo-accounts` | `password` in `.env.example` |
| `BLOCK_DEFAULT_PASSWORDS` | Refuse sign-in with `password`, `secret`, `12345678`, `password123`, `admin`, `changeme` | `false` |
| `SEARCH_INDEXING` | Let search engines index the public site | `false` |
| `DOCUMENTS_DISK`, `GALLERY_DISK` | Where PDFs and photos are stored (`private` / `gallery` or `s3`) | local disk |
| `MAX_UPLOAD_KB` | PDF upload limit | 20480 |
| `METRICS_CACHE_TTL` | Seconds dashboard figures stay cached | 300 |
| `RUN_QUEUE_WORKER` | Start a queue worker inside the web container | `true` |
| `RUN_MIGRATIONS` | Run `migrate --force` on boot | `true` |

> **Note:** `GALLERY_DISK` is read in `config/hub.php` but missing from `.env.example`.

## Command-line tasks

Run these on the server (on Render, from the service Shell). They are the only administrator commands defined in `app/Console/Commands/`.

### `hub:create-admin`

Creates the first Hub Director. Safe to run on every deploy.

```bash
php artisan hub:create-admin --email=director@uew.edu.gh --name="Hub Director" --password="<12+ chars, mixed case, number>"
```

| Option | Falls back to |
| --- | --- |
| `--email` | `ADMIN_EMAIL` |
| `--name` | `ADMIN_NAME` (default "Hub Director") |
| `--password` | `ADMIN_PASSWORD`; if empty, a 20-character password is generated and printed once |

Behaviour (from `CreateAdministrator`):

- If an **active** Hub Director already exists, it prints "Nothing to do" and exits successfully. It cannot be used to add a second Director. Use **Staff accounts** for that.
- Without an e-mail it fails with "Provide --email or set ADMIN_EMAIL."
- A password you supply must be 12+ characters with upper and lower case and a number. Generated ones are not checked.
- The account is created active with the e-mail already verified, so the first Director is not locked out before SMTP works.
- Audit action `user.created`. A password passed on the command line can end up in shell history. Prefer `ADMIN_PASSWORD` in the environment or the generated password.

### `hub:secure-demo-accounts`

Replaces well-known default passwords with `SEED_PASSWORD`. It runs on every container start (`docker/entrypoint.sh`).

```bash
php artisan hub:secure-demo-accounts --dry-run   # list affected accounts only
php artisan hub:secure-demo-accounts             # apply
```

- It scans every user and checks the stored hash against `password`, `secret`, `12345678`, `password123`, `admin` and `changeme`.
- `--dry-run` prints the affected e-mail addresses and changes nothing.
- Without `--dry-run`, if `SEED_PASSWORD` is missing, weak or itself one of those words, it prints an error and exits with failure without changing anything.
- Otherwise it sets the new password, clears the remember-me token and logs `user.password_rotated` per account. It never prints a password.
- It only changes accounts still using a default password, never passwords people chose.

> **Warning:** The command prints the list of affected e-mail addresses. Run it in a private shell.

### `hub:import-documents`

See [data management](data-management.md#importing-legacy-documents).

## After any configuration change

| Changed | Do this |
| --- | --- |
| Site settings, templates | Nothing. Effective at once |
| SMTP in the workspace | Send a test e-mail |
| `.env` / Render environment | Redeploy or restart. `docker/entrypoint.sh` runs `php artisan optimize`, which caches config. Locally run `php artisan optimize:clear` |
| `APP_KEY` | Re-enter the SMTP password. Existing sessions and encrypted values are invalidated |
