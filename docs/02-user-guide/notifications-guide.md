# Notifications guide

> **Audience:** Members and staff · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Dashboard guide](dashboard-guide.md), [E-mail preferences and unsubscribe](feature-guides/email-preferences-and-unsubscribe.md), [Application messages](feature-guides/application-messages.md), [Common user issues](common-user-issues.md)

This page explains the bell icon and the notifications page: what triggers a notification, how to read, open and clear them, and how in-app notifications differ from e-mail.

- **What it does:** shows short updates about your own account inside the site.
- **Who:** every signed-in person with a verified e-mail, members and staff. You only ever see your own.
- **Where:** the bell icon in the top bar, **See all notifications** at the bottom of the bell panel, the **Notifications** tab of My account, or `/notifications`.

## The bell

1. Look at the bell at the top right. A red number shows how many notifications are unread (it shows `99+` above 99).
2. Press the bell. A panel lists the 5 latest notifications. Unread ones have a blue background and a dark round icon.
3. Press a notification. It is marked as read and you go to the related page, for example your application.
4. Press **Mark all read** in the panel to clear the red number.
5. Press **See all notifications** for the full list.

The bell does not appear until your e-mail is verified.

## The notifications page

`/notifications` shows 20 per page, newest first.

| Control | What it does |
| --- | --- |
| **All** | Every notification |
| **Unread (3)** | Only unread ones, with the count |
| **Mark all as read** | Marks every unread notification as read and shows "All notifications marked as read." |
| Press a row | Marks that notification read and opens its link |

Empty states: "No notifications yet. We'll let you know here when something changes." and "No unread notifications". There is no way to delete a single notification, and old ones are not removed automatically.
> **Requires confirmation:** the code has no scheduled clean-up of old notifications (`routes/console.php` has no tasks).

## What creates a notification

### For members

| Trigger | Title you see | Opens |
| --- | --- | --- |
| You submit an application | "Application submitted", with your reference and "+10 XP" | Your application |
| A reviewer changes the status of your application | "<Programme>: <new status>", with the standard status text | Your application |
| Hub staff send you a message about an application | "New message about <Programme>" with the first 120 characters | Your application, at the Messages box |
| Staff review a progress report | "Progress report: Accepted" or "Changes requested". It mentions feedback when there is some | The report |
| You receive a badge | "You earned a badge: <title>" with the XP | My account |

### For staff

| Trigger | Who is told | Opens |
| --- | --- | --- |
| A member submits an application | Everyone holding `applications.review`, except the author | The application in the workspace |
| A member sends a message | The reviewer who last handled that application if they still have review rights, otherwise everyone with `applications.review` | The conversation |
| A member sends or updates a progress report | Everyone holding `applications.review` | The report |

Staff see the same bell and `/notifications` page, shown inside the workspace layout.

## Notifications and e-mail are separate

**Simple explanation:** the bell is a notice board inside the site. E-mail is sent on its own, by different code.

**Technical explanation:** in-app notifications are stored by `HubNotification` and sent through `NotificationCenter` in the `notifications` table. E-mail is sent through `TemplatedMail`. If a notification cannot be saved, the action that triggered it still succeeds and the problem is logged.

| Message | In-app notice | E-mail |
| --- | --- | --- |
| Application submitted | Always | Confirmation e-mail |
| Status change | Always | Only if the reviewer chose to notify you |
| Staff message about an application | Always | None. The code sends no e-mail for messages. See [Application messages](feature-guides/application-messages.md) |
| Announcements and event news | Not sent here | Only if you opted in. See [E-mail preferences and unsubscribe](feature-guides/email-preferences-and-unsubscribe.md) |

Turning off announcement e-mails does not switch off bell notifications. There is no setting to switch off bell notifications.

## Troubleshooting

| Problem | Fix |
| --- | --- |
| No bell icon | Your e-mail is not verified. Verify it at `/verify-email`. |
| I clicked a notification and landed on the notifications list | The link was missing or not a safe same-site path. Open the item from **My applications**. |
| The count did not drop | Reload the page. The count is read when the page loads. |
| I expected a status notification but got none | The status did not change. Notices are only created when the status changes. |
| I did not get an e-mail for a status change | Staff choose whether to send it. Check the bell and the application page. |
| A notification says "page not found" | The application or report was removed. Contact the Hub. |
