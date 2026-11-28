# Gallery

> **Audience:** Everyone, no account needed · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [Browsing the public website](browsing-the-public-website.md), [Contact and enquiries](contact-and-enquiries.md), [Content management](../../03-administration/content-management.md), [Common user issues](../common-user-issues.md)

This page explains the public photo gallery: how to browse albums, open photos in the viewer and what to do when a photo is missing. A short note at the end tells staff how drafts behave.

- **What it does:** shows photo albums from Hub events such as pitch days, bootcamps, workshops and community projects.
- **Why:** to show the Hub's work and let participants find photos of their event.
- **Who:** everyone can view published albums. Staff with the gallery-drafting permission can also preview unpublished albums.
- **Where:** **Gallery** in the top menu, or `/gallery`. The home page has a "Moments from the Hub" strip that links to albums.

## Browse albums

1. Open **Gallery**. The heading reads "Moments from the Hub".
2. Each card shows the cover photo, the album title, the number of photos, and the location when there is one.
3. Newest events come first. There are 12 albums per page. Use the page links at the bottom for more.
4. Press a card to open the album.

If no album is published: "No albums yet. Photos from our events will appear here."

An album only shows if it is published and has a cover photo. If the picture files for the cover are missing from storage, the album is hidden from the list rather than shown broken.

## Open an album

An album page (`/gallery/<album-name>`) shows the title, the date and location of the event, a description, a link to the related programme when there is a public one, and a grid of photos with captions. At the bottom, "More albums" suggests up to 3 others.

1. Press a photo to open the viewer.
2. Use the arrow buttons, or the **Left** and **Right** arrow keys, to move between photos.
3. Press **Close**, press **Esc**, or click outside the picture to leave the viewer.

Photos that are missing from storage are left out of the grid. The photo files are served from `/media/photos/<id>/thumb` (small) and `/media/photos/<id>/large` (full size). Browsers keep published photos for a long time, so a replaced photo may take a while to change on your device.

## Who can see what

| Visitor | Published album | Unpublished album |
| --- | --- | --- |
| Anyone | Yes | "Page not found" |
| Staff with permission to draft content (Hub Director, Programme Officer, Communications Intern) | Yes | Yes, as a preview. The photo files are not cached |
| Members | Yes | No |

## Errors and fixes

| What you see | Cause | Fix |
| --- | --- | --- |
| "Page not found" for an album | The album is unpublished or its address changed | Go back to **Gallery** and look again |
| An album you saw before has gone | Staff unpublished it or its photo files are missing | Contact the Hub if you need a photo |
| A photo does not load | The file is missing on the server | Reload. If it still fails, tell the Hub which album and photo |
| "Too many requests" | Many requests in a short time | Wait a minute |
| I want a photo of me removed | Photos are managed by Hub staff | Use the [contact form](contact-and-enquiries.md) and name the album and photo |

> **Requires confirmation:** the public pages show no download button. Whether a visitor may save or reuse photos is a Hub policy that the code does not state.

## For staff

Staff create albums, upload photos and publish them in the workspace under **Website, Gallery**. Uploading is limited to 20 requests per minute. Publishing needs `content.publish`; drafting needs `content.draft`. The staff steps are in [Content management](../../03-administration/content-management.md).
