# Business workflows

> **Audience:** Product owners, analysts, staff trainers, developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Step-by-step workflows (user guide)](../02-user-guide/step-by-step-workflows.md), [Administrative workflows](../03-administration/administrative-workflows.md), [Business logic](../04-architecture/business-logic.md), [Acceptance criteria](acceptance-criteria.md)

Six workflows with diagrams, taken from the code. All are **Implemented**; the file that proves each step is named under each diagram. Where the code is looser than the business might expect, a note says so.

## 1. Programme lifecycle

Stored statuses are `draft`, `published`, `closed`, `archived` ([`ProgramStatus`](../../app/Enums/ProgramStatus.php)). "Open" and "Opens soon" are computed from dates, not stored.

```mermaid
stateDiagram-v2
    [*] --> draft: "Officer creates programme"
    draft --> published: "Officer publishes"
    published --> closed: "Officer closes, or closes_at passes"
    published --> draft: "Officer unpublishes"
    closed --> published: "Officer reopens"
    closed --> archived: "Officer archives"
    published --> archived: "Officer archives"
    archived --> draft: "Officer edits status"
```

| Rule | Source |
| --- | --- |
| The status is a plain select on the programme form; the code does not enforce an order, so any status can be chosen from any other. | `ProgramRequest` (`Rule::enum(ProgramStatus::class)`), `ProgramService::update()` |
| The first time a programme becomes `published`, `published_at` is set. | `ProgramService::applyStatus()` |
| Public pages list `published` and `closed` programmes only. | `Program::scopeVisible()`, `ProgramStatus::publicValues()` |
| Applications are accepted only when status is `published`, `opens_at` has passed (or is empty), `closes_at` has not passed, and capacity is not reached. | `Program::isAcceptingApplications()` |
| The badge shows Draft, Archived, Closed, "Opens <date>" or Open. | `Program::availability()` |
| After publishing, an officer can announce it to opted-in members by e-mail. | `ProgramService::announce()` (`workspace.programs.announce`) |
| Only staff with `programs.manage` can change programmes. | `routes/web.php` |

The transitions in the diagram are the normal business path. They are drawn from what the form permits, not from a coded state machine.

## 2. Application lifecycle

```mermaid
stateDiagram-v2
    [*] --> draft: "Member saves answers"
    draft --> draft: "Member edits"
    draft --> submitted: "Member submits (declaration ticked)"
    draft --> withdrawn: "Member withdraws"
    submitted --> under_review: "Reviewer"
    submitted --> shortlisted: "Reviewer"
    submitted --> approved: "Reviewer (Accepted)"
    submitted --> rejected: "Reviewer (Not selected)"
    submitted --> withdrawn: "Member withdraws"
    under_review --> shortlisted: "Reviewer"
    under_review --> approved: "Reviewer"
    under_review --> rejected: "Reviewer"
    under_review --> withdrawn: "Member withdraws"
    shortlisted --> approved: "Reviewer"
    shortlisted --> rejected: "Reviewer"
    approved --> [*]
    rejected --> [*]
    withdrawn --> [*]
```

| Step | What happens | Source |
| --- | --- | --- |
| Save draft | Answers stored; reference `UEW-AP-<year>-<code>` created with the record; one application per member per programme. | `ApplicationService::save()`, unique index in `schema.md` |
| Submit | Status `submitted`; `submitted_at` set; profile snapshot stored; +10 XP; member and reviewers get in-app notices; e-mail to member and to staff who opted in. | `ApplicationService::save()`, `notifySubmitted()` |
| After submit | No further edits. Messages are possible both ways. | `ProgramApplication::isEditable()`, `ProgramApplicationPolicy::message()` |
| Review | Reviewer sets status, score, internal notes, decision message. Final statuses set `decided_at`. A reviewer cannot review their own application. | `ApplicationService::review()`, `ProgramApplicationPolicy::review()` |
| Notify | In-app notice on each change; e-mail only if "notify" is ticked. | `ApplicationService::review()` |
| Accepted | A "Participant" badge is awarded; the member may now file progress reports. | `ApplicationService::review()`, `ProgramApplication::canReportProgress()` |
| Bulk | Same decision for up to 500 applications; drafts and withdrawn skipped. | `ApplicationService::bulkReview()` |

> **Note:** Reviewers may move an application between any reviewable statuses, including from Accepted back to Under review. Only withdrawal is limited (`ApplicationStatus::canWithdraw()`). If the Hub wants a strict order, that is a **Proposed** change (see [functional requirements](functional-requirements.md), FR-55).

