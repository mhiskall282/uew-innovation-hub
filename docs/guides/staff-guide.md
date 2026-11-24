# Staff guide

For Hub staff using the workspace at `/workspace`. Sign in at `/sign-in` with your staff account; you land in the workspace automatically.

## Who can do what

You only see the menu items your role allows. Pages you can't use are hidden, and the server refuses them even if you type the address.

| Role | Main jobs |
| --- | --- |
| Hub Director | Everything, plus staff accounts, site settings, SMTP e-mail, e-mail templates and the audit log |
| Programme Officer | Programmes and forms, application reviews, incubation board, members and badges, broadcasts, projects, partners, publications, news, pages, enquiries |
| Reviewer | Review applications and legacy grants, incubation board, reports |
| Communications Intern | Draft news and blog posts (an officer publishes them) |
| Read-only Staff | View the project portfolio |

## Daily routine

1. Check the **bell** (top right): new applications, applicant messages and progress reports.
2. **Applications** shows everything waiting for a decision; the sidebar badge is the count.
3. **Incubation board** shows reports waiting for review.
4. **Enquiries** holds new contact-form messages.

## Launch a programme

1. **Programmes → New programme**.
2. Fill in title, type, focus area, summary and description (Markdown: `## heading`, `**bold**`, `- list`).
3. Build the **application form**. Add questions with *Add question*, *+ Paragraph*, *+ PDF upload* or *+ Dropdown*. Reorder with the arrows.
   - **Pre-fill from the applicant's profile** fills a question from the member's profile (region, age, institution...). Applicants can still change the answer.
   - Name, e-mail and phone are always attached from the profile, so don't ask for them again.
4. Set dates and capacity. Applications close automatically at the closing date or when capacity is reached.
5. Set **Status** to *Published*. Tick **E-mail matching members** to send a launch announcement to opted-in members interested in that focus area.

Renaming questions after people have applied is safe. Removing a question hides its answers from new reviews.

## Review applications

1. Open an application from **Applications** or the programme page.
2. You see the answers, uploaded PDFs and the applicant's details **as submitted** (age, region, education, affiliation). Gender and disability are deliberately hidden from reviewers.
3. Under **Decision**, pick a status, an optional score (0–100), a message to the applicant and internal notes (staff only).
4. Leave **E-mail the applicant** ticked to send the status e-mail. The in-app notification is always sent.
5. Choosing **Accepted** automatically gives the member a *Participant* (or *Incubatee*) badge.

**Bulk decisions:** on a programme page, tick several applications and apply one status to all.

**Messages:** use the **Messages** card on an application to talk to the applicant. They see it in their portal and get a notification. Use *internal notes* for anything private.

You can't review your own application.

## Incubation board (progress and budget reports)

Accepted applicants submit periodic reports with milestones, challenges, reach and a budget breakdown.

- **Spending by programme** compares programme funding, funds disbursed and reported spending. Burn above 100% is shown in red.
- Open a report to read it and its spending lines, then choose **Accepted** or **Changes requested**. Feedback is required when you ask for changes; the member sees it and can resubmit.

## Members, badges and the leaderboard

- **Members** lists every member account with search and filters. Export a CSV for funder reporting.
- On a member's page, **Award a badge**: Winner, Finalist, Participant, Alumni, Mentor or a custom recognition (custom badges can have their own XP). Badges show on the member's profile and, if they opted in, on the public community page.
- **Remove** a badge to take back its XP.
- **Suspend account** signs the member out and blocks sign-in.

## Broadcasts

**Broadcasts → New broadcast** e-mails opted-in members, filtered by interests and affiliation. Preview the audience count before sending. Members can unsubscribe from any broadcast.

## Website content

- **News & posts**: interns draft, officers publish now or schedule.
- **Pages**: About, FAQ, Privacy, Terms and any custom page, in Markdown.
- **Projects, Publications, Partners**: the public portfolio.

## Reports

**Reports** shows programme outcomes, member growth, affiliations, interests, the most active members, incubation budgets and anonymous inclusion figures (gender, age bands, regions) for funder reporting.

1. Pick a **Period** at the top: last 30 or 90 days, last 12 months, this year, last year, all time, or **Custom range** with your own from and to dates.
2. Choose **Export PDF**, tick the sections you want (programmes, membership, inclusion, incubation budgets, most active members) and **Download PDF**.
3. The PDF carries the UEW crest, the period, who exported it and when. Each export is recorded in the audit log.

On the Incubation board, **Budget PDF** exports the budget section on its own.

## Administration (Hub Director)

- **Staff accounts**: create staff, set roles, deactivate. The last active Hub Director can't be removed.
- **Site settings**: contact details, social links, announcement bar, registration on/off, and **SMTP e-mail** (the password is stored encrypted). Use *Send test e-mail* after changes.
- **E-mail templates**: edit wording of every automatic e-mail; *Reset* restores the default.
- **Audit log**: every sign-in and change, with who, when, IP address and field-level changes.

## Troubleshooting

| Problem | What to do |
| --- | --- |
| A menu item is missing | Your role doesn't include it. Ask the Hub Director. |
| "403 – You don't have access" | Same as above; the server checks every page. |
| Members say they get no e-mails | Check **Site settings → E-mail**, send a test e-mail, and check the queue worker is running (`php artisan queue:work`). |
| An applicant can't edit a submitted application | By design. Ask them to message you, or ask them to withdraw and reapply if the programme is still open. |
| Applications stopped arriving | Check the programme's closing date, capacity and status. |
| A report total looks wrong | Totals are recalculated from the spending lines on the server; ask the member to correct the lines. |
| Someone left the Hub | Deactivate their staff account in **Staff accounts**; their history stays in the audit log. |
