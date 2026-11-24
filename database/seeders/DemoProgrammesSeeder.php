<?php

namespace Database\Seeders;

use App\Enums\AchievementType;
use App\Enums\ApplicationStatus;
use App\Enums\ProgramStatus;
use App\Enums\ProgramType;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\Achievement;
use App\Models\ApplicationMessage;
use App\Models\Program;
use App\Models\ProgramApplication;
use App\Models\ProgressReport;
use App\Models\User;
use App\Services\EngagementService;
use App\Support\Demographics;
use App\Support\FormSchema;
use Illuminate\Database\Seeder;

/** Demo programmes, member accounts and applications. Idempotent. */
class DemoProgrammesSeeder extends Seeder
{
    public function run(): void
    {
        $officer = User::where('email', 'officer@uew.edu.gh')->first();

        $programmes = [
            [
                'title' => '2026 Student Innovation Fellowship',
                'type' => ProgramType::Fellowship, 'focus_area' => 'Educational Tech', 'is_featured' => true,
                'summary' => 'Up to GH₵ 25,000 seed funding, lab space and mentorship for student teams building education technology.',
                'description' => "The Fellowship supports UEW student teams with an idea that could improve teaching or learning in Ghana.\n\n## What you get\n\n- Seed funding of up to **GH₵ 25,000**\n- Six months of lab space at the Innovation Hub\n- A mentor from industry or academia\n- Showcase at Demo Day\n\n## Timeline\n\n1. Applications close at the deadline below\n2. Shortlisted teams pitch to the panel\n3. Fellows start the following month",
                'eligibility' => "- Teams of 1 to 4, led by a current UEW student\n- An early prototype or a clear plan to build one",
                'funding_amount' => 25000, 'opens_at' => now()->subWeeks(2), 'closes_at' => now()->addWeeks(3), 'capacity' => 200,
                'fields' => [
                    ['key' => 'team_name', 'label' => 'Team or project name', 'type' => 'short_text', 'required' => true],
                    ['key' => 'problem', 'label' => 'What problem are you solving, and for whom?', 'type' => 'long_text', 'required' => true, 'help' => 'Describe the learners, teachers or schools who benefit.'],
                    ['key' => 'stage', 'label' => 'Stage of your idea', 'type' => 'radio', 'required' => true, 'options' => ['Idea only', 'Early prototype', 'Tested with users']],
                    ['key' => 'team_size', 'label' => 'Team size', 'type' => 'number', 'required' => true],
                    ['key' => 'skills', 'label' => 'Which skills does your team have?', 'type' => 'checkboxes', 'options' => ['Software', 'Hardware', 'Design', 'Teaching', 'Business']],
                    ['key' => 'pitch_deck', 'label' => 'Pitch deck or proposal', 'type' => 'file', 'help' => 'Optional PDF, up to 10 slides.'],
                ],
            ],
            [
                'title' => 'AgriTech Seed Grant: Climate-smart Farming',
                'type' => ProgramType::Grant, 'focus_area' => 'Agriculture',
                'summary' => 'Grants for research teams testing climate-smart agriculture with farming communities in the Central Region.',
                'description' => "Funding for applied research that helps smallholder farmers adapt to a changing climate.\n\nProjects should run for 6 to 12 months and include community partners.",
                'eligibility' => 'UEW faculty and postgraduate researchers. Partner organisations may join as co-applicants.',
                'funding_amount' => 60000, 'opens_at' => now()->subDays(3), 'closes_at' => now()->addDays(5),
                'fields' => FormSchema::starter(),
            ],
            [
                'title' => 'Innovation Bootcamp: From Idea to Prototype',
                'type' => ProgramType::Event, 'focus_area' => 'STEM', 'location' => 'Innovation Hub, North Campus',
                'summary' => 'A one-day hands-on workshop on design thinking, rapid prototyping and pitching. Free; places are limited.',
                'description' => "Join mentors from the Hub and partner companies for a practical day of building.\n\n- Design thinking warm-up\n- Rapid prototyping with low-cost kits\n- Two-minute pitch practice",
                'opens_at' => now()->subDays(10), 'closes_at' => now()->addDays(12), 'starts_at' => now()->addDays(14)->setTime(9, 0), 'capacity' => 60,
                'fields' => [
                    ['key' => 'motivation', 'label' => 'Why do you want to attend?', 'type' => 'long_text', 'required' => true],
                    ['key' => 'needs', 'label' => 'Dietary or accessibility needs', 'type' => 'short_text'],
                ],
            ],
            [
                'title' => 'Inclusive Education Research Fund 2026',
                'type' => ProgramType::Grant, 'focus_area' => 'Inclusive Education',
                'summary' => 'Funding for research on assistive technology and inclusive teaching. Opens soon.',
                'description' => 'Details will be published when the call opens.',
                'funding_amount' => 80000, 'opens_at' => now()->addWeeks(2), 'closes_at' => now()->addWeeks(8),
                'fields' => FormSchema::starter(),
            ],
            [
                'title' => '2025 Community Water Challenge',
                'type' => ProgramType::Competition, 'focus_area' => 'Community Outreach',
                'summary' => 'Student teams designed low-cost water quality solutions for coastal communities.',
                'description' => 'This competition has closed. Winners were announced at the Hub open day.',
                'funding_amount' => 15000, 'opens_at' => now()->subMonths(4), 'closes_at' => now()->subMonths(2),
                'fields' => FormSchema::starter(),
            ],
        ];

        $created = [];
        foreach ($programmes as $data) {
            $fields = $data['fields'];
            unset($data['fields']);
            $program = Program::firstOrNew(['title' => $data['title']]);
            $program->fill($data + ['form_schema' => FormSchema::normalize($fields)]);
            $program->status = ProgramStatus::Published;
            $program->published_at ??= now()->subWeeks(2);
            $program->created_by = $officer?->id;
            $program->save();
            $created[] = $program;
        }

        // Demo members: one complete profile with applications, one awaiting onboarding.
        $member = $this->member('member@uew.edu.gh', 'Abena Sarpong', [
            'phone' => '+233 24 555 0199', 'affiliation' => 'student', 'institution' => 'University of Education, Winneba',
            'department' => 'BSc Information Technology Education', 'bio' => 'Final-year student building offline maths games for basic schools.',
            'gender' => 'female', 'date_of_birth' => '2003-04-12', 'region' => 'Central', 'education_level' => 'undergraduate', 'disability' => 'no',
            'interests' => ['Educational Tech', 'STEM', 'Fellowships', 'Events'], 'marketing_opt_in' => true, 'onboarded_at' => now()->subDays(20),
        ]);
        $this->member('newmember@uew.edu.gh', 'Yaw Boateng', [
            'onboarded_at' => null, 'phone' => '+233 24 555 0288', 'gender' => 'male', 'date_of_birth' => '2004-02-17', 'region' => 'Greater Accra',
        ]);

        $fellowship = $created[0];
        $bootcamp = $created[2];
        $closed = $created[4];

        $this->application($fellowship, $member, ApplicationStatus::UnderReview, [
            'team_name' => 'MathQuest GH',
            'problem' => 'Basic school pupils in rural areas have few engaging maths resources that work offline. MathQuest is a set of offline games aligned to the GES curriculum.',
            'stage' => 'Early prototype', 'team_size' => '3', 'skills' => ['Software', 'Teaching'],
        ]);
        $this->application($bootcamp, $member, ApplicationStatus::Draft, ['motivation' => 'I want to learn rapid prototyping…']);
        $this->application($closed, $member, ApplicationStatus::Approved, [
            'title' => 'SandFilter+', 'description' => 'A low-cost layered sand filter with a simple turbidity indicator.',
        ], 'Congratulations! Your team won second place. We will contact you about the prize ceremony.');

        // A few more applicants so the review queue has content.
        foreach ([['Kwesi Amoah', 'kwesi@example.com'], ['Efua Mensah', 'efua@example.com'], ['Kojo Asante', 'kojo@example.com']] as $i => [$name, $email]) {
            $user = $this->member($email, $name, [
                'phone' => '+233 20 555 01'.$i.'0', 'affiliation' => $i === 2 ? 'alumni' : 'student', 'institution' => 'University of Education, Winneba',
                'interests' => ['Educational Tech', 'Agriculture'], 'marketing_opt_in' => $i !== 1, 'onboarded_at' => now()->subDays(10 - $i),
                'gender' => ['male', 'female', 'male'][$i], 'date_of_birth' => ['2001-09-03', '1999-01-21', '1995-06-30'][$i],
                'region' => ['Central', 'Volta', 'Ashanti'][$i], 'education_level' => ['undergraduate', 'undergraduate', 'masters'][$i], 'disability' => 'no',
            ]);
            $this->application($fellowship, $user, [ApplicationStatus::Submitted, ApplicationStatus::Shortlisted, ApplicationStatus::Submitted][$i], [
                'team_name' => ['Read Along', 'FarmSense', 'EduSign'][$i],
                'problem' => 'A practical solution for Ghanaian classrooms, tested with teachers in Winneba.',
                'stage' => 'Idea only', 'team_size' => (string) ($i + 2),
            ]);
            $user->forceFill(['public_profile' => $i !== 1, 'xp' => max((int) $user->xp, 10 + $i * 40)])->save();
        }

        $this->engagement($member, $closed);
    }

