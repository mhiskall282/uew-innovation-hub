# System scope

> **Audience:** Managers, sponsors, developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Project overview](project-overview.md), [Features and capabilities](features-and-capabilities.md), [Technical debt register](../12-maintenance/technical-debt-register.md), [Environment configuration](../09-deployment-and-operations/environment-configuration.md)

This page states what is inside the platform, what is outside it, what it depends on, and the limits that apply today. Each limit was checked against the file named.

## In scope

- Public website, member portal, staff workspace and read-only open data API (see [overview](project-overview.md)).
- Programmes of six types: grant, fellowship, incubation, competition, training, event (`ProgramType`).
- Application forms with up to 40 fields in 12 field types (`FormSchema::MAX_FIELDS`, `FormSchema::TYPES`).
- Project, partner (organisation) and publication registers.
- News, blog, announcement posts, event photo albums and editable site pages.
- Announcement e-mails to opted-in members and transactional e-mails (codes, status changes).
- Audit log, CSV exports, a PDF report.
- Review of legacy grant applications already in the database (read and decide only; there is no public form that creates them).

## Out of scope

| Item | Evidence |
| --- | --- |
| Online payments or disbursement of funds | No payment code or package in `composer.json` |
| Single sign-on with UEW systems | `Auth\*` controllers use local e-mail and password only |
| Staff multi-factor authentication | Audit finding F-12 (recommendation) |
| Write access through the API, API keys, webhooks | `routes/api.php` has `GET` routes only; throttle only |
| Mobile app | None in repository |
| Self-service account deletion or data export | Audit finding F-15; deletion is requested by e-mail |
| Scheduled jobs | `routes/console.php` holds only the default `inspire` command |
| Excel (`.xlsx`) export | Exports are CSV (`ReportExportService`, `ApplicationService::exportCsv`) and one PDF. The legacy system produced Excel. |
| Public submission of legacy-style grant applications | `workspace.grants.*` routes review existing records only |

## Dependencies

| Dependency | Needed for | Where configured |
| --- | --- | --- |
| PostgreSQL (hosted), SQLite (local default), MySQL driver installed in the image | All data | `config/database.php`, `Dockerfile`, `.env.example` |
| SMTP server | Real e-mail (codes, notifications, broadcasts) | Hub Director's Site settings page, or `MAIL_*` variables |
| S3-compatible bucket (AWS S3, Cloudflare R2, Backblaze B2) | Durable uploaded PDFs (`DOCUMENTS_DISK=s3`) and gallery photos (`GALLERY_DISK=s3`) | `config/hub.php`, `config/filesystems.php` |
| Queue worker | Sending e-mail and broadcasts | `docker/entrypoint.sh` starts `queue:work` inside the container unless `RUN_QUEUE_WORKER=false` |
| Render (hosting), GitHub (code and CI) | Current test deployment | `render.yaml`, `.github/workflows/ci.yml` |
| PHP GD extension | Gallery image processing | `Dockerfile` installs `gd` |

## Current limits

| Limit | Detail | Source |
| --- | --- | --- |
| Free web service sleeps | After 15 minutes without traffic; the next request takes about a minute. | Comment in `render.yaml` |
| Free PostgreSQL expires | After 30 days on Render's free plan. | Comment in `render.yaml` |
| Uploads lost on redeploy | Free plan has no persistent disk. Documents and gallery photos are lost unless S3 or R2 is set. Missing gallery files are hidden rather than shown broken (`GalleryTest`). | `render.yaml`, `config/hub.php` |
| E-mail not delivered | `MAIL_MAILER=log` writes mail to the log. New online sign-ups cannot receive their code until SMTP is set. | `render.yaml`, handover report section 4 |
| Queue runs inside the web container | A restart interrupts it; on Vercel `QUEUE_CONNECTION=sync`. | `docker/entrypoint.sh`, `vercel.json` |
| No scheduler | Scheduled posts work because visibility is computed from `published_at` when a page is read. Nothing runs at a set time. | `routes/console.php`, `PostService` |
| API has no authentication | Public data only; 60 requests per minute per IP; 60-second cache header. | `AppServiceProvider`, `Api\V1\ApiController` |
| Demo data on the test site | `SEED_DEMO_DATA=true` creates demo accounts and sample content on first boot. | `render.yaml`, `docker/entrypoint.sh` |
| Search indexing off | `X-Robots-Tag: noindex` unless `SEARCH_INDEXING=true`. | `config/hub.php` |
| Upload size | PDFs up to 20 MB by default (`MAX_UPLOAD_KB=20480`). | `config/hub.php` |
| Content security policy allows `unsafe-eval` | Needed by Alpine.js; accepted risk (F-11). | Audit report |

## Alternative deployment

`vercel.json` and `api/index.php` define a Vercel entry point (PHP runtime `vercel-php@0.8.0`, `QUEUE_CONNECTION=sync`, `DOCUMENTS_DISK=s3`). The documentation here treats Render as the supported target.

> **Requires confirmation:** whether the Vercel deployment has ever been run. The repository has no instructions or CI job for it, and Vercel's read-only file system conflicts with local gallery storage (`GALLERY_DISK` defaults to `gallery`, which is not set in `vercel.json`).

## Leftover or unused items

- `legacy/` is the old procedural PHP system. It is not served; the web root is `public/`.
- The root folders `reports/` and `publications/` are the source for `hub:import-documents`.
- The view folders `resources/views/{admin,dashboard,grants,organizations,posts,projects,publications,reports}` are mostly leftovers. No controller in `app/` calls `view('<folder>.…')` for them. The one exception is `reports/_details.blade.php`, which `portal/reports/show.blade.php` and `workspace/progress-reports/show.blade.php` include. See the [technical debt register](../12-maintenance/technical-debt-register.md).
