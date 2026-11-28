# User Management

> **Audience:** Hub Director (staff accounts), Programme Officers (members) · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Role and permission management](role-and-permission-management.md), [Audit and monitoring](audit-and-monitoring.md), [Authentication architecture](../04-architecture/authentication-architecture.md), [Authentication problems](../11-troubleshooting/authentication-problems.md)

The system keeps two kinds of account in one `users` table. **Staff** accounts (any role except `member`) are managed by the Hub Director under *Staff accounts*. **Members** (`Role::Member`) are public sign-ups managed by Programme Officers under *Members*. This page covers both.

## Staff accounts

Code: [`UserController`](../../app/Http/Controllers/Workspace/UserController.php), [`UserService`](../../app/Services/UserService.php), [`UserPolicy`](../../app/Policies/UserPolicy.php), [`StoreUserRequest`](../../app/Http/Requests/Workspace/StoreUserRequest.php), [`UpdateUserRequest`](../../app/Http/Requests/Workspace/UpdateUserRequest.php).

Needs `users.manage`. The user routes only carry `can:workspace.access`, and the policy then checks `users.manage`, so an officer who types `/workspace/users` gets a 403 page.

### Create a staff account

1. Open **Staff accounts** > **New account** (`/workspace/users/create`).
2. Enter name, e-mail, optional department, role and a password with confirmation.
3. Save. The system creates the account as **active** and with the **e-mail already verified** (`UserService::create`), so the person can sign in immediately without a verification code.
4. Share the temporary password with the person directly. The page says so after saving.

Details:

| Field | Rule |
| --- | --- |
| E-mail | Required, unique, stored in lower case |
| Role | One of the five staff roles. `member` is not offered |
| Password | Required, confirmed. Production: at least 12 characters with upper case, lower case and a number. Other environments: at least 8 (`AppServiceProvider`) |

The audit log records `user.created`. I found no step that forces the new person to change the temporary password at first sign-in. Ask them to do it under **Account settings** at once, or use the reset e-mail described below instead of sharing a password. 

### Edit a staff account

`/workspace/users/{id}/edit`. You can change name, e-mail, department, role and, optionally, the password.

| Guard | Behaviour |
| --- | --- |
| Self-role guard | You cannot change your own role. The form returns "You cannot change your own role. Ask another Hub Director." |
| Last-Hub-Director guard | A role change away from Hub Director fails when the account is the only active Hub Director. "Assign another Hub Director first." |
| Password change | Saving a new password signs the person out of other devices (remember-me token rotated, stored sessions deleted when `SESSION_DRIVER=database`) and queues a "password changed" notice e-mail |
| Audit | `user.updated` with a field-level diff. Passwords are logged as `changed`, never as values |

### Deactivate and re-activate

Use the **Deactivate** / **Activate** button in the Staff accounts list (`PATCH /workspace/users/{id}/toggle`).

> **Warning:** Deactivation signs the person out everywhere immediately and blocks sign-in. It does not delete anything. The Hub Director cannot deactivate their own account, and the last active Hub Director cannot be deactivated by anyone.

Deactivated accounts see "Your account has been deactivated. Contact the Hub Director if this is unexpected." on their next request (`EnsureAccountIsActive`). The audit log records `user.deactivated` or `user.activated`.

### Delete a staff account

The edit page has a **Delete account** button with a confirmation prompt.

> **Warning:** Deletion is permanent. `User` does not use soft deletes, so the row is removed. Prefer deactivation. Columns such as `created_by`, `author_id` and `reviewed_by` that I checked in the migrations use `nullOnDelete`, so records the person created stay but lose their author name. Audit log entries keep their text description. **Requires confirmation:** that every foreign key to `users` behaves the same way. Test on a copy of the database first.

The same guards apply: you cannot delete yourself or the last active Hub Director. The audit log records `user.deleted`.

### Send a password-reset or verification e-mail

