# E-mail preferences and unsubscribe

> **Audience:** Members · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Profile and account management](../profile-and-account-management.md), [Notifications guide](../notifications-guide.md), [Account registration](../account-registration.md), [Data protection](../../08-security/data-protection.md)

This page explains which e-mails the Hub sends, how to stop announcement e-mails with the unsubscribe link or the settings page, and how to switch them back on.

- **What it does:** lets you control announcement e-mails (new programmes and events).
- **Why:** so you only receive e-mail you want. The choice is also a consent record under Ghana's Data Protection Act, 2012 (Act 843).
- **Who:** members. Staff have their own e-mail alert settings, see [Profile and account management](../profile-and-account-management.md#for-staff).
- **Where:** the unsubscribe link at the bottom of an announcement e-mail (`/unsubscribe/<number>` with a signed address), or the tick box in **Profile & settings** (`/portal/profile`).

## Two kinds of e-mail

| Kind | Examples | Can you stop it? |
| --- | --- | --- |
| **Account and application e-mails** | Verification code, password reset code, welcome e-mail, "application submitted" confirmation, status change e-mails, "password changed" notice | No. These are needed to run your account |
| **Announcements** | New programmes and events that match your interests, sent by staff as a broadcast | Yes. This is the preference described here |

Messages about your own applications are always sent, even when you have unsubscribed from announcements.

## Unsubscribe from an e-mail

1. Open the announcement e-mail and press its **Unsubscribe** link.
2. A page opens: "Stop announcement e-mails?" It shows your masked address, for example `a•••••@gmail.com`, and says "You'll still get e-mails about your own account and applications."
3. Press **Unsubscribe**.
4. The page "You have been unsubscribed" confirms it.

You do not need to sign in. The link is a signed address made for you. Nobody can change another person's setting by guessing the number, because a wrong or edited link is refused with a 403 page.

**Simple explanation:** opening the link only shows a question. Pressing the button does the change. This stops mail scanners that open every link in an e-mail from unsubscribing you by accident.

**Technical explanation:** the link is a Laravel signed route. `GET` shows the page. `POST` to the same signed address unsubscribes. The mail also carries the standard one-click `List-Unsubscribe` headers, so Gmail and Yahoo can show their own **Unsubscribe** button. That request needs no page, and the signature authorises it.

If you already unsubscribed, the page says "You're already unsubscribed".

Limit: 20 unsubscribe requests a minute from one connection.

## Change the setting in your profile

1. Sign in and open **Profile & settings** (`/portal/profile`).
2. Under **Communication**, tick or untick "E-mail me when new programmes and events that match my interests are launched".
3. Press **Save profile**. You see "Your profile was updated."

To switch announcements **back on** after unsubscribing, use this tick box. The unsubscribe pages say the same: "Changed your mind? Switch announcements back on in your profile settings." That link needs you to be signed in.

## The setting on the first profile page

New members see the box already ticked on the onboarding page (`/onboarding`, "Step 3 of 3"). Untick it before you press **Finish and go to my account** if you do not want announcements. The default only applies on that first page. After that, your last saved choice is used.

Choosing interests also affects what the **Recommended for you** card shows. It is separate from announcement e-mails.

## Who sends announcements

Programme Officers and the Hub Director send broadcasts from the workspace. A broadcast goes only to members who have the announcement setting ticked. The workspace has an audience count screen. See the [administration section](../../03-administration/administrator-overview.md).

## Errors and fixes

| What you see | Cause | Fix |
| --- | --- | --- |
| "You do not have access" (403) on the unsubscribe link | The link was changed, cut short or broken by a mail program | Copy the whole link from the e-mail, or untick the box in your profile |
| "Page not found" on the unsubscribe link | The account was deleted | None needed |
| Still receiving announcements after unsubscribing | You may have two accounts with different addresses, or the e-mail was sent before you unsubscribed | Unsubscribe from each address. Allow a short time for queued mail |
| The page says "Changed your mind?" and the link asks me to sign in | The profile setting needs a sign-in | Sign in, then tick the box |
| No more e-mails at all, including codes | Account e-mails are not affected by this setting | Check spam, and the address on your profile. See [Common user issues](../common-user-issues.md) |

Staff e-mail alerts (**New programme applications**, **New contact-form enquiries**) are set in the workspace under **Account settings**.
