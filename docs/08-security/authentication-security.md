# Authentication Security

> **Audience:** developers, security reviewers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Authentication architecture](../04-architecture/authentication-architecture.md), [Authorization security](authorization-security.md), [Threat model](threat-model.md), [Security testing](../10-testing-and-quality/security-testing.md)

This page describes how people prove who they are: sign-in, throttling and lockout, one-time e-mail codes, signed e-mail links, sessions and password policy. Gaps are listed at the end.

## Sign-in

Route: `POST /sign-in` (`login.store`), middleware `guest`, `throttle:10,1`. Logic is in [`LoginRequest::authenticate()`](../../app/Http/Requests/Auth/LoginRequest.php) and [`SessionController::store()`](../../app/Http/Controllers/Auth/SessionController.php).

1. The e-mail is lower-cased and trimmed. Only accounts with `is_active = true` can authenticate.
2. Wrong password, unknown e-mail and deactivated account return one message: "These details do not match an active account." The form cannot be used to discover accounts.
3. On success the session ID is regenerated (`session()->regenerate()`), `last_login_at` and `last_login_ip` are stored, and `auth.login` is written to the audit log.
4. Unverified accounts go to `verification.notice`. Members never land in `/workspace`, even through an intended URL.
5. Sign-out invalidates the session, regenerates the CSRF token and records `auth.logout`.

"Remember me" is supported through the `remember` field. Deactivating a user or changing their password rotates `remember_token` (`UserService::revokeOtherSessions`).

## Throttling and lockout

| Limit | Key | Threshold | Window | Cleared on success? |
| --- | --- | --- | --- | --- |
| Route throttle | IP | 10 requests/min | 1 min | n/a |
| Per e-mail and IP | `lower(email)|ip` | 5 failures | 15 min | Yes |
| Per account (all IPs) | `login-account|sha256(email)` | 20 failures | 15 min | No, by design |

When blocked, the form shows "Too many sign-in attempts. Try again in N minute(s)." and a Laravel `Lockout` event fires. Every failed attempt writes `auth.login_failed` with the e-mail typed and the IP.

**Simple explanation:** five wrong guesses from one place lock that place out for 15 minutes. Twenty wrong guesses from anywhere lock the account out, so a botnet cannot guess slowly from many addresses.

**Technical explanation:** both counters use Laravel's `RateLimiter` on the cache store (`CACHE_STORE=database` in production). The per-account counter is intentionally not cleared when a legitimate sign-in succeeds from another IP.

Residual risk: an attacker who knows an e-mail address can lock that account out for 15 minutes by submitting 20 failures. Password reset by e-mail still works during that time.

### Default-password blocking

`BLOCK_DEFAULT_PASSWORDS` (`config/hub.php`, default `false`) refuses sign-in with any password in `SecureDemoAccounts::WEAK` (`password`, `secret`, `12345678`, `password123`, `admin`, `changeme`). The check runs before the credential check, counts as a failed attempt, writes `auth.default_password_refused`, and shows the same message for every account.

The container entrypoint also runs `php artisan hub:secure-demo-accounts` on every boot. It replaces default passwords on existing accounts with `SEED_PASSWORD` when that value is strong (12+ characters, mixed case, a number). If `SEED_PASSWORD` is weak the command warns and leaves accounts unchanged.

> **Warning:** `render.yaml` does not set `BLOCK_DEFAULT_PASSWORDS`. On the hosted demo, blocking is on only if someone sets it in the Render dashboard. Verify in the dashboard; the repository cannot show it.

## One-time e-mail codes

Implemented in [`VerificationService`](../../app/Services/VerificationService.php) and the model [`VerificationCode`](../../app/Models/VerificationCode.php). Used for e-mail verification (`verify_email`) and password reset (`reset_password`).

| Property | Value |
| --- | --- |
| Format | 6 digits from `random_int(100000, 999999)` |
| Storage | `Hash::make($code)` in `code_hash`, hidden from serialisation; never stored in plain text, session or logs by the application |
| Expiry | 10 minutes (`EXPIRY_MINUTES`) |
| Guesses | 5 per code (`MAX_ATTEMPTS`); the sixth marks the code consumed |
| Resend | 1 per 60 seconds per user and purpose (`RateLimiter` key `otp:{purpose}:{user id}`) |
| Replacement | Issuing a code consumes earlier unconsumed codes of the same purpose |
| Atomicity | `verify()` and `consumeLink()` run in a DB transaction with `lockForUpdate()`, so parallel guesses are counted one by one and a code cannot be used twice |
| Delivery | Sent synchronously with `Mail::send`, so no queue worker is needed |