## 3. Progress report review

```mermaid
sequenceDiagram
    actor M as "Member (accepted)"
    participant S as "Hub system"
    actor R as "Reviewer"
    M->>S: "Submit report (period, summary, milestones, line items)"
    S->>S: "Recompute spent total, set status submitted, +20 XP on first report"
    S-->>R: "Notify staff with applications.review"
    R->>S: "Open report, choose Accepted or Changes requested, add feedback"
    S-->>M: "In-app notice with status"
    alt "Changes requested"
        M->>S: "Edit and resubmit"
        S-->>R: "Notify: report updated"
    end
```

Source: `ProgressReportService::submit()` and `review()`. A report is editable only in `changes_requested` (`ProgressReport::isEditable()`). Review sets `reviewed_by` and `reviewed_at` and writes the audit log.

## 4. Content publishing (news, blog, announcements)

```mermaid
flowchart TD
    A["Intern or officer writes post (content.draft)"] --> B{"Author has content.publish?"}
    B -- "No (intern)" --> C["Saved as draft, publish date ignored"]
    C --> D["Officer opens draft"]
    D --> E{"Officer chooses"}
    B -- "Yes (officer, Hub Director)" --> E
    E -- "Publish" --> F["published_at = now, visible on /news and API"]
    E -- "Schedule" --> G["published_at in future, hidden until date passes"]
    E -- "Draft" --> H["published_at empty, hidden"]
    G --> F
```

| Rule | Source |
| --- | --- |
| Without `content.publish`, `published_at` stays as it was. A new post stays a draft. | `PostService::publishDate()` |
| Interns edit and delete only their own unpublished drafts. | `PostPolicy::update()` |
| Scheduling in the past is rejected. | `ContentPublishingTest::test_scheduling_in_the_past_is_rejected` |
| No timer publishes a scheduled post. It becomes visible when its date passes because visibility is derived from `published_at` when read. | `routes/console.php` is empty; BRIEF fact |
| Unpublish by saving as draft. | `ContentPublishingTest::test_officer_can_unpublish_by_saving_as_draft` |

## 5. Gallery publishing

```mermaid
flowchart TD
    A["Drafter creates album (content.draft)"] --> B["Uploads photos: decoded, resized, metadata removed"]
    B --> C["Arrange order, captions, cover"]
    C --> D{"Publisher ticks Published?"}
    D -- "No" --> E["Album and photos hidden from public"]
    D -- "Yes (content.publish)" --> F["is_published = true, published_at set"]
    F --> G["Visible at /gallery and on home page"]
    G --> H["Only publishers edit; only publishers delete"]
```

Source: `GalleryService::save()`, `AlbumPolicy`, `GalleryTest`. Drafters can edit an album only while it is unpublished. Lost files are hidden, not shown broken. Set `GALLERY_DISK=s3` to keep photos across deploys.

## 6. Broadcast sending

```mermaid
flowchart TD
    A["Officer drafts broadcast: subject, body, audience filters"] --> B["Audience preview: count of matching members"]
    B --> C{"Intent"}
    C -- "Save draft" --> A
    C -- "Send" --> D["Status sending, recipients counted, audit entry"]
    D --> E["SendBroadcast job queued"]
    E --> F["One queued e-mail per member, 200 per chunk, signed unsubscribe link"]
    F --> G["Status sent, sent_at set, recipients_count updated"]
```

| Rule | Source |
| --- | --- |
| Recipients are active, verified members with `marketing_opt_in`, optionally filtered by interests and affiliations. | `BroadcastService::recipients()` |
| Only drafts can be edited or sent. Sent broadcasts return 422 on edit. | `Workspace\BroadcastController` |
| Programme announcements create a broadcast from the `program_launched` template and send it immediately. | `ProgramService::announce()` |
| The job has one try and a 600-second timeout. A failure leaves the status at `sending`. **Requires confirmation:** there is no retry or "failed" status in `BroadcastStatus`. | `SendBroadcast`, `BroadcastStatus` |
| With `MAIL_MAILER=log` the messages are written to the log, not delivered. | `render.yaml` |

## 7. Supporting flow: new member

```mermaid
flowchart LR
    A["Sign up"] --> B["E-mail code or link"]
    B --> C["Onboarding profile"]
    C --> D["Portal dashboard"]
    D --> E["Apply to programme"]
```

Sources: `Auth\RegisterController`, `VerificationService`, `Portal\OnboardingController`, middleware `verified.email` and `onboarded` in `routes/web.php`.
