# Common user issues

> **Audience:** Members and staff · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [User FAQ](user-faq.md), [Login and authentication](login-and-authentication.md), [Account registration](account-registration.md), [Authentication problems](../11-troubleshooting/authentication-problems.md), [Admin troubleshooting](../03-administration/admin-troubleshooting.md)

This page lists the problems people meet most often, grouped by task, with the exact message, the cause and the fix. Messages are copied from the code. If a message is not listed here, note the page address and the time, then use the [contact form](feature-guides/contact-and-enquiries.md).

## Sign-up and e-mail codes

| Problem or message | Cause | Fix |
| --- | --- | --- |
| The 6-digit e-mail never arrives | Spam or promotions folder, a typing mistake in the address, or the site's e-mail is not set up | Look in spam. Check the address on the "Check your inbox" page. Wait one minute and press **Send a new code**. If it still fails, contact the Hub: it may be an e-mail delivery problem on the server |
| "A code was sent recently. You can ask for a new one in 37 seconds." | A new code can be requested once every 60 seconds | Wait for the time shown |
| "This code has expired. Ask for a new one." | Codes last 10 minutes | **Send a new code** |
| "That code is not correct. 2 attempt(s) left." | Mistyped digits | Retype. An older code stops working once you ask for a new one, so use the newest e-mail |
| "Too many incorrect attempts. Ask for a new code." | Five wrong guesses | **Send a new code** |
| "Enter the 6-digit code from the e-mail." | The box has letters or the wrong number of digits | Enter exactly 6 digits. Spaces are fine |
| "An account with this e-mail already exists. Sign in or reset your password." | You registered before | Sign in or use **Forgot password?** |
| "You need to be at least 15 years old to create an account." | Date of birth too recent | Check the date |
| "Enter a valid phone number, for example +233 24 123 4567." | Letters or too few digits in the phone box | Use digits, spaces and `+ ( ) -`, 7 to 30 characters |
| "New registrations are paused at the moment." | The Hub Director paused sign-ups | Try again later |
| "Your sign-up could not be processed." | A hidden spam field was filled, often by a form-filling browser extension | Turn the extension off and try again |
| I made the account with the wrong e-mail | The e-mail on an account cannot be edited by members | Register again with the right address, or contact the Hub |
| The link in the e-mail says "This link has expired" | Links work once and last 10 minutes. A newer e-mail replaces older ones | Sign in. You are taken to `/verify-email`. Ask for a new code |

## Signing in

| Problem or message | Cause | Fix |
| --- | --- | --- |
| "These details do not match an active account." | Wrong e-mail or password, or the account is switched off | Check spelling and caps lock. Use the eye icon to show the password. Use **Forgot password?** |
| "Too many sign-in attempts. Try again in 15 minute(s)." | 5 wrong tries from your connection, or 20 across all connections for this account | Wait. Then use **Forgot password?** instead of guessing |
| "This password is not allowed on this site. Use "Forgot password?"..." | A well-known default password is blocked | Reset the password |
| "Your account has been deactivated." | Staff switched the account off | Contact the Hub Director |
| Sent to `/verify-email` after signing in | The e-mail is not verified yet | Enter the code, or ask for a new one |
| Sent to `/onboarding` after signing in | The profile page was never finished | Finish it. It cannot be skipped |
| Signed out after a while | Session ends after 120 minutes without use (default) | Tick **Keep me signed in** on your own device |
| "This page expired" (419) | The page stayed open too long, or cookies are blocked | Refresh and try again. Allow cookies for the site |
| "Too many requests" (429) | A rate limit | Wait a minute |

## Password reset

| Problem or message | Cause | Fix |
| --- | --- | --- |
| The page says a code was sent, but nothing arrives | The page gives the same answer for every address. The address may have no account, or the account is switched off, or you asked again within 60 seconds | Check the spelling. Check spam. Wait a minute and try once more |
| Opening `/reset-password` jumps back to `/forgot-password` | The browser has not asked for a code in this session | Start again from **Forgot password?** in the same browser |
| "This code has expired. Ask for a new one." | 10 minutes passed or the session lost the e-mail | Request a new code |
| Reset by e-mail button: "This link has expired or has already been used." | Used once or replaced by a newer e-mail | **Request a new link** |
| Password rejected as weak | Production needs 12 or more characters, upper and lower case letters and a number | Choose a longer password |

## Applications

| Problem or message | Cause | Fix |
| --- | --- | --- |
| No **Apply now** button | Not signed in, the programme is not open, closed, or full, or you are staff | Read the line under the programme details. Staff cannot apply |
| "This programme is not accepting applications right now." | Not open, closed or full | See the dates on the programme page |
| "Some answers need attention" | A question failed a rule | Fix each red outlined question. Other answers are kept |
| "The <question> must be a file of type: pdf." | The upload is not a PDF | Export to PDF |
| "The <question> must not be greater than 20480 kilobytes." | File over the limit (default 20 MB) | Compress the PDF |
| "Confirm that your answers are accurate before submitting." | The declaration is not ticked | Tick it |
| "This application has already been submitted and can no longer be changed." | Submitted applications are locked | Send a message from the application page |
| "You have already applied to this programme." | A submitted or withdrawn application exists | Open it from **My applications**. A withdrawn application cannot be reopened |
| I cannot see a **Withdraw** button | Only Submitted and Under review applications can be withdrawn | "This application can no longer be withdrawn." appears if you force it |
| My answers did not save | The page expired or you pressed Submit with errors | Press **Save draft** often. Keep long answers in a document and paste them in |
| The Messages box is missing | Drafts have no messages | Submit first |

## Progress reports

| Problem or message | Cause | Fix |
| --- | --- | --- |
| No **New report** button | Your application is not Accepted | Wait for the decision |
| "Only accepted applicants can report on this programme." | Application not Accepted, or not yours | Check the status |
| "This report is being reviewed and can no longer be changed." | Awaiting review or Accepted | Send a message to ask the Hub to reopen it |
| "Spending is above funds received. Explain why under Challenges." | Warning, not an error | Add a note under Challenges |

## Notifications, e-mail and privacy

| Problem or message | Cause | Fix |
| --- | --- | --- |
| No bell icon | E-mail not verified | Verify it |
| I get announcement e-mails I do not want | Announcements are on | Press **Unsubscribe** in the e-mail or untick the box in **Profile & settings** |
| I do not get e-mails about a status change | Staff choose whether to e-mail status changes | Check the bell and **My applications** |
| Not on the leaderboard | Public profile off, or 0 XP, or outside the top 30 | See [Badges and leaderboard](feature-guides/badges-and-leaderboard.md) |
| The unsubscribe link gives a 403 page | The link was broken by a mail program | Use the full link, or untick the box in your profile |

## Pages and permissions

| Problem or message | Cause | Fix |
| --- | --- | --- |
| "You do not have access" (403) | Your role does not hold the permission, or you are a member opening a staff page | Go back. Ask the Hub Director if you are staff |
| "Page not found" (404) | Wrong address, unpublished or private page | Use the menu |
| A staff menu item is missing | Menu items depend on your role | See [User roles and permissions](user-roles-and-permissions.md) |
| Work lost when a page expired | Sessions end after the lifetime without activity | Save drafts early |

## What to send the Hub when you ask for help

1. Your account e-mail address (never your password).
2. The application reference, for example `UEW-AP-2026-7K3QX9`, or the report reference `UEW-PR-2026-K4M7QX`.
3. The page address and the exact message.
4. The time it happened.
