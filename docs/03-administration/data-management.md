# Data Management

> **Audience:** Hub Director, Programme Officers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Database overview](../07-database/database-overview.md), [Backup and restore](../07-database/backup-and-restore.md), [Data retention](../07-database/data-retention.md), [Tables and columns](../07-database/tables-and-columns.md), [Database problems](../11-troubleshooting/database-problems.md)

This page covers the records staff maintain by hand (projects, publications, partner organisations), uploaded files, exports, and what happens when records are removed. For table and column detail see [tables and columns](../07-database/tables-and-columns.md).

## Projects

Code: [`ProjectController`](../../app/Http/Controllers/Workspace/ProjectController.php), [`ProjectService`](../../app/Services/ProjectService.php), [`ProjectPolicy`](../../app/Policies/ProjectPolicy.php).

| Action | Permission | Roles |
| --- | --- | --- |
| View list, detail, download report PDF | `projects.view` | all staff |
| Create, edit | `projects.manage` | Hub Director, Programme Officer |
| Archive (delete) | `projects.delete` | Hub Director |
| Export CSV | `reports.export` | Hub Director, Programme Officer, Reviewer |

A project has a title, focus area, status, visibility (public or internal), dates, beneficiary target and reached, grant amount, lead researcher, department, linked partners with a role each, and an optional report PDF. The index filters by search, status, focus area, year and partner.

> **Warning:** The **Delete** button on a project is an archive. `ProjectService::delete` soft-deletes the row and keeps the report file "so the record can be restored". The workspace has no restore screen, so the project disappears from every list and from the public site. Restoring needs a database change (`deleted_at = NULL`). Only the Hub Director sees this button. The audit log records `project.deleted`.

Project history (the last 15 audit entries for that project) is shown on its detail page.

## Publications

Code: [`PublicationController`](../../app/Http/Controllers/Workspace/PublicationController.php), [`PublicationPolicy`](../../app/Policies/PublicationPolicy.php). Needs `publications.manage` (Hub Director, Programme Officer).

Types come from `config/hub.php`: Research Paper, Policy Brief, Newsletter, Technical Report, Annual Report. A publication can link to a project and carry a PDF. The list searches title, authors and journal name.

> **Warning:** The delete action says "removed". Publications are soft-deleted (`SoftDeletes`), with no restore screen. Audit: `publication.deleted`.

## Partner organisations

Code: [`OrganizationController`](../../app/Http/Controllers/Workspace/OrganizationController.php), [`OrganizationPolicy`](../../app/Policies/OrganizationPolicy.php). Anyone with `projects.view` can open the list. Create, edit and delete need `partners.manage` (Hub Director, Programme Officer).

Types: Funding Partner, Government Agency, Academic Institution, NGO, Industry Partner, Partner. The role a partner plays on a project (Funding Partner, Implementing Partner, Research Partner, Technical Advisor, Government Partner, Host Institution, Partner) is set on the project.

An organisation linked to any project cannot be deleted: "Remove it from those projects first." Organisations are hard-deleted when allowed (`organization.deleted`).

## Storage and durability

Two disks hold uploads.

| Data | Config | Local default | Durable option |
| --- | --- | --- | --- |
| Project reports, publication PDFs, grant proposals, application file answers | `DOCUMENTS_DISK` (`config/hub.php`) | `private`, `storage/app/private` | `s3` (any S3-compatible bucket, such as Cloudflare R2) |
| Gallery photos | `GALLERY_DISK` | `gallery`, `storage/app/gallery` | `s3` |

> **Warning:** On Render's free plan the container disk is erased on every deploy and restart. Records stay in PostgreSQL but the files are gone. Downloads then fail with 404 "The requested document is not available." (`DocumentStorage::download`) and gallery photos silently vanish from public pages (`AlbumPhoto::isAvailable`). Set both disks to `s3` before storing real data. Free Render PostgreSQL databases also expire after 30 days (`render.yaml` comment).

How documents are handled (`DocumentStorage`): only PDFs, saved under server-generated names such as `reports/project_20261130_ab12….pdf`, never under `public/`, and streamed through controllers that check permissions first. The maximum PDF size is `MAX_UPLOAD_KB` (default 20480 KB, 20 MB). PHP limits must be at least as high. See [backend problems](../11-troubleshooting/backend-problems.md#upload-too-large).

## Importing legacy documents

Command: `php artisan hub:import-documents [--source=PATH]`. Code: [`ImportLegacyDocuments`](../../app/Console/Commands/ImportLegacyDocuments.php).

It copies `*.pdf` files from the `reports/` and `publications/` folders under the source onto the documents disk, so legacy database rows that point at those names keep working.

```powershell
php artisan hub:import-documents
php artisan hub:import-documents --source="C:\path\to\folder-with-reports-and-publications"
```

```bash
php artisan hub:import-documents --source=/path/to/folder
```

Behaviour:

- The default source is the project root (`base_path()`). Folders that do not exist are skipped without error.
- Only files ending in `.pdf` (any case) are copied.
- Files that already exist on the disk are skipped, so it is safe to repeat.
- It prints `Documents imported: N copied, M already present.` and always exits successfully, even when nothing was found.
- `docker/entrypoint.sh` runs it on every boot and only warns if it fails. With a disk that is wiped on deploy, the boot-time copy restores only the PDFs that ship inside the image. Anything uploaded through the workspace is not restored.

## Exports and personal data

| Export | Contains personal data |
| --- | --- |
| Projects CSV | No (lead researcher name only) |
| Members CSV | Yes: name, e-mail, phone, gender, age, region, education, affiliation |
| Programme applications CSV | Yes: applicant contact details and form answers |
| Report PDF | No. Demographics are totals only |

See [data protection](../08-security/data-protection.md) for the legal basis and handling rules. The workspace has no "export my data" or "delete a member" function. A request to erase a member's data is a manual database task. **Requires confirmation:** the Hub's procedure for such requests.

## Backups

The application has no backup command, and `routes/console.php` schedules nothing. Backups depend on the database host. Render and Neon provide them according to their plans. See [backup and restore](../07-database/backup-and-restore.md) and [backup and recovery](../09-deployment-and-operations/backup-and-recovery.md). Files on `s3` need their own bucket versioning.

> **Recommended, not implemented:** a scheduled database dump and an application-level restore screen for soft-deleted records.

## What is removed for good and what is not

| Record | Removal | Recoverable from the workspace |
| --- | --- | --- |
| Project, programme, news post, publication, legacy grant application | Soft delete | No (database change only) |
| Staff account | Hard delete | No |
| Partner organisation, website page, gallery album and photos, contact enquiry, draft broadcast | Hard delete (files removed from disk) | No |
| Member account | Cannot be deleted. Suspend instead | Re-activate |
| Audit log entries | Never removed by the application | n/a |