Route limits: `POST /forgot-password` uses the `password-codes` limiter (3/min/IP and 10/hour per `email|ip`). `POST /reset-password` is `throttle:10,1`. `POST /verify-email` is `throttle:10,1`; `POST /verify-email/resend` is `throttle:5,10`.

Password reset responds the same way whether or not the e-mail has an account. Residual risk: for an existing account the request sends mail synchronously, so the response may be slower than for an unknown address (timing difference). Not mitigated.

> **Warning:** with `MAIL_MAILER=log` (the value in `render.yaml`) the mail, including the code and the signed link, is written to the application log. Anyone who can read Render logs can verify accounts or reset passwords. Use a real mailer in any deployment with real users.

## Signed e-mail links

Each code e-mail also contains a button: `URL::temporarySignedRoute('verification.link' | 'password.link', expiry, ['user' => id, 'record' => id])`.

- Routes: `GET|POST email/verify/{user}/{record}` and `GET|POST password/reset/{user}/{record}`, middleware `signed,throttle:10,1`.
- `GET` only shows a confirmation page (so mail scanners that pre-open links do not consume them). The change happens on `POST` ([`EmailLinkController`](../../app/Http/Controllers/Auth/EmailLinkController.php)).
- The link and the code share one record. Using either consumes both (`consumeLink`). A link replaced by a newer code, expired, or tampered with returns the `auth.link-expired` page with HTTP 403.
- Reset links check `is_active` and validate the new password before consuming the record. A reset revokes other sessions and e-mails a password-changed notice.
- The page shows a masked address (`EmailLinkController::mask`).

Unsubscribe links (`/unsubscribe/{user}`) are signed but **not time-limited** (`URL::signedRoute` in `SendBroadcast`). `POST unsubscribe/{user}` is exempt from CSRF in `bootstrap/app.php` so mail providers can send RFC 8058 one-click requests; the signature authorises the request instead. Effect of a leaked link: that member is unsubscribed from announcements. Nothing else.

## Sessions and cookies

| Setting | Value | Source |
| --- | --- | --- |
| Driver | `database` (`SESSION_DRIVER`) | `config/session.php`, `render.yaml` |
| Lifetime | 120 minutes idle (`SESSION_LIFETIME`) | same |
| `Secure` | `SESSION_SECURE_COOKIE`: `true` in `render.yaml`, `false` in `.env.example` | same |
| `HttpOnly` | `true` (`SESSION_HTTP_ONLY`) | `config/session.php` |
| `SameSite` | `lax` | `config/session.php` |
| Encryption | `SESSION_ENCRYPT=false` | `.env.example` |
| Cookie name | Slug of `APP_NAME` + `-session` | `config/session.php` |

Fixation: the ID is regenerated on sign-in, sign-up, and password change (`Portal/ProfileController`, `Workspace/ProfileController`).

Revocation: `UserService::revokeOtherSessions` rotates the remember token and deletes the user's rows in the `sessions` table (database driver only). It runs on deactivation, deletion, admin password reset, own password change, reset by code or link, and "sign out other devices". Middleware [`EnsureAccountIsActive`](../../app/Http/Middleware/EnsureAccountIsActive.php) also signs out a deactivated user on the next request, whatever the driver.

`php.ini` in the container sets `session.cookie_httponly=1`, `session.use_strict_mode=1` and `session.upload_progress.enabled=0`.

## Password policy

- Production (`APP_ENV=production`): `Password::min(12)->letters()->mixedCase()->numbers()`. Other environments: minimum 8. Source: `AppServiceProvider::boot()`.
- No breached-password check (`uncompromised()`) and no maximum age. Recommended, not implemented.
- Own-password changes need the current password (`staff_change_password_needs_current_password...` in `AdministrationTest`).
- Staff accounts are created by the Hub Director (`UserService::create`), who sets the initial password. A Hub Director can send a reset e-mail instead of choosing a password (`workspace.users.password-reset`).
- The first administrator comes from `ADMIN_EMAIL`, `ADMIN_NAME`, `ADMIN_PASSWORD` through `php artisan hub:create-admin`. The command is idempotent.

## Gaps

| Gap | Impact | Status |
| --- | --- | --- |
| No two-factor authentication | A stolen password gives full access to that role | Recommended, not implemented. Priority: Hub Director and Programme Officer |
| Per-account lockout can be abused to lock out a user for 15 minutes | Availability only | Accepted trade-off |
| Reset request timing differs for existing accounts | Account enumeration by timing | Not mitigated. Queue the mail or add a fixed delay |
| Unsubscribe links never expire | Low | Accepted |
| `MAIL_MAILER=log` on the hosted demo | Codes in logs | Configuration choice |
| Failed sign-in audit rows store the typed e-mail, which can be a mistyped password | Low | Not mitigated |
