# Content Management

> **Audience:** Hub Director, Programme Officers, Communications Interns · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Data management](data-management.md), [Administrative workflows](administrative-workflows.md), [Gallery guide](../02-user-guide/feature-guides/gallery.md), [Programme applications guide](../02-user-guide/feature-guides/programme-applications.md), [Business logic](../04-architecture/business-logic.md)

This page covers everything staff publish or run through the workspace: programmes and their application forms, the application review queue, progress reports, legacy grants, broadcasts, news posts, website pages, the gallery and contact enquiries. Projects, publications and partners are in [data management](data-management.md).

## Programmes and the form builder

Code: [`ProgramController`](../../app/Http/Controllers/Workspace/ProgramController.php), [`ProgramService`](../../app/Services/ProgramService.php), [`FormSchema`](../../app/Support/FormSchema.php). Needs `programs.manage`. Viewing one programme's applications also works with `applications.review`.

A programme (`programs`) has a type (grant, fellowship, incubation, competition, training, event), a focus area, dates (`opens_at`, `closes_at`, `starts_at`), capacity, funding amount and its own application form.

### Programme status

| Status | Meaning |
| --- | --- |
| `draft` | Staff only |
| `published` | Visible and open to applications between its dates. `published_at` is set the first time |
| `closed` | Still visible, no new applications |
| `archived` | Removed from public view, records kept |

### Create a programme

1. **Programmes** > **New programme**. Fill the title, type, summary, description, eligibility, dates and capacity.
2. Build the application form (below).
3. Choose a status. Tick **notify members** to announce it on first publication.
4. Save. Audit: `program.created`.

### Form builder

The form is stored as JSON in `form_schema`. New programmes start with `FormSchema::starter()`. Up to 40 fields (`FormSchema::MAX_FIELDS`). Field types:

| Type | Shown as |
| --- | --- |
| `short_text`, `long_text` | Short answer, Paragraph |
| `number`, `email`, `phone`, `url`, `date` | Typed inputs |
| `select`, `radio`, `checkboxes` | Choices (these three need options) |
| `yes_no` | Yes / No |
| `file` | PDF upload |

Fields can be required, carry help text, and some can pre-fill from the member's profile (`Demographics::prefillSources()`). Keys are normalised and made unique on save.

> **Warning:** Changing or removing fields after applications exist can orphan stored answers. The edit page tells you when applications exist (`hasApplications`). Prefer adding fields over changing existing ones.

### Announce, export, delete

| Action | Route | Rule |
| --- | --- | --- |
| Announce | `workspace.programs.announce` | Published programmes only (otherwise 422). Creates a broadcast to members opted in whose interests include the focus area, or to all opted-in members when the programme has none |
| Export applications | `workspace.programs.export` | CSV of submitted applications |
| Delete | `workspace.programs.destroy` | Refused when any non-draft application exists: "Archive it instead of deleting it." Otherwise a soft delete |

> **Warning:** Deleted programmes are soft-deleted, and the workspace has no restore screen. Recovery needs a database change. Set the status to `archived` rather than deleting.

## Application review queue

Code: [`ApplicationController`](../../app/Http/Controllers/Workspace/ApplicationController.php), [`ApplicationService`](../../app/Services/ApplicationService.php). Needs `applications.review` (Hub Director, Programme Officer, Reviewer).

- **Applications** (`/workspace/applications`) lists open applications (`submitted`, `under_review`, `shortlisted`) oldest first. Use the status filter to see decided ones. Drafts never appear.
- Open an application to read answers, download uploaded PDFs, read the message thread, see the audit history and the member's other applications.
- Opening an application marks the member's messages as read.

### Decide one application

On the application page set the status, internal notes (staff only, up to 10,000 characters), an optional decision message to the member (up to 2,000), an optional score 0 to 100, and whether to send an e-mail.

