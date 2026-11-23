# UEW Innovation Hub

The project, funding and communications platform for the Innovation Hub at the University of Education, Winneba (UEW).

It has three parts:

| Part | URL | Who uses it |
| --- | --- | --- |
| Public website | `/` | Anyone: browse projects, publications, news and partners; apply for funding; track an application |
| Staff workspace | `/workspace` | Hub staff, by role: manage projects, review grants, publish content, manage staff |
| Open data API | `/api/v1` | Partners and developers: read-only JSON for public data |

Everyone signs in at `/sign-in` (`/login` redirects there). Staff land in the workspace, members in their portal.

Built with Laravel 12, PHP 8.4, Blade, Tailwind CSS v4 and Alpine.js. Runs on SQLite locally and PostgreSQL or MySQL in production.

## Features

**Public website**
- Impact figures, six focus areas, current projects, latest news and publications
- Project directory with search and filters; project pages with partners, reach and report download
- Publication library with PDF downloads (download counts recorded)
- News, blog posts and announcements written in Markdown
- Programmes (grants, fellowships, incubation, competitions, training, events) with open / upcoming / closed tabs
- Member accounts: sign-up, 6-digit e-mail verification, onboarding with interests, password reset by code
- Member portal: apply with custom forms, save drafts, upload PDFs, track status, withdraw

**Staff workspace**
- Dashboard with portfolio, reach, funding and grant pipeline figures, adjusted to the user's role
- Projects: create, edit, archive, link partners with roles, upload reports, mark as internal, CSV export, change history
- Grant applications: review queue with status tabs, decisions with panel notes, applicant email notifications
- News and posts: drafts, publish now or schedule; interns draft, officers publish
- Publications and partner organisations management
- Staff accounts with five roles, deactivation, password resets and a last-Hub-Director safeguard
- Audit log of every sign-in and change, with field-level diffs
- Branded error pages, responsive layout, collapsible sidebar

**Roles**

| Role | Can |
| --- | --- |
| Hub Director (`super_admin`) | Everything, including staff accounts, the audit log and archiving projects |
| Programme Officer (`officer`) | Projects, partners, publications, grant reviews, publish content, exports |
| Grant Reviewer (`reviewer`) | Review grant applications, view the portfolio, exports |
| Communications Intern (`intern`) | Draft news and blog posts, view the portfolio |
| Read-only Staff (`viewer`) | View the portfolio |

The matrix lives in [`app/Enums/Role.php`](app/Enums/Role.php) and is enforced on every route and action. See [docs/security.md](docs/security.md).

## Run it locally

Requirements: PHP 8.2+ (8.4 recommended) with `pdo_sqlite`, Composer 2, Node.js 20.19+ or 22.12+.

```bash
git clone https://github.com/mhiskall282/uew-innovation-hub.git
cd uew-innovation-hub
composer install
npm ci
cp .env.example .env          # Windows PowerShell: Copy-Item .env.example .env
php artisan key:generate
touch database/database.sqlite  # PowerShell: New-Item database/database.sqlite
php artisan migrate --seed      # schema plus demo accounts and sample content
php artisan hub:import-documents  # copies the legacy report and newsletter PDFs into storage
npm run build
php artisan serve
```

Open http://localhost:8000 for the public site and http://localhost:8000/workspace/sign-in for the workspace.

For live CSS/JS reloading run `npm run dev` in a second terminal. Queued emails are written to `storage/logs/laravel.log` (`MAIL_MAILER=log`); run `php artisan queue:work` to process them.

### Demo accounts

`php artisan db:seed` creates one account per role. The password for all of them is `password` (change it with `SEED_PASSWORD` before seeding).

| Email | Role |
| --- | --- |
| admin@uew.edu.gh | Hub Director |
| officer@uew.edu.gh | Programme Officer |
| reviewer@uew.edu.gh | Grant Reviewer |
| intern@uew.edu.gh | Communications Intern |
| viewer@uew.edu.gh | Read-only Staff |

Demo data is for local use only. The seeder refuses to run in production unless `SEED_DEMO_DATA=true`. Real deployments create their first administrator with `php artisan hub:create-admin` (see [docs/deployment.md](docs/deployment.md)).

## Tests

```bash
php artisan test
```

101 feature and unit tests cover the public site, member sign-up with e-mail codes, onboarding, password reset, sign-in and lockout, the full role-by-page access matrix, project, content, publication, partner and user management, document access, the API, and security headers.

## Deploy

- **Render** (Docker, free tier): [`render.yaml`](render.yaml) blueprint, [`Dockerfile`](Dockerfile)
- **Vercel** (serverless PHP): [`vercel.json`](vercel.json), [`api/index.php`](api/index.php)

Step-by-step instructions, costs and limits are in [docs/deployment.md](docs/deployment.md).

## Documentation

- [Architecture](docs/architecture.md): structure, layers, data model, request flow, scaling
- [API reference](docs/api.md): endpoints, parameters, response format
- [Deployment](docs/deployment.md): local, Render, Vercel, storage, mail, upgrades
- [Security](docs/security.md): access control, data protection, hardening checklist

## Repository layout

```
app/
  Console/Commands/   hub:create-admin, hub:import-documents
  Enums/              Role, Permission, ProjectStatus, GrantStatus, PostType
  Http/Controllers/   Site/ (public), Workspace/ (staff), Api/V1/ (open data), Auth/
  Http/Requests/      Validation for every form
  Http/Resources/     API representations (public fields only)
  Models/             Eloquent models and shared concerns
  Policies/           Per-model authorization
  Services/           Business logic: projects, grants, posts, users, documents, metrics, audit
resources/views/
  site/ workspace/ auth/ errors/ emails/ components/ui/ components/layouts/
docker/               Apache, PHP and entrypoint for the container image
docs/                 Project documentation
legacy/               The original procedural PHP system, kept for reference only (not deployed)
reports/, publications/  Legacy PDFs imported by hub:import-documents
```
