# Authentication Problems

> **Audience:** Developers, administrators · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Authentication architecture](../04-architecture/authentication-architecture.md), [Authentication security](../08-security/authentication-security.md), [User management](../03-administration/user-management.md), [Login and authentication (users)](../02-user-guide/login-and-authentication.md)

Sign-in, e-mail verification, password reset, throttling and lockout. Code: `app/Http/Controllers/Auth/*`, [`LoginRequest`](../../app/Http/Requests/Auth/LoginRequest.php), [`VerificationService`](../../app/Services/VerificationService.php), middleware in `app/Http/Middleware/`, rate limiters in `AppServiceProvider::registerRateLimiters()`.

## How the checks stack up

A request to `/portal` or `/workspace` passes through `auth` then `active` (`EnsureAccountIsActive`) then `verified.email` (`EnsureEmailIsVerified`), then `onboarded` for members, then `can:workspace.access` for staff. Each failing step has a different symptom.

| Step fails | What the user sees |
| --- | --- |
| Not signed in | Redirect to `/sign-in` |
| Account inactive | Redirect to sign-in with "Your account has been deactivated. Contact the Hub Director if this is unexpected." |
| E-mail not verified | Redirect to `/verify-email` |
| Member not onboarded | Redirect to `/onboarding` |
| Staff role without permission | 403 page |

## "These details do not match an active account."

**Symptoms:** Correct-looking credentials are refused.

**Possible causes:**

1. Wrong password or e-mail typo. The e-mail is lower-cased before checking.
2. The account is deactivated or suspended. The form gives the same message for unknown, wrong-password and inactive accounts on purpose.
3. A demo account password changed. `hub:secure-demo-accounts` moves accounts that used `password`, `secret` and similar to `SEED_PASSWORD` on every container boot.
4. The Caps Lock or a password manager pasted an old password.

**Diagnostic steps:**

1. As an administrator, open the account (Staff accounts or Members) and check whether it is active.
2. Look for `auth.login_failed` for that e-mail in the audit log, and check the IP.
3. Locally:

```powershell
php artisan tinker --execute="dump(App\Models\User::where('email','person@example.com')->first(['id','role','is_active','email_verified_at'])?->toArray());"
```

**Resolution:** Re-activate if needed, then send a password-reset e-mail from the account page, or let the person use **Forgot password**.

**Prevention:** Tell testers to change the shared tester password after first sign-in. Set `SEED_PASSWORD` to a strong value.

## "This password is not allowed on this site"

**Symptoms:** The sign-in form says the password is not allowed and suggests "Forgot password?".

**Cause:** `BLOCK_DEFAULT_PASSWORDS=true` and the typed password is one of `password`, `secret`, `12345678`, `password123`, `admin`, `changeme`. The check runs before the credentials are tried. It is audited as `auth.default_password_refused`.

**Resolution:** Reset the password, or run `php artisan hub:secure-demo-accounts` with a strong `SEED_PASSWORD` so default-password accounts move to it.

## "Too many sign-in attempts. Try again in N minute(s)."

**Symptoms:** The form refuses even the correct password.

**Possible causes:** `LoginRequest` locks for 15 minutes after 5 failures for the same e-mail and IP, or after 20 failures for the same e-mail from all IPs. A shared office IP can trigger the first one. A `Lockout` event is fired.

**Diagnostic steps:** Audit log, area `auth`, search the e-mail for repeated `login_failed`.

**Commands / Resolution:**

- Wait the stated minutes.
- The person can use **Forgot password**. A successful reset does not clear the lock. The limiter key stays until it decays.
- Developers can clear limiter keys by clearing the cache. Rate-limit counters live in the cache store (`CACHE_STORE=database` by default):

```powershell
php artisan cache:clear
```

`cache:clear` also drops cached settings and metrics, which rebuild on the next request. It does not sign anyone out.

**Prevention:** Behind a proxy set `TRUSTED_PROXIES=*`, otherwise every visitor looks like the proxy's IP and the per-IP limit is shared by everyone.

## HTTP 429 "Too Many Requests"

**Symptoms:** A page shows the 429 error view (`resources/views/errors/429.blade.php`), or a form says to try later.

**Limits that exist in the code** (`routes/web.php`, `AppServiceProvider`):

| Where | Limit |
| --- | --- |
| Sign-in `POST /sign-in` | 10 per minute, plus the lockout above |
| Sign-up | 5 per minute and 30 per day per IP (`registrations`) |
| Forgot password | 3 per minute per IP, and 10 per hour per e-mail and IP (`password-codes`) |
| Reset password, verification submit | 10 per minute |
| Resend verification code | 5 per 10 minutes, and one code per 60 seconds per user |
| E-mail links (`email/verify/...`, `password/reset/...`) | 10 per minute |
| Unsubscribe | 20 per minute |
| Contact form | 3 per minute, 20 per day per IP |
| Apply to a programme | 30 per minute |
| Admin-sent reset/verification e-mails | 10 per minute |
| Workspace PDF report | 10 per minute |
| Test e-mail | 5 per minute |
| Public API | 60 per minute per IP |

