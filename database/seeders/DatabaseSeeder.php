<?php

namespace Database\Seeders;

use App\Models\GrantApplication;
use App\Models\Organization;
use App\Models\Post;
use App\Models\Project;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@uew.edu.gh'],
            [
                'name' => 'UEW Hub Director',
                'role' => 'super_admin',
                'department' => 'Directorate of Research & Innovation',
                'password' => Hash::make('password'),
            ]
        );

        $officer = User::firstOrCreate(
            ['email' => 'officer@uew.edu.gh'],
            [
                'name' => 'Lead Research Officer',
                'role' => 'officer',
                'department' => 'Office of Dean of Faculty of Applied Sciences',
                'password' => Hash::make('password'),
            ]
        );

        $reviewer = User::firstOrCreate(
            ['email' => 'reviewer@uew.edu.gh'],
            [
                'name' => 'Prof. K. Mensah (Dean)',
                'role' => 'reviewer',
                'department' => 'Academic Affairs Committee',
                'password' => Hash::make('password'),
            ]
        );

        $intern = User::firstOrCreate(
            ['email' => 'intern@uew.edu.gh'],
            [
                'name' => 'Kofi Boateng (NSS / Intern)',
                'role' => 'intern',
                'department' => 'Hub Media & Research Desk',
                'password' => Hash::make('password'),
            ]
        );

        $viewer = User::firstOrCreate(
            ['email' => 'viewer@uew.edu.gh'],
            [
                'name' => 'Guest Academic Researcher',
                'role' => 'viewer',
                'department' => 'External Academic Partner',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Seed Organizations
        $unesco = Organization::firstOrCreate(
            ['name' => 'UNESCO'],
            ['type' => 'Funding Partner', 'website' => 'https://unesco.org', 'contact_email' => 'accra@unesco.org']
        );

        $ges = Organization::firstOrCreate(
            ['name' => 'Ghana Education Service (GES)'],
            ['type' => 'Government Agency', 'website' => 'https://ges.gov.gh', 'contact_email' => 'info@ges.gov.gh']
        );

        $mcf = Organization::firstOrCreate(
            ['name' => 'Mastercard Foundation'],
            ['type' => 'Funding Partner', 'website' => 'https://mastercardfdn.org', 'contact_email' => 'partnerships@mastercardfdn.org']
        );

        $bc = Organization::firstOrCreate(
            ['name' => 'British Council'],
            ['type' => 'Partner', 'website' => 'https://britishcouncil.org.gh', 'contact_email' => 'info@britishcouncil.org']
        );

        $unicef = Organization::firstOrCreate(
            ['name' => 'UNICEF Ghana'],
            ['type' => 'Funding Partner', 'website' => 'https://unicef.org/ghana', 'contact_email' => 'accra@unicef.org']
        );

        $mesti = Organization::firstOrCreate(
            ['name' => 'Ministry of Environment, Science, Technology and Innovation (MESTI)'],
            ['type' => 'Government Agency', 'website' => 'https://mesti.gov.gh', 'contact_email' => 'contact@mesti.gov.gh']
        );

        $uew = Organization::firstOrCreate(
            ['name' => 'University of Education, Winneba'],
            ['type' => 'Academic Institution', 'website' => 'https://uew.edu.gh', 'contact_email' => 'inno-hub@uew.edu.gh']
        );

        // 3. Seed Projects
        $projectsData = [
            [
                'legacy_id' => 19,
                'title' => 'STEM Robotics & Coding Incubator for High Schools',
                'summary' => 'Hands-on experiential training modules utilizing custom microcontroller kits and open-source curriculum for Senior High School STEM teachers and students.',
                'category' => 'STEM',
                'status' => 'active',
                'start_date' => '2024-03-01',
                'end_date' => '2025-06-30',
                'beneficiaries_target' => 1500,
                'beneficiaries_reached' => 1250,
                'grant_amount' => 185000.00,
                'lead_researcher' => 'Dr. Emmanuel Kusi',
                'department' => 'Department of ICT Education',
                'report_file_path' => 'project_19_1790777535.pdf',
                'orgs' => [
                    $unesco->id => 'Primary Funding Partner',
                    $ges->id => 'Implementing Partner',
                    $uew->id => 'Host Institution'
                ],
            ],
            [
                'legacy_id' => 23,
                'title' => 'Digital Literacy for Rural Basic School Educators',
                'summary' => 'Comprehensive upskilling curriculum equipping primary school educators across rural Central and Western regions with offline-first digital teaching tools.',
                'category' => 'Educational Tech',
                'status' => 'active',
                'start_date' => '2024-05-15',
                'end_date' => '2025-11-20',
                'beneficiaries_target' => 4000,
                'beneficiaries_reached' => 3400,
                'grant_amount' => 240000.00,
                'lead_researcher' => 'Dr. Aba Cudjoe',
                'department' => 'Department of Basic Education',
                'report_file_path' => 'project_23_1790872941.pdf',
                'orgs' => [
                    $mcf->id => 'Funding Partner',
                    $ges->id => 'Operational Partner',
                ],
            ],
            [
                'legacy_id' => 24,
                'title' => 'Adaptive Learning Platforms for Visually Impaired Students',
                'summary' => 'Development and deployment of tactile haptic interfaces and screen-reader optimized digital learning courseware for special education learners.',
                'category' => 'Inclusive Education',
                'status' => 'completed',
                'start_date' => '2024-08-01',
                'end_date' => '2025-08-30',
                'beneficiaries_target' => 680,
                'beneficiaries_reached' => 680,
                'grant_amount' => 145000.00,
                'lead_researcher' => 'Prof. Samuel Hayford',
                'department' => 'Department of Special Education',
                'report_file_path' => 'project_24_1790873274.pdf',
                'orgs' => [
                    $unicef->id => 'Funding Partner',
                    $uew->id => 'Research Lead',
                ],
            ],
            [
                'legacy_id' => 25,
                'title' => 'Sustainable Agrotech Hydroponics Demonstration Farm',
                'summary' => 'Smart climate-controlled vertical farming and hydroponic sensor testbed designed for sustainable youth employment and agricultural pedagogy.',
                'category' => 'Agriculture',
                'status' => 'active',
                'start_date' => '2025-01-10',
                'end_date' => '2026-04-15',
                'beneficiaries_target' => 2500,
                'beneficiaries_reached' => 2150,
                'grant_amount' => 310000.00,
                'lead_researcher' => 'Dr. Frank Appiah',
                'department' => 'Department of Agricultural Science Education',
                'report_file_path' => 'project_25_1790874381.pdf',
                'orgs' => [
                    $mesti->id => 'Government Co-Sponsor',
                    $bc->id => 'Technical Advisor',
                ],
            ],
            [
                'legacy_id' => 26,
                'title' => 'AI-Powered Local Language Translation for Primary Pedagogy',
                'summary' => 'Fine-tuning open neural machine translation models on Akan, Ewe, and Ga educational corpora to support mother-tongue bilingual education.',
                'category' => 'Educational Tech',
                'status' => 'active',
                'start_date' => '2025-02-01',
                'end_date' => '2026-09-30',
                'beneficiaries_target' => 10000,
                'beneficiaries_reached' => 8500,
                'grant_amount' => 420000.00,
                'lead_researcher' => 'Dr. John Okyere',
                'department' => 'Department of Ghanaian Languages & Linguistics',
                'report_file_path' => 'project_26_1790874884.pdf',
                'orgs' => [
                    $mcf->id => 'Grant Funder',
                    $unesco->id => 'Strategic Advisor',
                ],
            ],
            [
                'legacy_id' => 28,
                'title' => 'Community Clean Water IoT Sensing & Solar Pumping Station',
                'summary' => 'Real-time water quality monitoring network coupled with automated solar-powered filtration stations serving coastal peri-urban Winneba communities.',
                'category' => 'Community Outreach',
                'status' => 'active',
                'start_date' => '2025-03-01',
                'end_date' => '2026-12-31',
                'beneficiaries_target' => 15000,
                'beneficiaries_reached' => 14200,
                'grant_amount' => 375000.00,
                'lead_researcher' => 'Dr. Beatrice Arthur',
                'department' => 'Department of Integrated Science Education',
                'report_file_path' => 'project_28_1790938305.pdf',
                'orgs' => [
                    $mesti->id => 'Government Partner',
                    $unicef->id => 'WASH Partner',
                ],
            ],
        ];

        foreach ($projectsData as $item) {
            $orgs = $item['orgs'];
            unset($item['orgs']);
            $item['created_by'] = $admin->id;

            $project = Project::firstOrCreate(
                ['title' => $item['title']],
                $item
            );

            foreach ($orgs as $orgId => $role) {
                if (!$project->organizations()->where('organizations.id', $orgId)->exists()) {
                    $project->organizations()->attach($orgId, ['role' => $role]);
                }
            }
        }

        // 4. Seed Publications
        $p1 = Project::where('legacy_id', 19)->first();
        $p4 = Project::where('legacy_id', 24)->first();

        Publication::firstOrCreate(
            ['title' => 'UEW Innovation Hub Newsletter (Vol. 4, 2025)'],
            [
                'project_id' => $p1?->id,
                'type' => 'Newsletter',
                'authors' => 'Directorate of Research & Innovation Team',
                'publish_date' => '2025-12-15',
                'journal_name' => 'UEW Innovation Quarterly',
                'pdf_file_path' => '1791276659_uew_innovation_hub_news_letter_2025_4.pdf',
                'downloads_count' => 148,
            ]
        );

        Publication::firstOrCreate(
            ['title' => 'Empirical Impact Assessment of Assistive EdTech in Central Region Ghana'],
            [
                'project_id' => $p4?->id,
                'type' => 'Research Paper',
                'authors' => 'Prof. Samuel Hayford, Dr. John Okyere',
                'publish_date' => '2025-09-20',
                'journal_name' => 'African Journal of Special Educational Needs',
                'doi_url' => 'https://doi.org/10.1000/182',
                'pdf_file_path' => null,
                'downloads_count' => 76,
            ]
        );

        Publication::firstOrCreate(
            ['title' => 'Policy Framework for University-Industry Innovation Clusters in Sub-Saharan Africa'],
            [
                'project_id' => null,
                'type' => 'Policy Brief',
                'authors' => 'UEW Strategic Innovation Directorate',
                'publish_date' => '2025-11-05',
                'journal_name' => 'West African Higher Education Policy Review',
                'doi_url' => 'https://doi.org/10.1000/183',
                'pdf_file_path' => null,
                'downloads_count' => 93,
            ]
        );

        // 5. Seed CMS Posts (News & Blogs)
        Post::firstOrCreate(
            ['title' => 'UEW Innovation Hub Wins $500,000 EdTech Acceleration Grant'],
            [
                'type' => 'news',
                'category' => 'Grants & Awards',
                'summary' => 'The University of Education, Winneba has been selected as the lead consortium hub for the West Africa Digital Pedagogy initiative.',
                'content' => 'In a landmark ceremony held at the Main Campus in Winneba, the Directorate of Research & Innovation announced the award of half a million dollars in competitive grant funding. The initiative will establish six regional MakerLabs and train over 5,000 in-service STEM teachers in low-cost robotics and computational pedagogy.',
                'author_id' => $admin->id,
                'is_featured' => true,
                'published_at' => now()->subDays(2),
            ]
        );

        Post::firstOrCreate(
            ['title' => 'Democratizing Artificial Intelligence for Ghanaian Basic Education'],
            [
                'type' => 'blog',
                'category' => 'Technology & Society',
                'summary' => 'Reflections from Dr. John Okyere on fine-tuning neural translation models for indigenous Akan and Ewe classroom instruction.',
                'content' => 'Language barriers remain one of the most persistent hurdles to foundational literacy and numeracy across rural Ghanaian primary schools. By tailoring offline language models to local dialects, our researchers are bridging the digital divide without requiring constant internet access.',
                'author_id' => $officer->id,
                'is_featured' => true,
                'published_at' => now()->subDays(5),
            ]
        );

        Post::firstOrCreate(
            ['title' => 'Call for Applications: 2026 Student Innovation & AgriTech Fellowship'],
            [
                'type' => 'announcement',
                'category' => 'Opportunities',
                'summary' => 'Undergraduate and postgraduate students at UEW are invited to submit incubation proposals for solar farming and IoT water monitoring.',
                'content' => 'Applications are now open for the 2026 Innovation Fellowship cohort. Successful teams receive seed funding of up to GH₵ 25,000, dedicated laboratory space at the Innovation Hub, and industry mentorship.',
                'author_id' => $intern->id,
                'is_featured' => true,
                'published_at' => now()->subDay(),
            ]
        );

        // 6. Seed Grant Applications
        GrantApplication::firstOrCreate(
            ['reference_no' => 'UEW-GR-829104-2026'],
            [
                'applicant_name' => 'Ebenezer Addo',
                'email' => 'e.addo@gmail.com',
                'phone' => '+233 24 123 4567',
                'institution_or_org' => 'Department of Physics Education, UEW',
                'project_title' => 'Portable Photovoltaic Micro-Lab for Off-Grid Schools',
                'category' => 'STEM',
                'funding_requested' => 18500.00,
                'summary' => 'A compact, ruggedized solar training kit that allows high school students to build and test renewable circuits in un-electrified rural classrooms.',
                'status' => 'approved',
                'reviewer_notes' => 'Highly viable and directly aligns with the Ministry of Education STEM expansion agenda.',
                'reviewed_by' => $reviewer->id,
            ]
        );

        GrantApplication::firstOrCreate(
            ['reference_no' => 'UEW-GR-419082-2026'],
            [
                'applicant_name' => 'Grace Mensah-Bonsu',
                'email' => 'grace.mbonsu@agritechgh.com',
                'phone' => '+233 20 987 6543',
                'institution_or_org' => 'Winneba Youth Agri-Innovators',
                'project_title' => 'Aquaponics Tilapia & Organic Vegetable Closed-Loop System',
                'category' => 'Agriculture',
                'funding_requested' => 32000.00,
                'summary' => 'Sustainable recirculating aquaculture facility utilizing black soldier fly larvae as zero-cost protein feed for commercial youth farming.',
                'status' => 'under_review',
                'reviewer_notes' => 'Proposal documents received. Site visit scheduled for feasibility appraisal.',
                'reviewed_by' => $officer->id,
            ]
        );
    }
}
