# Login and authentication

> **Audience:** Members and staff · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Account registration](account-registration.md), [Profile and account management](profile-and-account-management.md), [Common user issues](common-user-issues.md), [Authentication architecture](../04-architecture/authentication-architecture.md), [Authentication security](../08-security/authentication-security.md)

This page covers signing in, signing out, staying signed in, lockouts and rate limits, and resetting a forgotten password by code or by e-mail link.

## Sign in

- **Where:** **Sign in** at the top right, or `/sign-in`. The addresses `/login`, `/signin` and `/workspace/sign-in` redirect there.
- **Who:** members and staff use the same page.

Steps:

1. Open `/sign-in` ("Welcome back").
2. Type your e-mail address and password. The eye icon shows or hides the password.
3. Tick **Keep me signed in** on your own device if you want a long-lasting session.
4. Press **Sign in**.

Where you land:

| Account | Condition | Landing page |
| --- | --- | --- |
| Anyone | E-mail not yet verified | `/verify-email` |
| Member | Profile not finished | `/onboarding` |
| Member | Normal | `/portal` (My account), or the page you were trying to open |
| Staff | Normal | `/workspace`, or the page you were trying to open |

A member is never sent into the staff workspace, even if an old link pointed there.

## Messages on the sign-in page

| Message | Cause and fix |
| --- | --- |
| "These details do not match an active account." | Wrong password, unknown e-mail, or a deactivated account. The site gives one answer for all three on purpose. Check the spelling, or reset the password. |
| "Too many sign-in attempts. Try again in 15 minute(s)." | You hit the lockout, see below. Wait for the time shown. |
| "This password is not allowed on this site. Use "Forgot password?" to set a new one, or ask the Hub administrator." | The site refuses well-known default passwords. Reset the password. This protection only applies when `BLOCK_DEFAULT_PASSWORDS` is on. |
| "Your account has been deactivated. Contact the Hub Director if this is unexpected." | A staff member switched your account off while you were signed in. Contact the Hub Director. |
| "This page expired" (419) | The form stayed open too long. Go back, refresh and try again. |

## Lockouts and limits

| Rule | Value | Source |
| --- | --- | --- |
| Failed sign-ins per e-mail and connection | 5, then locked for 15 minutes | `LoginRequest` |
| Failed sign-ins per account from all connections together | 20, then locked for 15 minutes | `LoginRequest` |
| A successful sign-in | Clears the first counter, not the second | `LoginRequest` |
| Sign-in form posts per connection | 10 per minute | `routes/web.php` |
| Sign-up form posts | 5 per minute and 30 per day per connection | `AppServiceProvider` |
| "Forgot password" requests | 3 per minute per connection, and 10 per hour per e-mail and connection | `AppServiceProvider` |
| Reset form posts | 10 per minute | `routes/web.php` |
| E-mail code checks | 10 per minute | `routes/web.php` |
| "Send a new code" | 5 per 10 minutes, and once every 60 seconds per account | `routes/web.php`, `VerificationService` |

If you see "Too many requests" (429), wait a minute and try again. Every failed sign-in is written to the audit log.

## Stay signed in and sign out

- Without **Keep me signed in**, the session ends after 120 minutes without activity. This is the `SESSION_LIFETIME` default in `.env.example`. The Hub's hosting may set a different value.
- To sign out, open the menu under your name at the top right and choose **Sign out**. On a phone, open the menu icon and choose **Sign out**.
- Changing your password signs you out on every other device.

## Forgot your password

There are two ways. Both start on the same page.

1. On `/sign-in` choose **Forgot password?** (`/forgot-password`).
2. Enter your e-mail address and press **Send reset code**.
3. The page always shows the same message: "If an account uses that address, we have sent it a 6-digit code." It does not say whether the address has an account.

### Reset by code

1. You arrive on `/reset-password` ("Choose a new password"). It remembers your e-mail address in this browser. Use the same browser and tab as in step 2.
2. Type the 6-digit code and the new password twice. Press **Reset password**.
3. You see "Your password was reset. Sign in with your new password."

### Reset by e-mail link

1. Open the reset e-mail and press its button.
2. A page "Choose a new password" opens for your masked address, for example `a•••••@gmail.com`. Type the new password twice and press **Save new password**.
3. You see "Your password was changed. Sign in with your new password."

Opening the link only shows the form. The password changes only when you press the button. A weak password does not use up the link.

| Rule | Value |
| --- | --- |
| Code and link lifetime | 10 minutes |
| Wrong code guesses | 5, then "Too many incorrect attempts. Ask for a new code." |
| Reset e-mail frequency | One new code per 60 seconds. If you ask too soon, the page still says the code was sent |
| New password, production | At least 12 characters, upper and lower case letters, a number |
| New password, development and test | At least 8 characters |
| Side effect | You are signed out on every device. The Hub also treats the e-mail as verified |
| Deactivated accounts | Receive no code. A link for a deactivated account returns "page not found" |

Messages you may see:

| Message | Fix |
| --- | --- |
| "This code has expired. Ask for a new one." | Go to `/forgot-password` and start again. The same message appears if your session lost the e-mail address. |
| "That code is not correct. 2 attempt(s) left." | Check the e-mail, type the newest code. |
| "The password field confirmation does not match." | Type the same password in both boxes. |
| "This link has expired or has already been used. Ask for a new one." | Press **Request a new link** on the page. |
| The e-mail never arrives | See [Common user issues](common-user-issues.md). |

## Change your password while signed in

See [Profile and account management](profile-and-account-management.md).

## Why it works this way

**Simple explanation:** the code or link proves you can read the e-mail inbox of the account. The site never shows the code on screen.

**Technical explanation:** `VerificationService` stores codes as bcrypt hashes, allows 5 guesses, and locks the row while counting. Each e-mail button is a signed, temporary URL for one code record. Details: [Authentication architecture](../04-architecture/authentication-architecture.md).