**Resolution:** Wait. The `Retry-After` header and the lockout message give the delay. In development, `php artisan cache:clear` resets all counters. Automated tests that hammer a route should clear the limiter or use a different IP.

**Prevention:** Check `TRUSTED_PROXIES` on hosted sites.

## Verification or reset code is not accepted

**Symptoms:** "That code is not correct. N attempt(s) left.", "This code has expired. Ask for a new one.", "Too many incorrect attempts. Ask for a new code."

**Facts from `VerificationService`:** codes have six digits, expire after 10 minutes, allow 5 wrong guesses, and a new code invalidates earlier ones. A new code can be requested once per 60 seconds. Codes are stored hashed and only delivered by e-mail.

**Possible causes:** The person used an older e-mail. The ten minutes passed. More than five wrong tries. The e-mail never arrived (see below).

**Resolution:** Request a new code and use the newest e-mail. Administrators can resend: **Members** > account > **Resend verification**, or **Send password reset**.

## Verification or reset e-mail never arrives

**Possible causes (check in this order):**

1. `MAIL_MAILER=log` (the `render.yaml` default). The e-mail is only written to the log. See [backend problems](backend-problems.md#e-mails-only-appear-in-the-log).
2. SMTP is misconfigured. Use **Site settings > E-mail delivery > Send test e-mail**.
3. Spam folder.
4. The one-per-60-seconds limit blocked a second request.

Codes are sent synchronously, so a missing queue worker does not stop them. It does stop confirmations, status e-mails and broadcasts.

**Diagnostic steps (local):**

```powershell
Get-Content storage\logs\laravel.log -Tail 80
```

**Prevention:** Configure SMTP before inviting users.

## "This link has expired" (signed link page)

**Symptoms:** Clicking the button in a verification or reset e-mail shows the **Link expired** page (view `auth.link-expired`, HTTP 403).

**Cause:** `InvalidSignatureException` is rendered as that page in `bootstrap/app.php`. It appears when the link is older than its 10 minutes, was already used, was replaced by a newer e-mail, or the URL was changed.

**Another cause on hosted sites:** a link signed for `https://...` but opened as `http://...` (or a different host) fails the signature check. This happens when `APP_URL` is wrong, or `TRUSTED_PROXIES` is not set behind a load balancer. **Requires confirmation** on your host: compare the host and scheme in the e-mailed link with the address in the browser.

**Resolution:** Use the buttons on the page: sign in to get a new verification e-mail, or request a password reset. Mail scanners that pre-open links do not use them up, because the link only opens a confirmation page.

**Prevention:** Set `APP_URL` to the public HTTPS address and `TRUSTED_PROXIES=*` on Render. Tell people to use the newest e-mail.

## Redirected to "Verify your e-mail" again and again

**Symptoms:** After sign-in every portal or workspace page goes back to `/verify-email`.

**Cause:** `email_verified_at` is empty. Members must verify. Staff accounts created by the Hub Director or by `hub:create-admin` are verified automatically. Staff records created another way (seeder, database) may not be.

**Resolution:** Resend from the account page, or enter the code. Developers can set it:

```powershell
php artisan tinker --execute="App\Models\User::where('email','person@example.com')->update(['email_verified_at'=>now()]);"
```

**Prevention:** Use the workspace to create staff.

## "Page expired" (419)

**Symptoms:** Submitting any form shows the 419 page, or sign-in loops back to the form.

**Possible causes:**

1. The form was open longer than `SESSION_LIFETIME` (120 minutes).
2. The session cookie is not stored. `SESSION_SECURE_COOKIE=true` on a plain `http://localhost` site means the browser drops the cookie. Locally keep it `false`.
3. The `sessions` table is missing (`SESSION_DRIVER=database` before migrating).
4. The browser blocks cookies, or an old cookie from another app on `localhost` conflicts.
5. `APP_KEY` changed, invalidating existing sessions.

**Diagnostic steps and resolution:**

```powershell
php artisan migrate:status
php artisan config:show session
```

Clear cookies for the site, check `.env`, run `php artisan optimize:clear`, and migrate if the sessions table is missing.

## Signed out unexpectedly

**Possible causes:** The account was deactivated (takes effect on the next request). The person changed their password or pressed "sign out other devices" (`UserService::revokeOtherSessions` deletes other sessions). Session lifetime ended. The `sessions` table was cleared by a database reset.

**Diagnostic step:** Audit log: `user.deactivated`, `auth.password_changed`, `auth.sessions_revoked`.

## Locked out as the only Hub Director

**Symptoms:** The only Hub Director cannot sign in and no one else can manage staff.

**Resolution:** On the server run the command. It only acts when no **active** Hub Director exists:

```bash
php artisan hub:create-admin --email=director@uew.edu.gh --name="Hub Director"
```

If the account exists but is inactive, the command creates another one (the e-mail must be unused). Re-activating the old record needs a database change. If the e-mail is already taken the command fails with a validation error. A generated password is shown once. See [system configuration](../03-administration/system-configuration.md#command-line-tasks).

## Wrong page after sign-in

Members land on `/portal`, staff on `/workspace` (`User::homeUrl()`). A member who opens a workspace URL gets a 403 because members hold no permissions.
