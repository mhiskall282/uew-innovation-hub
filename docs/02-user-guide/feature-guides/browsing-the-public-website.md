# Browsing the public website

> **Audience:** Everyone, no account needed · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Gallery](gallery.md), [Contact and enquiries](contact-and-enquiries.md), [Programme applications](programme-applications.md), [Badges and leaderboard](badges-and-leaderboard.md), [Open data API](../../06-api-reference/api-overview.md)

The public website needs no sign-in. This page explains each public section: programmes, projects, publications, news, partners, the community page and the information pages such as About and Privacy.

- **What:** read-only pages about the Hub's work.
- **Why:** so applicants and partners can see what the Hub funds and which programmes are open.
- **Who:** anyone. Signed-in members and staff see the same pages plus account links.
- **Where:** the top menu: **Programmes**, **Projects**, **Publications**, **News**, **Gallery**, **Partners**, **Contact**. The home page (`/`) links to all of them. The footer adds **Community & leaderboard**, **Events**, information pages and the **Open data API** link.

On a phone, press the menu button at the top right to open the same list.

## Programmes (`/programmes`)

A programme is a grant, fellowship, incubation, competition, training or event.

1. Open **Programmes**.
2. Choose a tab: **Open now**, **Opening soon** or **Closed**. Each tab shows how many programmes it holds.
3. Use the chips to filter by type: **All types** or one of Grant, Fellowship, Incubation, Competition, Training, Event. A focus-area filter can also be added in the address (`?area=STEM`).
4. Press a programme card to read it.

On the **Open now** tab with no type chosen, an events strip lists up to 3 upcoming events.

A programme page shows the type, focus area, an availability badge (**Open**, **Opens 12 Jan**, **Closed**), the description, **Who can apply**, and **What you will be asked** (the list of questions, so you can prepare). The side panel shows date and location for events, funding ("up to GH₵ ..."), opening date, closing date and number of places.

The button at the bottom of the side panel depends on who you are and the programme state:

| Situation | What you see |
| --- | --- |
| Not signed in, programme open | **Create an account to apply**, and a **Sign in** link |
| Signed in as a member, no application yet | **Apply now** |
| Signed in as a member with a draft | **Continue my draft** |
| You already submitted | "You have applied", your status, and **View my application** |
| Programme not yet open | "Applications open on <date>." |
| Programme closed | "Applications for this programme have closed." |
| All places taken | "All places have been filled." |
| Signed in as staff | "Staff accounts cannot apply." and a link to the workspace |

Programmes in draft or archived state are not visible and return "Page not found". See [Programme applications](programme-applications.md).

## Projects (`/projects`)

Projects are the research and community projects the Hub supports. Only projects marked public appear.

1. Type in the search box ("Search by title, researcher or department"), pick a **focus area** (STEM, Educational Tech, Agriculture, Inclusive Education, Digital Humanities, Community Outreach) or a **status** (Active, Completed).
2. Press **Clear filters** to start again. If nothing matches you see "No projects found. Try a different search or remove a filter."
3. Open a project to see **About the project**, **Partners**, **Publications from this project** and **Impact**. Projects with a report show **Download project report** (a PDF).

## Publications (`/publications`)

1. Search by title, author or journal.
2. Filter by type: Research Paper, Policy Brief, Newsletter, Technical Report or Annual Report.
3. Press a title or its download link to open the PDF. Each download is counted.

Empty result: "No publications found. Try another type or search term."

## News (`/news`)

Announcements, funding calls and stories. Filter by type: News, Blog or Announcement. Open a story to read it. Related stories from the same category appear underneath.

Only posts whose publication time has passed are visible. A post scheduled for the future stays hidden until that time with no further action by staff. Empty page: "Nothing published yet. Check back soon for news from the Hub."

## Partners (`/partners`)

An alphabetical list of partner organisations grouped by type (for example Funding Partner, Academic Institution). Each shows how many public projects it works on and a **Website** link.

## Community and leaderboard (`/community`)

Shows the most active members who chose to appear, and recent badges. See [Badges and leaderboard](badges-and-leaderboard.md).

## Information pages

Pages such as About, FAQ, Privacy and Terms are written by staff in the workspace and reached by a short address like `/privacy` and `/terms`. The footer links to the ones staff chose to list. A page that does not exist or is not published returns "Page not found".

## Gallery and contact

See [Gallery](gallery.md) and [Contact and enquiries](contact-and-enquiries.md).

## Search engines and the open data API

Search engines are only invited to index the site when `SEARCH_INDEXING` is on, which is off by default. Developers can read the same public data as JSON at `/api/v1`. See the [API overview](../../06-api-reference/api-overview.md).

## Errors and fixes

| What you see | Meaning | Fix |
| --- | --- | --- |
| "Page not found" (404) | The page is unpublished, removed, or the address has a typo | Use the menu. Old addresses `/news-and-blogs`, `/apply`, `/track` redirect for you |
| "Too many requests" (429) | Many requests in a short time | Wait a minute |
| A file does not download | The document record has no file or the file is missing | Contact the Hub and name the page |
| Nothing under **Open now** | No programme is open today | Check **Opening soon**, or create an account and choose interests to hear first |
