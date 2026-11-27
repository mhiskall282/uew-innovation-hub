<?php

namespace Database\Seeders;

use App\Enums\GrantStatus;
use App\Enums\PostType;
use App\Enums\ProjectStatus;
use App\Enums\Role;
use App\Models\GrantApplication;
use App\Models\Organization;
use App\Models\Post;
use App\Models\Project;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo accounts (one per role) and sample content based on the legacy
 * project records. Idempotent: safe to run more than once.
 *
 * The report and newsletter PDFs referenced here ship in
 * storage/app/private/{reports,publications}.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('hub.seed_password');

        $staff = [];
        foreach ([
            ['admin@uew.edu.gh', 'Hub Director', Role::SuperAdmin, 'Office of the Hub Director'],
            ['officer@uew.edu.gh', 'Ama Owusu', Role::Officer, 'Innovation Hub Programmes Office'],
            ['reviewer@uew.edu.gh', 'Prof. Kwame Mensah', Role::Reviewer, 'Grant Review Panel'],
            ['intern@uew.edu.gh', 'Kofi Boateng', Role::Intern, 'Hub Communications Desk'],
            ['viewer@uew.edu.gh', 'Esi Asante', Role::Viewer, 'Faculty of Science Education'],
        ] as [$email, $name, $role, $department]) {
            // role/is_active are not mass assignable, so set them explicitly.
            $user = User::firstOrNew(['email' => $email]);
            $user->forceFill([
                'name' => $name, 'role' => $role, 'department' => $department, 'is_active' => true,
                'password' => $password, 'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
            $staff[$role->value] = $user;
        }

        $orgs = collect([
            ['UNESCO', 'Funding Partner', 'https://www.unesco.org'],
            ['Ghana Education Service (GES)', 'Government Agency', 'https://ges.gov.gh'],
            ['Mastercard Foundation', 'Funding Partner', 'https://mastercardfdn.org'],
            ['British Council', 'Partner', 'https://www.britishcouncil.org.gh'],
            ['UNICEF Ghana', 'Funding Partner', 'https://www.unicef.org/ghana'],
            ['Ministry of Environment, Science, Technology and Innovation (MESTI)', 'Government Agency', 'https://mesti.gov.gh'],
            ['University of Education, Winneba', 'Academic Institution', 'https://uew.edu.gh'],
        ])->mapWithKeys(fn ($row) => [$row[0] => Organization::firstOrCreate(['name' => $row[0]], ['type' => $row[1], 'website' => $row[2]])]);

        $projects = [
            [19, 'STEM Robotics and Coding Incubator for High Schools', 'STEM', ProjectStatus::Active, '2024-03-01', '2025-06-30', 1500, 1250, 185000, 'Dr. Emmanuel Kusi', 'Department of ICT Education', 'project_19_1790777535.pdf',
                "Hands-on training using low-cost microcontroller kits and an open curriculum for Senior High School STEM teachers and students.\n\nTeachers complete a six-week course and then run after-school robotics clubs in their own schools.",
                ['UNESCO' => 'Funding Partner', 'Ghana Education Service (GES)' => 'Implementing Partner', 'University of Education, Winneba' => 'Host Institution'], true],
            [23, 'Digital Literacy for Rural Basic School Teachers', 'Educational Tech', ProjectStatus::Active, '2024-05-15', '2025-11-20', 4000, 3400, 240000, 'Dr. Aba Cudjoe', 'Department of Basic Education', 'project_23_1790872941.pdf',
                'Training for primary school teachers in rural Central and Western regions on offline-first digital teaching tools.',
                ['Mastercard Foundation' => 'Funding Partner', 'Ghana Education Service (GES)' => 'Implementing Partner'], true],
            [24, 'Adaptive Learning Platforms for Visually Impaired Students', 'Inclusive Education', ProjectStatus::Completed, '2024-08-01', '2025-08-30', 680, 680, 145000, 'Prof. Samuel Hayford', 'Department of Special Education', 'project_24_1790873274.pdf',
                'Tactile interfaces and screen-reader friendly courseware for learners with visual impairments.',
                ['UNICEF Ghana' => 'Funding Partner', 'University of Education, Winneba' => 'Research Partner'], true],
            [25, 'Sustainable Agri-tech Hydroponics Demonstration Farm', 'Agriculture', ProjectStatus::Active, '2025-01-10', '2026-04-15', 2500, 2150, 310000, 'Dr. Frank Appiah', 'Department of Agricultural Science Education', 'project_25_1790874381.pdf',
                'A climate-controlled vertical farm and sensor test bed for youth employment and agricultural teaching.',
                ['Ministry of Environment, Science, Technology and Innovation (MESTI)' => 'Government Partner', 'British Council' => 'Technical Advisor'], true],
            [26, 'Local-language AI Translation for Primary Teaching', 'Educational Tech', ProjectStatus::Active, '2025-02-01', '2026-09-30', 10000, 8500, 420000, 'Dr. John Okyere', 'Department of Ghanaian Languages', 'project_26_1790874884.pdf',
                'Fine-tuning open translation models on Akan, Ewe and Ga classroom material to support mother-tongue bilingual education.',
                ['Mastercard Foundation' => 'Funding Partner', 'UNESCO' => 'Technical Advisor'], true],
            [28, 'Community Clean Water Sensing and Solar Pumping', 'Community Outreach', ProjectStatus::Active, '2025-03-01', '2026-12-31', 15000, 14200, 375000, 'Dr. Beatrice Arthur', 'Department of Integrated Science Education', 'project_28_1790938305.pdf',
                'Real-time water quality monitoring with solar-powered filtration stations for coastal communities around Winneba.',
                ['Ministry of Environment, Science, Technology and Innovation (MESTI)' => 'Government Partner', 'UNICEF Ghana' => 'Funding Partner'], true],
            [null, 'Teacher Wellbeing Survey Platform (pilot)', 'Educational Tech', ProjectStatus::Pending, '2026-01-15', null, 800, 0, 45000, 'Dr. Akosua Frimpong', 'Department of Psychology and Education', null,
                'Internal pilot of an anonymous survey tool. Not yet announced publicly.',
                ['Ghana Education Service (GES)' => 'Research Partner'], false],
        ];

        foreach ($projects as [$legacyId, $title, $category, $status, $start, $end, $target, $reached, $grant, $lead, $department, $report, $summary, $partners, $public]) {
            $project = Project::updateOrCreate(['title' => $title], [
                'legacy_id' => $legacyId,
                'summary' => $summary,
                'category' => $category,
                'status' => $status,
                'start_date' => $start,
                'end_date' => $end,
                'beneficiaries_target' => $target,
                'beneficiaries_reached' => $reached,
                'grant_amount' => $grant,
                'lead_researcher' => $lead,
                'department' => $department,
                'report_file_path' => $report,
                'is_public' => $public,
                'created_by' => $staff[Role::Officer->value]->id,
            ]);

            $project->organizations()->sync(
                collect($partners)->mapWithKeys(fn ($role, $name) => [$orgs[$name]->id => ['role' => $role]])->all()
            );
        }

        $robotics = Project::where('legacy_id', 19)->first();
        $inclusive = Project::where('legacy_id', 24)->first();

        foreach ([
            ['UEW Innovation Hub Newsletter (Vol. 4, 2025)', 'Newsletter', 'UEW Innovation Hub team', '2025-12-15', 'UEW Innovation Quarterly', null, '1791276659_uew_innovation_hub_news_letter_2025_4.pdf', $robotics?->id, 148,
                'Highlights from the year: new robotics clubs, the hydroponics farm opening and the 2026 fellowship call.'],
            ['Impact of Assistive Education Technology in the Central Region of Ghana', 'Research Paper', 'Prof. Samuel Hayford, Dr. John Okyere', '2025-09-20', 'African Journal of Special Educational Needs', 'https://doi.org/10.1000/182', null, $inclusive?->id, 76,
                'A mixed-methods study of learning outcomes for 680 learners using adaptive courseware over twelve months.'],
            ['Policy Framework for University-Industry Innovation Clusters in Sub-Saharan Africa', 'Policy Brief', 'UEW Innovation Hub', '2025-11-05', 'West African Higher Education Policy Review', 'https://doi.org/10.1000/183', null, null, 93,
                'Recommendations for how universities can structure partnerships with industry to commercialise education research.'],
        ] as [$title, $type, $authors, $date, $journal, $doi, $pdf, $projectId, $downloads, $abstract]) {
            Publication::updateOrCreate(['title' => $title], [
                'type' => $type, 'authors' => $authors, 'publish_date' => $date, 'journal_name' => $journal,
                'doi_url' => $doi, 'pdf_file_path' => $pdf, 'project_id' => $projectId, 'downloads_count' => $downloads, 'abstract' => $abstract,
            ]);
        }

        foreach ([
            ['UEW Innovation Hub wins EdTech acceleration grant', PostType::News, 'Grants & Awards', $staff[Role::SuperAdmin->value], now()->subDays(2), true,
                'UEW has been selected to lead a West Africa consortium on digital teaching.',
                "The University of Education, Winneba has been selected to lead a West African consortium on digital teaching.\n\n## What the grant funds\n\n- Six regional maker labs\n- Training for **5,000 in-service STEM teachers** in low-cost robotics\n- A shared, openly licensed curriculum\n\nWork starts in the first quarter of next year."],
            ['Teaching in the mother tongue with AI', PostType::Blog, 'Technology & Society', $staff[Role::Officer->value], now()->subDays(5), true,
                'Dr. John Okyere on building translation models for Akan and Ewe classrooms.',
                "Language barriers remain one of the biggest obstacles to early literacy in rural schools.\n\nBy adapting offline translation models to local languages, our researchers are helping teachers explain new concepts in the language children speak at home, without needing a constant internet connection.\n\n> Children learn best in the language they think in."],
            ['Call for applications: 2026 Student Innovation Fellowship', PostType::Announcement, 'Opportunities', $staff[Role::Officer->value], now()->subDay(), true,
                'UEW students can apply for seed funding, lab space and mentorship.',
                "Applications are open for the 2026 Innovation Fellowship.\n\nSuccessful teams receive **up to GH₵ 25,000** in seed funding, lab space at the Hub and mentorship from industry partners.\n\n[Apply online](/apply) before the end of the month."],
            ['Hydroponics farm open day (draft)', PostType::News, 'Events', $staff[Role::Intern->value], null, false,
                'Draft write-up of the farm open day for an officer to review.',
                "Over 200 students and farmers visited the hydroponics demonstration farm last Saturday.\n\nEditor's note: quotes from Dr. Appiah to be added before publishing."],
        ] as [$title, $type, $category, $author, $publishedAt, $featured, $summary, $content]) {
            Post::updateOrCreate(['title' => $title], [
                'type' => $type, 'category' => $category, 'author_id' => $author->id, 'published_at' => $publishedAt,
                'is_featured' => $featured, 'summary' => $summary, 'content' => $content,
            ]);
        }

        foreach ([
            ['UEW-GR-2026-PV7K2M', 'Ebenezer Addo', 'e.addo@example.com', '+233 24 123 4567', 'Department of Physics Education, UEW', 'Portable Solar Micro-Lab for Off-Grid Schools', 'STEM', 18500,
                'A compact, rugged solar training kit that lets high school students build and test renewable energy circuits in classrooms without electricity.', GrantStatus::Approved,
                'Strong fit with the national STEM expansion agenda. Budget is realistic.', $staff[Role::Reviewer->value]],
            ['UEW-GR-2026-AQ4H8R', 'Grace Mensah-Bonsu', 'grace@example.com', '+233 20 987 6543', 'Winneba Youth Agri-Innovators', 'Aquaponics Tilapia and Vegetable Closed-Loop System', 'Agriculture', 32000,
                'A recirculating aquaculture system that uses black soldier fly larvae as low-cost fish feed for youth-run farms.', GrantStatus::UnderReview,
                'Site visit scheduled to check feasibility.', $staff[Role::Officer->value]],
            ['UEW-GR-2026-SL3N6T', 'Yaw Darko', 'yaw.darko@example.com', '+233 27 555 0101', 'Department of Special Education, UEW', 'Sign Language Learning App for Basic Schools', 'Inclusive Education', 12000,
                'A mobile app that teaches Ghanaian Sign Language basics to hearing pupils and teachers through short video lessons and quizzes.', GrantStatus::Submitted,
                null, null],
        ] as [$ref, $name, $email, $phone, $org, $title, $category, $amount, $summary, $status, $notes, $reviewer]) {
            GrantApplication::updateOrCreate(['reference_no' => $ref], [
                'applicant_name' => $name, 'email' => $email, 'phone' => $phone, 'institution_or_org' => $org,
                'project_title' => $title, 'category' => $category, 'funding_requested' => $amount, 'summary' => $summary,
                'status' => $status, 'reviewer_notes' => $notes, 'reviewed_by' => $reviewer?->id,
                'reviewed_at' => $reviewer ? now()->subDays(3) : null,
            ]);
        }
    }
}
