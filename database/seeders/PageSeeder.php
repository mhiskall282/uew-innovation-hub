<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Default CMS pages. Safe in production: only creates pages that do not
 * exist yet, never overwrites edits made in the workspace.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('hub.contact_email');

        $pages = [
            ['about', 'About the Hub', 'How the UEW Innovation Hub supports research, teaching and enterprise.', 10, <<<'MD'
The **UEW Innovation Hub** is the University of Education, Winneba's home for turning research and ideas into projects that improve teaching, learning and communities across Ghana.

## What we do

- **Fund** promising ideas through grants, fellowships and competitions
- **Incubate** student and staff ventures with lab space, mentors and partners
- **Train** teachers, researchers and entrepreneurs in practical skills
- **Share** what works through publications, events and open data

## Who we work with

Our programmes are open to UEW students, staff and alumni, and many welcome partner organisations and members of the public. We work with government agencies, development partners, industry and schools.

## Get involved

[Create a free account](/sign-up) to apply for programmes and hear about new opportunities, or [contact us](/contact) to discuss a partnership.
MD],
            ['faq', 'Frequently asked questions', 'Accounts, applications and programmes.', 20, <<<'MD'
## Do I need an account to apply?

Yes. A free account lets you save drafts, upload documents, follow the status of every application and receive updates. Sign-up takes a minute and we confirm your e-mail with a 6-digit code.

## Who can apply?

Each programme lists its own eligibility. Many are open to UEW students, staff and alumni; some also welcome partners and the public.

## Can I change my application after submitting?

No. Save a draft until you are ready; once submitted, your answers are fixed so every applicant is reviewed fairly. You can withdraw an application while it is under review.

## How will I hear about the outcome?

You receive an e-mail when the status changes, and you can check it any time under **My applications**.

## I didn't receive my code.

Check your spam folder, then use **Send a new code**. Codes expire after 10 minutes.

## How do I stop announcement e-mails?

Use the unsubscribe link in any announcement, or switch announcements off in **Profile & settings**. Messages about your own applications are always sent.
MD],
            ['privacy', 'Privacy notice', 'How we collect and use personal data, in line with the Data Protection Act, 2012 (Act 843).', 30, <<<MD
This notice explains how the University of Education, Winneba (UEW) Innovation Hub processes personal data in line with the **Data Protection Act, 2012 (Act 843)**.

## What we collect

- **Account details:** name, e-mail address, password (stored as a one-way hash)
- **Profile details:** phone number, gender, date of birth, region, affiliation, institution, department, education level, disability status (optional), short bio and interests
- **Applications:** your answers, any documents you upload, and a copy of your profile details at the time you submit
- **Programme participation:** messages with Hub staff, progress and budget reports, badges and XP
- **Enquiries:** messages sent through the contact form
- **Technical data:** IP address and sign-in times, kept for security

## Why we use it

To run your account, assess applications, contact you about decisions, send announcements you asked for, and keep the platform secure.

## Who can see it

Only Hub staff whose role requires it. Applications are visible to the review panel, who do **not** see your gender or disability status. Gender, age, region and disability are reported to funders only as anonymous totals. If you switch on the community page, your name, affiliation, institution, bio, XP and badges become public; you can switch it off at any time. We do not sell your data.
## Cookies
We use only the cookies needed to run the site: a session cookie that keeps you signed in and a security cookie that protects forms against forgery. There are no advertising or analytics cookies.

## How long we keep it

Account and application records are kept while your account is active and for the period required by university policy.

## Your rights

You can view and update your profile at any time, unsubscribe from announcements, and ask us to correct or delete your data by e-mailing [{$email}](mailto:{$email}).
MD],
            ['terms', 'Terms of use', 'The rules for using the UEW Innovation Hub website and accounts.', 40, <<<'MD'
By creating an account or using this website you agree to these terms.

## Your account

Keep your password private and tell us straight away if you think someone else has used your account. You are responsible for activity on your account.

## Applications

Information you submit must be accurate and your own work. The Hub may reject or withdraw an application that is misleading or plagiarised. Decisions of the review panel are final.

## Acceptable use

Do not attempt to access other people's accounts or data, upload harmful files, or disrupt the service.

## Content

Text and documents on this site are owned by UEW or their authors. Public publications may be shared with attribution.

## Changes

We may update these terms. Significant changes will be announced on the website.
MD],
        ];

        foreach ($pages as [$slug, $title, $summary, $order, $body]) {
            Page::firstOrCreate(['slug' => $slug], [
                'title' => $title, 'summary' => $summary, 'body' => $body,
                'is_published' => true, 'show_in_footer' => true, 'sort_order' => $order,
            ]);
        }
    }
}