| Status | Notes |
| --- | --- |
| `submitted`, `under_review`, `shortlisted` | Open states |
| `approved`, `rejected` | Final. `decided_at` is set. Approving awards the member a `participant` badge |
| `draft`, `withdrawn` | Cannot be set by staff |

A status change always sends the member an in-app notification. The **e-mail** is sent only when the "notify" box is ticked. Audit: `application.reviewed` with a diff.

### Bulk decisions

On a programme's page, tick applications and choose a status (`workspace.applications.bulk`). Up to 500 at once.

- Drafts and withdrawn applications are skipped silently, and the message reports how many were updated.
- Each application goes through the same `review()` method, so notifications, badges and audit entries are identical to single decisions.
- The existing decision message on each application is kept. Bulk decisions cannot write a new one.

> **Warning:** Bulk **Approved** or **Rejected** is final for the member and, if "notify" is ticked, sends one e-mail per application. Filter and check the selection first. There is no undo button. You can set a different status afterwards on each application.

## Progress reports (Incubation board)

Code: [`ProgressReportController`](../../app/Http/Controllers/Workspace/ProgressReportController.php), [`ProgressReportService`](../../app/Services/ProgressReportService.php). Needs `applications.review`.

Accepted teams submit periodic progress and budget reports from the member portal. The board shows reports newest first, funding versus reported spending per programme, and a count awaiting review.

1. Open a report. Compare it with the member's earlier reports shown beside it.
2. Choose **Accepted** or **Changes requested**. Changes requested needs feedback ("Tell the member what to change").
3. Save. The member gets an in-app notification. Audit: `report.reviewed`.

You cannot review a report you wrote yourself (`ProgressReportPolicy::review`).

## Legacy grant applications

Code: [`GrantApplicationController`](../../app/Http/Controllers/Workspace/GrantApplicationController.php). Needs `grants.review` (Hub Director, Programme Officer, Reviewer).

These are applications imported from the old system (`grant_applications`). The list filters by status (`submitted`, `under_review`, `shortlisted`, `approved`, `rejected`), focus area and search on reference, applicant, e-mail, project title or organisation. Open one to read it, download the proposal PDF, add reviewer notes, set the status and optionally notify the applicant. New applications go through Programmes instead.

## Broadcasts

Code: [`BroadcastController`](../../app/Http/Controllers/Workspace/BroadcastController.php), [`BroadcastService`](../../app/Services/BroadcastService.php), [`SendBroadcast`](../../app/Jobs/SendBroadcast.php). Needs `broadcasts.send`.

1. **Broadcasts** > **New**. Write subject (max 200) and body (max 20,000). The body starts with the `first_name` placeholder.
2. Pick an audience: interests and affiliations, or leave both empty for everyone eligible. The live count comes from `workspace.broadcasts.audience`.
3. **Save draft** or **Send**.

Who receives it: active members with a verified e-mail who ticked "announcements" (`marketing_opt_in`). Unverified, suspended and opted-out members are never included. Every message has an unsubscribe link.

How sending works: `BroadcastService::send` sets the status to `sending`, stores the recipient count and queues the `SendBroadcast` job. The job reads recipients in chunks of 200 and queues one e-mail each, then marks the broadcast `sent`. The job has `tries = 1` and a 600-second timeout.