    /** Badges, XP, a conversation and a progress report for the demo member. */
    private function engagement(User $member, Program $closed): void
    {
        $application = ProgramApplication::where('program_id', $closed->id)->where('user_id', $member->id)->first();
        $staff = User::where('email', 'officer@uew.edu.gh')->first() ?? User::where('email', 'admin@uew.edu.gh')->first();

        foreach ([[AchievementType::Participant, null], [AchievementType::Finalist, 'Second place, Pitch Day']] as [$type, $note]) {
            Achievement::firstOrNew(['user_id' => $member->id, 'program_id' => $closed->id, 'type' => $type])->forceFill([
                'title' => EngagementService::defaultTitle($type, $closed),
                'description' => $note, 'xp' => $type->xp(), 'awarded_by' => $staff?->id, 'awarded_at' => now()->subDays(1),
            ])->save();
        }
        $member->forceFill(['public_profile' => true, 'xp' => 20 + Achievement::where('user_id', $member->id)->sum('xp')])->save();

        if (! $application || ApplicationMessage::where('program_application_id', $application->id)->exists()) {
            return;
        }

        foreach ([[$staff, true, 'Congratulations again! Please send your first progress report with a simple budget breakdown by the end of the month.'],
            [$member, false, 'Thank you! We have bought the filter materials and will report once the school pilot starts.']] as [$author, $fromStaff, $body]) {
            (new ApplicationMessage)->forceFill([
                'program_application_id' => $application->id, 'user_id' => $author?->id, 'from_staff' => $fromStaff, 'body' => $body,
            ])->save();
        }

        (new ProgressReport)->forceFill([
            'program_application_id' => $application->id, 'user_id' => $member->id,
            'period' => 'Month 1', 'period_end' => now()->subDays(3)->toDateString(),
            'summary' => 'Built three SandFilter+ units and trained two teachers at Winneba Basic School to maintain them.',
            'milestones' => "Prototype finalised\nPilot school agreed\nTurbidity indicator tested",
            'challenges' => 'Sand supplier delays added a week.', 'next_steps' => 'Start daily water-quality logging with pupils.',
            'beneficiaries' => 240, 'budget_received' => 5000, 'budget_spent' => 3150,
            'line_items' => [
                ['category' => 'Equipment & materials', 'description' => 'Filter housings and sand', 'budgeted' => 2500, 'spent' => 2300],
                ['category' => 'Travel & transport', 'description' => 'Transport to pilot school', 'budgeted' => 600, 'spent' => 450],
                ['category' => 'Training & workshops', 'description' => 'Teacher training session', 'budgeted' => 500, 'spent' => 400],
            ],
            'status' => ReportStatus::Submitted,
        ])->save();
    }

    private function member(string $email, string $name, array $profile): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill($profile + [
            'name' => $name, 'role' => Role::Member, 'is_active' => true,
            'password' => config('hub.seed_password'), 'email_verified_at' => $user->email_verified_at ?? now()->subMonth(),
        ])->save();

        return $user;
    }

    private function application(Program $program, User $user, ApplicationStatus $status, array $answers, ?string $message = null): void
    {
        $application = ProgramApplication::firstOrNew(['program_id' => $program->id, 'user_id' => $user->id]);
        $application->forceFill([
            'status' => $status,
            'answers' => $answers,
            'decision_message' => $message,
            'submitted_at' => $status === ApplicationStatus::Draft ? null : ($application->submitted_at ?? now()->subDays(4)),
            'decided_at' => $status->isFinal() ? now()->subDays(1) : null,
            'applicant_snapshot' => $status === ApplicationStatus::Draft ? null : Demographics::snapshot($user),
        ])->save();
    }
}
