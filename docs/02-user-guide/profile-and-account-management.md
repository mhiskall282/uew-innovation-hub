# Profile and account management

> **Audience:** Members (staff section at the end) · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Account registration](account-registration.md), [Login and authentication](login-and-authentication.md), [E-mail preferences and unsubscribe](feature-guides/email-preferences-and-unsubscribe.md), [Badges and leaderboard](feature-guides/badges-and-leaderboard.md), [Data protection](../08-security/data-protection.md)

This page explains the **Profile & settings** page: what each field is for, who sees it, how to change your password, and how to control your privacy choices.

- **Where:** account menu, **Profile & settings**, or `/portal/profile`.
- **Who:** signed-in members with a finished profile.

## Edit your profile

1. Open `/portal/profile`.
2. Change the fields you want in the three blocks: **Contact & personal details**, **Background**, **Your interests**, and the **Communication** choices.
3. Press **Save profile**.

Expected result: "Your profile was updated." Changes are written to the audit log.

Your **e-mail address is shown but cannot be edited** here. The right-hand card **Account** shows your e-mail, the date it was verified and the date you joined.
> **Requires confirmation:** the member portal has no screen to change a verified e-mail address. Ask the Hub through the [contact form](feature-guides/contact-and-enquiries.md). Staff are told to ask the Hub Director.

### Fields, rules and who sees them

| Field | Rule | Why it is collected | Who sees it |
| --- | --- | --- | --- |
| Full name | Required, 2 to 255 characters | Identifies you | Reviewers. Public only if you switch on the public profile |
| Phone number | Required, 7 to 30 characters of digits, spaces and `+ ( ) -` | Contact about applications | Reviewers |
| Gender | Required | Eligibility and anonymous reports to funders | Not shown to reviewers |
| Date of birth | Required, at least 15 years ago, after 1920 | Eligibility and anonymous age bands | Reviewers see your age |
| Region you live in | Required, one of the listed regions or Outside Ghana | Eligibility and reports | Reviewers |
| I am a... | Required: UEW student, UEW staff or faculty, UEW alumni, Partner organisation, Member of the public | Reviewer context | Reviewers, and public if you opt in |
| Highest education level | Required | Eligibility and reviewer context | Reviewers |
| Institution or organisation | Optional, up to 255 characters | Reviewer context | Reviewers, and public if you opt in |
| Department, faculty or programme of study | Optional, up to 255 characters | Reviewer context | Reviewers |
| Do you have a disability? | Optional: No, Yes, Prefer not to say | Plan accessible programmes, report on inclusion | Not shown to reviewers |
| Short bio | Optional, up to 1,000 characters | Introduce yourself | Public if you opt in |
| Interests | Required, 1 to 12 choices | Recommend programmes and target announcements | You and the Hub |

Your gender, date of birth and disability answer are kept in a snapshot when you submit an application. Changing your profile later does not change what reviewers already assessed.

### Validation messages

| Message | Fix |
| --- | --- |
| "Enter a valid phone number, for example +233 24 123 4567." | Use only digits, spaces, `+`, brackets and hyphens. |
| "You need to be at least 15 years old." | Check the date of birth. |
| "Choose at least one area you are interested in." | Tick at least one box. |
| "Please check the highlighted fields." | Scroll up. Each problem field shows its reason in red. The yellow banner does not list every field, so look for red text under each input. |

## Privacy and consent choices

| Choice | Setting name | Default | Effect |
| --- | --- | --- | --- |
| E-mail me when new programmes and events that match my interests are launched | `marketing_opt_in` | Ticked on the first onboarding page, afterwards whatever you saved | Announcement e-mails. E-mails about your own account and applications are always sent |
| Show me on the public community page and leaderboard | `public_profile` | Off | Shows your name, affiliation, institution, bio, XP and badges. E-mail, phone and personal details are never shown. You must also have more than 0 XP to be listed |

To disappear from the public page, untick the box and press **Save profile**. The profile page `/community/<id>` then returns "page not found".

The Hub handles personal data under Ghana's Data Protection Act, 2012 (Act 843). The privacy notice is on the website at `/privacy`.
> **Requires confirmation:** the member portal has no "delete my account" or "download my data" button. Requests have to go to the Hub through the contact form.

## Profile completeness

The **Profile completeness** bar on the right and the **Profile** card on My account show the share of these 11 items you filled in: name, phone, gender, date of birth, region, affiliation, institution, education level, bio, interests and verified e-mail. Institution and bio are optional on the form but count towards the bar.

## Change your password

1. On `/portal/profile`, find the card **Change password**.
2. Type your **Current password**, the **New password** and **Confirm new password**.
3. Press **Update password**.

Expected result: "Your password was changed. Other devices have been signed out." Other devices and "keep me signed in" sessions end. The Hub also e-mails you that the password changed.

| Message | Fix |
| --- | --- |
| "Your current password is incorrect." | Retype the current password. If you forgot it, sign out and use **Forgot password?**. |
| "Choose a password you have not used for this account." | The new password must differ from the current one. |
| "The password field confirmation does not match." | Type the same new password twice. |
| Strength message | Production needs 12 or more characters, upper and lower case letters and a number. Other environments need 8. |

## Sign out and sessions

Sign out from the menu under your name. See [Login and authentication](login-and-authentication.md).

## For staff

Staff do not use `/portal/profile`. Staff open **Account settings** (`/workspace/account`) from the left menu, under **You**.

- Edit **Full name** and **Department or office**. The e-mail address is read-only: "Ask the Hub Director to change your e-mail or role."
- Switch the e-mail alerts **New programme applications** and **New contact-form enquiries** on or off, then press **Save profile**.
- Change the password with **Update password**. It needs the current password and signs out your other devices.
- **Sign out other devices** ends all your other sessions. It asks you to confirm with your password. The list of sessions needs the database session driver.

Staff cannot change their own role. See [User roles and permissions](user-roles-and-permissions.md).