> **Warning:** Sending is final. Only drafts can be edited or deleted. Sent broadcasts are kept for the record. A broadcast stuck on `sending` means the queue worker is not running or the job failed. See [admin troubleshooting](admin-troubleshooting.md#broadcast-stays-sending).

## News and posts

Code: [`PostController`](../../app/Http/Controllers/Workspace/PostController.php), [`PostService`](../../app/Services/PostService.php), [`PostPolicy`](../../app/Policies/PostPolicy.php). Menu: **News & posts**, needs `content.draft`.

| Role | Can do |
| --- | --- |
| Communications Intern (`content.draft`) | Create drafts. Edit or delete only their own unpublished posts. A publish date they submit is ignored and the post stays a draft |
| Programme Officer, Hub Director (`content.publish`) | Publish, schedule, edit and delete any post |

States come from `published_at`: **draft** (empty), **scheduled** (in the future) and **published** (now or past). There is no scheduler job. A scheduled post becomes visible when its time passes because the public pages filter on `published_at`. Choosing "publish" keeps an existing past date, otherwise uses now. Audit: `post.created`, `post.updated`, `post.deleted`. Posts are soft-deleted.

## Website pages

Code: [`PageController`](../../app/Http/Controllers/Workspace/PageController.php). Needs `pages.manage`. Pages such as About, FAQ, Privacy and Terms are created by `PageSeeder`, which `docker/entrypoint.sh` runs on every boot and never overwrites edits.

- Fields: title (200), slug (letters, digits, hyphens, max 80), summary (300), body (100,000), published, show in footer, sort order 0 to 1000.
- The slug is made from the title when left empty.
- A fixed list of slugs is refused because it would clash with the site: `projects`, `publications`, `news`, `partners`, `contact`, `programmes`, `apply`, `sign-in`, `sign-up`, `portal`, `workspace`, `api`, `gallery`, `media`, `up` and others listed in `PageController::RESERVED`.
- Audit: `page.created`, `page.updated` (body text is left out of the diff), `page.deleted`.

> **Warning:** Deleting a page removes it permanently (the `Page` model does not use soft deletes). Unpublish instead.

## Gallery albums

Code: [`AlbumController`](../../app/Http/Controllers/Workspace/AlbumController.php), [`GalleryService`](../../app/Services/GalleryService.php), [`AlbumPolicy`](../../app/Policies/AlbumPolicy.php). Menu: **Gallery**, needs `content.draft`.

| Task | Rule |
| --- | --- |
| Create or edit an album | Interns can while the album is unpublished. Publishers can always |
| Publish or feature an album | `content.publish` only. For others the checkboxes are ignored |
| Upload photos | Up to 10 files per request, each at most 10 MB, JPEG, PNG or WebP, at least 300 by 300 pixels, at most 30 megapixels |
| Arrange | Caption (300), sort order and cover photo in one save |
| Delete a photo or an album | Photo: anyone who may update the album. Album: `content.publish` only |

Processing: each upload is decoded and re-encoded with the GD extension into two JPEGs (1600 px and 640 px). The original and its metadata, including GPS position, are discarded. Photos are served through `media.photo`, which checks that the album is published, so unpublished photos are visible to staff only. Audit: `album.created`, `album.updated`, `album.photos_added`, `album.arranged`, `album.photo_deleted`, `album.deleted`.

> **Warning:** Deleting a photo or an album removes the files from the disk and cannot be undone. There is no recycle bin.

> **Known issue:** `docker/php.ini` sets `max_file_uploads = 5` and `post_max_size = 30M`, but the album form accepts 10 files of up to 10 MB. On the Docker deployment, selecting more than 5 files drops the extras, and a request over 30 MB fails before Laravel sees it. Upload 3 to 5 photos at a time. See [backend problems](../11-troubleshooting/backend-problems.md#photo-upload-drops-files).

Without a persistent disk (Render free plan), photos disappear on every deploy while their database rows remain. `AlbumPhoto::isAvailable()` hides missing photos from public pages. Use `GALLERY_DISK=s3`.

## Contact enquiries

Code: [`ContactMessageController`](../../app/Http/Controllers/Workspace/ContactMessageController.php), [`ContactMessageService`](../../app/Services/ContactMessageService.php). Menu: **Enquiries**, needs `messages.manage`.

Statuses: `new`, `read`, `replied`, `archived`. The list hides archived messages unless you filter for them. Opening a message marks it read. Staff reply from their own mail client. The workspace does not send replies. Delete removes the message permanently (`message.deleted`). The public form is limited to 3 messages per minute and 20 per day per IP.

Staff with `messages.manage` who ticked "contact message notifications" in Account settings get an e-mail for new enquiries.
