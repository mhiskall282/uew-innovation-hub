# Application messages

> **Audience:** Members · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Programme applications](programme-applications.md), [Notifications guide](../notifications-guide.md), [Contact and enquiries](contact-and-enquiries.md), [Common user issues](../common-user-issues.md)

This page explains the **Messages** box on an application page. It is a private conversation between you and the Hub team about one application.

- **What it does:** lets you ask a question, and lets reviewers reply, inside the application.
- **Why:** answers stay attached to the application and its reference number.
- **Who:** the member who owns the application, and staff who hold the application-review permission (Programme Officer, Reviewer, Hub Director). No one else can read it.
- **Where:** open **My applications**, then **View**. Scroll to **Messages**. The link `#messages` in a notification jumps straight there.

The Messages box does not appear on a draft. It appears once the application is submitted, and it stays available if you withdraw or are not selected.

## Send a message

1. Open the application page (`/portal/applications/{id}`).
2. In **Messages**, type in **Your message**. The placeholder suggests, for example, "Can I add a team member after submitting?"
3. Press **Send message**.

Expected result: the page reloads at the Messages box with "Message sent to the Hub team." Your message appears on the right in a dark bubble with the date and time.

| Rule | Value | Message if wrong |
| --- | --- | --- |
| Length | 2 to 3,000 characters | "The message field is required." or "The message field must be at least 2 characters." |
| Format | Plain text only. Links are not clickable and nothing is formatted | None |
| Rate | 20 messages a minute | "Too many requests" page (429) |

## Read a reply

1. When staff reply, you get a bell notification: "New message about <programme>" with the first 120 characters.
2. Press it. The application opens at the Messages box.
3. Staff replies show on the left in a grey bubble with the name "UEW Innovation Hub".

Opening the application marks the staff messages as read.

Staff replies are shown to you in the site and the bell. The code does not send an e-mail for each message, so check the bell or **My applications** if you are waiting for an answer.

## Who gets your message

- The reviewer who last handled the application, if they still hold review rights.
- Otherwise everyone who can review applications, except you.

They see a bell notification: "<your name> sent a message".

## Good practice

- Quote what you need, for example "Can I upload a corrected budget?".
- Keep personal data such as passwords or bank details out of messages. Staff will never ask for your password.
- For general questions that are not about one application, use [Contact](contact-and-enquiries.md).
- Write one message per question so replies are easy to follow.

## Errors and fixes

| What you see | Cause | Fix |
| --- | --- | --- |
| No Messages box | The application is still a draft | Submit it first |
| "You do not have access" (403) | The application is not yours | Sign in with the account that applied |
| "Page not found" (404) | The application does not exist or belongs to another member | Check the reference in your list |
| Message is not sent and the box shows red text | Fewer than 2 characters or more than 3,000 | Shorten or lengthen |
| No reply | Staff have not answered yet | Check the bell. Use the [contact form](contact-and-enquiries.md) and quote your reference if it has been more than two working days |

> **Requires confirmation:** the Contact page says "We usually reply within two working days." The code does not enforce a reply time for application messages.
