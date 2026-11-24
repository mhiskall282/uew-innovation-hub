# Stakeholder overview

For University leadership, funders, partners and the ICT directorate.

## What the platform does

The UEW Innovation Hub platform runs the Hub's full innovation pipeline in one system, from a call for applications to reporting outcomes to funders.

```
Call launched → Members apply → Panel reviews → Teams accepted → Progress & budget reports → Outcomes, badges, reporting
```

| Area | What it provides |
| --- | --- |
| Public website | Projects, publications, news, partners, programmes and a community leaderboard, in UEW branding |
| Member portal | Verified accounts, applications with custom forms, drafts and PDF uploads, status tracking, messaging with the Hub, progress and budget reports, badges and XP |
| Staff workspace | Programme builder, review queue, incubation board, member management, broadcasts, content, reports, audit log |
| Open data API | Read-only JSON of public data (projects, publications, news, partners, programmes, statistics) for partners and dashboards |

## Value for each group

**University leadership** sees programme throughput, acceptance rates, member growth, reach and funding on the **Reports** page, without spreadsheets.

**Funders and partners** get:
- spending against funding per programme, from the teams' own budget reports, reviewed by Hub staff;
- anonymous inclusion figures (gender, age bands, regions) suitable for donor reporting;
- a public, linkable record of funded projects and publications.

**Students and innovators** get one account for every opportunity, clear status updates, direct messaging with the Hub, and recognition (badges and a leaderboard) they can show.

**Hub staff** replace e-mail threads and forms with one review queue, one inbox of notifications and an audit trail.

## Data protection and security

- Personal data is handled under Ghana's Data Protection Act, 2012 (Act 843). Members see why each item is collected.
- Reviewers never see an applicant's gender or disability status. Demographics are reported only as totals.
- The community page is opt-in and never shows contact or demographic details.
- Accounts are verified by e-mail code. Passwords are hashed. Sign-in, registration and code requests are rate limited.
- Access is role-based and enforced on the server for every page and action (least privilege).
- HTTPS only, with HSTS, a Content Security Policy and other security headers. All forms are protected against CSRF.
- Every sign-in and change is recorded in the audit log.
- Automated tests (including a check that requests every page as every role) and a CI pipeline run on each change, including security audits of dependencies.

Details: [security.md](../security.md).

## Running costs and hosting

The platform runs on a single Docker container and a PostgreSQL database. It can start on Render's free tier and move to a paid plan or UEW's own servers without code changes. Uploaded documents can be stored on any S3-compatible service. See [deployment.md](../deployment.md).

## Key figures to watch

| Measure | Where |
| --- | --- |
| Applications per programme, acceptance rate | Reports → Programmes |
| New members per month | Reports |
| Funds disbursed vs spent | Incubation board |
| People reached by funded teams | Progress reports |
| Inclusion (gender, age, region) | Reports → Inclusion |
| Most engaged members | Reports → Most active members |

## Contacts

- Platform owner: Hub Director, UEW Innovation Hub
- Technical: see the repository README and `docs/` folder