Administrators never choose or see another person's password through this feature. The controller is [`AccountEmailController`](../../app/Http/Controllers/Workspace/AccountEmailController.php).

| Action | Route | Who | Effect |
| --- | --- | --- | --- |
| Send password-reset e-mail | `workspace.users.password-reset` (staff), `workspace.members.password-reset` (members) | Staff target: `users.manage`. Member target: `members.manage` | Issues a six-digit code and a signed link valid for 10 minutes. Refused with "Reactivate the account first" when the account is inactive |
| Resend verification e-mail | `workspace.members.verification` | `members.manage` | Same, for e-mail verification. Says "already verified" when it is |

Both are limited to 10 per minute per user, and each address can receive one code per 60 seconds (`VerificationService::RESEND_SECONDS`). Audit actions: `user.password-reset_sent`, `user.verification_sent`.

These e-mails need working delivery. With `MAIL_MAILER=log` the code only appears in the log. See [system configuration](system-configuration.md#smtp-and-e-mail-delivery).

### Account lockout

Sign-in locks after 5 failed attempts for the same e-mail and IP, or 20 failures for the same e-mail from any IP, for 15 minutes (`LoginRequest`). There is no admin button to unlock. Wait 15 minutes or ask the person to use **Forgot password**. Details in [authentication problems](../11-troubleshooting/authentication-problems.md).

## Members

Code: [`MemberController`](../../app/Http/Controllers/Workspace/MemberController.php), [`AchievementController`](../../app/Http/Controllers/Workspace/AchievementController.php), [`MemberService`](../../app/Services/MemberService.php), [`EngagementService`](../../app/Services/EngagementService.php). Needs `members.manage` (Hub Director, Programme Officer).

### Find members

`/workspace/members` shows 25 per page with four totals (all, verified, subscribed, joined this month). Filters:

| Filter | Options |
| --- | --- |
| Search | name, e-mail, institution, department, phone |
| Affiliation | the `Affiliation` enum values |
| Interest | the interest list from `ProfileRequest::interestOptions()` |
| State | `unverified`, `subscribed`, `suspended` |

### Member profile

`/workspace/members/{id}` shows the member's applications, badges and XP. From here staff can:

- **Resend verification** and **Send password reset** (see above).
- **Suspend / re-activate** (`PATCH /workspace/members/{id}/toggle`). Suspension signs the member out and blocks sign-in. It uses the same `UserService::setActive` as staff.
- **Award a badge** and **remove a badge**.
- Export the filtered list as CSV (`workspace.members.export`).

There is no screen to delete a member. Members are suspended, not removed.

### Badges (achievements)

Badge types (`AchievementType`): `winner`, `finalist`, `participant`, `alumni`, `mentor`, `custom`.

| Step | Detail |
| --- | --- |
| Award | Pick a type. Optionally pick one of the member's programmes. A `custom` badge needs a title (max 150 characters) and may carry 0 to 1000 XP. Standard badges use fixed XP |
| Duplicate | Awarding a badge the member already has returns "already has" and adds nothing |
| Remove | The **Remove** button takes back the badge and its XP (`EngagementService::revoke`) |
| Automatic | Approving an application awards the `participant` badge automatically |
| Audit | `achievement.awarded`, `achievement.revoked` |

Members control whether they appear on the public leaderboard through their own profile setting. See [badges and leaderboard](../02-user-guide/feature-guides/badges-and-leaderboard.md).

## Which actions need which permission

| Action | Hub Director | Programme Officer | Other staff |
| --- | --- | --- | --- |
| Create, edit, deactivate, delete staff | Yes | No | No |
| Send staff password-reset e-mail | Yes | No | No |
| View members, export, suspend, award badges | Yes | Yes | No |
| Send member password-reset or verification e-mail | Yes | Yes | No |

Full matrix: [authorization architecture](../04-architecture/authorization-architecture.md).
