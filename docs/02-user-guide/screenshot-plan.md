# Screenshot plan

> **Audience:** Documentation maintainers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Documentation home](../README.md), [Feature guides](feature-guides/README.md), [Getting started](getting-started.md), [Dashboard guide](dashboard-guide.md)

**No screenshots are included yet.** None of the user guide pages reference an image file. This page lists the screens worth capturing, what to show in each, and the rules to follow, so a maintainer can add them later without hunting for the right screen.

## Capture rules

1. Use the live site or a local site with demo data (see [`DemoContentSeeder.php`](../../database/seeders/DemoContentSeeder.php)). Never use real people's data.
2. Use a desktop window about 1280 pixels wide. For the mobile menu use 390 pixels.
3. Hide or blur e-mail addresses, phone numbers and names that belong to real people. Never show a password, a real verification code or a signed link.
4. Do not show the address bar if it contains a signed link or an application reference of a real person.
5. Save images as PNG under `docs/02-user-guide/images/` (this folder does not exist yet), named after the screen, for example `sign-up-form.png`. Add the image link in the target page only after the file is committed.
6. Write alt text that says what the screen shows.
7. Recapture after any change to the screen, and update **Last verified** on the page.

## Screens to capture

| # | Screen | Address | Role needed | What to highlight | Target page |
| --- | --- | --- | --- | --- | --- |
| 1 | Home page | `/` | None | Top menu, **Sign in**, **Create account** buttons, "Open opportunities" | [Getting started](getting-started.md) |
| 2 | Programmes list, **Open now** tab | `/programmes` | None | Tabs with counts, type chips, a programme card | [Browsing the public website](feature-guides/browsing-the-public-website.md) |
| 3 | Programme page with **Create an account to apply** | `/programmes/<programme>` | None (signed out) | Availability badge, "What you will be asked", side panel button | [Browsing the public website](feature-guides/browsing-the-public-website.md) |
| 4 | Sign-up form | `/sign-up` | None | "Step 1 of 3", all fields, password hint, terms box, gold button | [Account registration](account-registration.md) |
| 5 | Sign-up form with errors | `/sign-up` | None | Red messages (e-mail exists, phone format) | [Account registration](account-registration.md) |
| 6 | Verify e-mail | `/verify-email` | Unverified member | "Step 2 of 3", the 6-digit box, **Send a new code**, **Use a different account** | [Account registration](account-registration.md) |
| 7 | E-mail link confirmation page | signed link from the e-mail | None | "Confirm your e-mail" button. Hide the address bar | [Account registration](account-registration.md) |
| 8 | Onboarding | `/onboarding` | New member | "Step 3 of 3", progress dots, interests, the two tick boxes | [Account registration](account-registration.md) |
| 9 | Sign-in page | `/sign-in` | None | Fields, **Keep me signed in**, **Forgot password?** | [Login and authentication](login-and-authentication.md) |
| 10 | Sign-in lockout message | `/sign-in` | None | "Too many sign-in attempts. Try again in 15 minute(s)." | [Login and authentication](login-and-authentication.md) |
| 11 | Forgot password | `/forgot-password` | None | E-mail field, **Send reset code** | [Login and authentication](login-and-authentication.md) |
| 12 | Reset password by code | `/reset-password` | None (after step 11) | Code box, new password fields | [Login and authentication](login-and-authentication.md) |
| 13 | My account (Overview) | `/portal` | Member | Four stat cards, tab bar, **Recommended for you**, **Your level** | [Dashboard guide](dashboard-guide.md) |
| 14 | Account menu | any page | Member | Menu items under the name, the bell | [Dashboard guide](dashboard-guide.md) |
| 15 | Application form | `/programmes/<programme>/apply` | Member | Applicant card, numbered questions, "Filled from your profile", PDF upload, **Submit** card with **Save draft** | [Programme applications](feature-guides/programme-applications.md) |
| 16 | Form with validation errors | same | Member | The "Some answers need attention" alert and red outlines | [Programme applications](feature-guides/programme-applications.md) |
| 17 | My applications list | `/portal/applications` | Member | Status badges, **Continue** and **View** buttons | [Programme applications](feature-guides/programme-applications.md) |
| 18 | Application details | `/portal/applications/<id>` | Member | Reference, four-step bar, status message, **Withdraw** card | [Programme applications](feature-guides/programme-applications.md) |
| 19 | Messages box | `/portal/applications/<id>#messages` | Member | Both bubble colours, **Send message** | [Application messages](feature-guides/application-messages.md) |
| 20 | Progress reports page | `/portal/reports` | Member with an accepted application | Two cards, info box "What goes in a report?" | [Progress reports](feature-guides/progress-reports.md) |
| 21 | New report form, budget table | `/portal/applications/<id>/reports/create` | Accepted member | Spending lines, **Budget at a glance** | [Progress reports](feature-guides/progress-reports.md) |
| 22 | Report with "Changes requested" | `/portal/reports/<reference>` | Accepted member | Feedback box, **Make changes** | [Progress reports](feature-guides/progress-reports.md) |
| 23 | Bell panel open | any page | Member | Unread count, **Mark all read**, **See all notifications** | [Notifications guide](notifications-guide.md) |
| 24 | Notifications page | `/notifications` | Member | **All** and **Unread** tabs | [Notifications guide](notifications-guide.md) |
| 25 | Profile & settings | `/portal/profile` | Member | Consent boxes, **Account** card, completeness bar, **Change password** | [Profile and account management](profile-and-account-management.md) |
| 26 | Community leaderboard | `/community` | None | Ranked list, **How XP works**, recent badges | [Badges and leaderboard](feature-guides/badges-and-leaderboard.md) |
| 27 | Public member profile | `/community/<id>` | None | Rank, XP, badges | [Badges and leaderboard](feature-guides/badges-and-leaderboard.md) |
| 28 | Gallery list | `/gallery` | None | Album cards with photo counts | [Gallery](feature-guides/gallery.md) |
| 29 | Gallery viewer | `/gallery/<album>` | None | Photo viewer with arrows | [Gallery](feature-guides/gallery.md) |
| 30 | Contact page | `/contact` | None | Contact panel and the form | [Contact and enquiries](feature-guides/contact-and-enquiries.md) |
| 31 | "Message sent" panel | `/contact` after sending | None | Green confirmation | [Contact and enquiries](feature-guides/contact-and-enquiries.md) |
| 32 | Unsubscribe confirmation | signed link from a broadcast | None | "Stop announcement e-mails?" and the button. Hide the address bar | [E-mail preferences and unsubscribe](feature-guides/email-preferences-and-unsubscribe.md) |
| 33 | Workspace dashboard, Hub Director | `/workspace` | Hub Director | Left menu, all cards | [Dashboard guide](dashboard-guide.md) |
| 34 | Workspace dashboard, Reviewer | `/workspace` | Reviewer | Shorter menu, **Waiting for review** | [Dashboard guide](dashboard-guide.md) |
| 35 | Workspace dashboard, Read-only Staff | `/workspace` | Read-only Staff | Shortest menu, **Partners** card | [Dashboard guide](dashboard-guide.md) |
| 36 | Workspace **Account settings** | `/workspace/account` | Any staff | Two e-mail alert boxes, password card, **Sign out other devices** | [Profile and account management](profile-and-account-management.md) |
| 37 | 403 page | any staff page opened as a member | Member | "You do not have access" | [User roles and permissions](user-roles-and-permissions.md) |
| 38 | Mobile menu | any page, 390 px wide | None | Drawer with menu items | [Getting started](getting-started.md) |

## Suggested order

Capture screens 4 to 8 in one session with a new demo account, then 13 to 25 with a member who has a submitted application and an accepted one. The seeded demo accounts are listed in `database/seeders/DemoContentSeeder.php`. The password comes from `SEED_PASSWORD`.

## Status

| Item | State |
| --- | --- |
| Screenshots captured | 0 of 38 |
| `docs/02-user-guide/images/` folder | Not created |
| Image links in guides | None |
