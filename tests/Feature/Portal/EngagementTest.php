<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Enums\AchievementType;
use App\Enums\ApplicationStatus;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\Achievement;
use App\Models\Program;
use App\Models\ProgramApplication;
use App\Models\ProgressReport;
use App\Models\User;
use App\Notifications\HubNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Notifications, badges and XP, application messages, progress reports, prefill and the community page. */
class EngagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function accepted(?User $member = null): ProgramApplication
    {
        return ProgramApplication::factory()
            ->between(Program::factory()->published()->create(), $member ?? User::factory()->member()->create())
            ->status(ApplicationStatus::Approved)
            ->create();
    }

    private function reportPayload(array $overrides = []): array
    {
        return $overrides + [
            'period' => 'Month 1', 'period_end' => now()->toDateString(),
            'summary' => 'We built and tested the first prototype with two schools.',
            'milestones' => 'Prototype done', 'budget_received' => '1000',
            'line_items' => [
                ['category' => 'Equipment & materials', 'description' => 'Kits', 'budgeted' => '600', 'spent' => '550.50'],
                ['category' => 'Other', 'description' => '', 'budgeted' => '', 'spent' => ''], // blank row is dropped
            ],
        ];
    }

    public function test_accepting_an_application_notifies_awards_a_badge_and_xp(): void
    {
        $reviewer = $this->staff(Role::Reviewer);
        $application = ProgramApplication::factory()->create();
        $member = $application->applicant;

        $this->actingAs($reviewer)->put(route('workspace.applications.update', $application), ['status' => 'approved'])->assertRedirect();

        $member->refresh();
        $this->assertSame(2, $member->unreadNotifications()->count()); // status change + badge
        $this->assertSame(AchievementType::Participant, $member->achievements()->sole()->type);
        $this->assertSame(AchievementType::Participant->xp(), $member->xp);
    }

    public function test_submitting_an_application_notifies_reviewers_and_gives_xp(): void
    {
        $reviewer = $this->staff(Role::Reviewer);
        $member = User::factory()->member()->create();
        $program = Program::factory()->published()->create();

        $this->actingAs($member)->post(route('portal.apply.store', $program), [
            'intent' => 'submit', 'declaration' => '1',
            'answers' => ['title' => 'Solar kits', 'description' => 'Details of the idea.'],
        ])->assertRedirect();

        $this->assertSame(1, $reviewer->notifications()->count());
        $this->assertSame(10, $member->fresh()->xp);
        $this->assertSame($member->phone, ProgramApplication::sole()->applicant_snapshot['phone']);
    }

    public function test_notification_links_only_follow_local_paths_and_belong_to_their_owner(): void
    {
        $member = User::factory()->member()->create();
        $other = User::factory()->member()->create();
        $member->notify(new HubNotification('Hi', 'Body', 'https://evil.example/phish'));
        $notification = $member->notifications()->sole();

        // Absolute URLs are stored as paths, so the redirect can never leave the site.
        $this->assertSame('/phish', $notification->data['url']);

        $this->actingAs($other)->get(route('notifications.open', $notification->id))->assertNotFound();
        $this->actingAs($member)->get(route('notifications.open', $notification->id))->assertRedirect('/phish');
        $this->assertNotNull($notification->fresh()->read_at);

        // A crafted row with a protocol-relative or backslash path falls back to the inbox.
        foreach (['//evil.example', '/\\evil.example'] as $bad) {
            $notification->forceFill(['data' => ['title' => 'x', 'body' => 'y', 'url' => $bad]])->save();
            $this->actingAs($member)->get(route('notifications.open', $notification->id))->assertRedirect(route('notifications.index'));
        }
    }

    public function test_applicant_and_staff_can_message_but_strangers_cannot(): void
    {
        $application = ProgramApplication::factory()->create();
        $member = $application->applicant;
        $reviewer = $this->staff(Role::Reviewer);
        $stranger = User::factory()->member()->create();

        $this->actingAs($member)->post(route('portal.applications.messages.store', $application), ['body' => 'Can I add a teammate?'])->assertRedirect();
        $this->assertSame(1, $reviewer->notifications()->count());

        $this->actingAs($reviewer)->post(route('workspace.applications.messages.store', $application), ['body' => 'Yes, e-mail us their name.'])->assertRedirect();
        $this->assertSame(1, $member->notifications()->count());

        $this->actingAs($stranger)->post(route('portal.applications.messages.store', $application), ['body' => 'Sneaky'])->assertForbidden();
        $this->actingAs($this->staff(Role::Intern))->post(route('workspace.applications.messages.store', $application), ['body' => 'Hi'])->assertForbidden();

        $this->assertSame(2, $application->messages()->count());
        $this->actingAs($member)->get(route('portal.applications.show', $application))->assertOk()->assertSee('Yes, e-mail us their name.');
    }

    public function test_accepted_member_submits_a_report_with_server_side_totals(): void
    {
        $application = $this->accepted();
        $member = $application->applicant;

        $this->actingAs($member)->post(route('portal.reports.store', $application), $this->reportPayload(['budget_spent' => '999999']))->assertRedirect();

        $report = ProgressReport::sole();
        $this->assertSame('550.50', $report->budget_spent); // sum of lines, not the posted figure
        $this->assertCount(1, $report->line_items);
        $this->assertSame(20, $member->fresh()->xp);
    }

    public function test_reports_are_limited_to_accepted_applications_and_their_owner(): void
    {
        $pending = ProgramApplication::factory()->create();
        $this->actingAs($pending->applicant)->post(route('portal.reports.store', $pending), $this->reportPayload())->assertForbidden();

        $application = $this->accepted();
        $this->actingAs(User::factory()->member()->create())->post(route('portal.reports.store', $application), $this->reportPayload())->assertForbidden();

        $this->actingAs($application->applicant)->post(route('portal.reports.store', $application), $this->reportPayload());
        $report = ProgressReport::sole();

        $this->actingAs(User::factory()->member()->create())->get(route('portal.reports.show', $report))->assertForbidden();
        // Submitted reports are locked until the Hub asks for changes.
        $this->actingAs($application->applicant)->get(route('portal.reports.edit', $report))->assertForbidden();
    }

    public function test_staff_review_requests_changes_and_member_can_resubmit(): void
    {
        $application = $this->accepted();
        $this->actingAs($application->applicant)->post(route('portal.reports.store', $application), $this->reportPayload());
        $report = ProgressReport::sole();
        $reviewer = $this->staff(Role::Reviewer);

        $this->actingAs($reviewer)->put(route('workspace.progress-reports.update', $report), ['status' => 'changes_requested'])->assertSessionHasErrors('feedback');
        $this->actingAs($reviewer)->put(route('workspace.progress-reports.update', $report), ['status' => 'changes_requested', 'feedback' => 'Add receipts.'])->assertRedirect();
        $this->assertSame(ReportStatus::ChangesRequested, $report->fresh()->status);

        $this->actingAs($application->applicant)->put(route('portal.reports.update', $report), $this->reportPayload(['period' => 'Month 1 (revised)']))->assertRedirect();
        $this->assertSame(ReportStatus::Submitted, $report->fresh()->status);
        $this->assertSame(20, $application->applicant->fresh()->xp); // no XP for resubmitting

        $this->actingAs($this->staff(Role::Viewer))->get(route('workspace.progress-reports.index'))->assertForbidden();
        $this->actingAs($reviewer)->get(route('workspace.progress-reports.index'))->assertOk()->assertSee('Month 1 (revised)');
    }

    public function test_staff_award_and_revoke_badges(): void
    {
        $officer = $this->staff(Role::Officer);
        $member = User::factory()->member()->create();

        $this->actingAs($officer)->post(route('workspace.achievements.store', $member), ['type' => 'winner', 'xp' => 999])->assertRedirect();
        $this->actingAs($officer)->post(route('workspace.achievements.store', $member), ['type' => 'winner'])->assertRedirect(); // duplicate ignored

        $this->assertSame(300, $member->fresh()->xp); // fixed XP for standard badges
        $this->assertSame(1, Achievement::count());

        $this->actingAs($this->staff(Role::Reviewer))->post(route('workspace.achievements.store', $member), ['type' => 'mentor'])->assertForbidden();

        $this->actingAs($officer)->delete(route('workspace.achievements.destroy', Achievement::sole()))->assertRedirect();
        $this->assertSame(0, $member->fresh()->xp);
    }

    public function test_community_page_shows_only_opted_in_members_without_private_data(): void
    {
        $public = User::factory()->member()->create(['name' => 'Akosua Public', 'public_profile' => true, 'xp' => 120, 'phone' => '+233 24 000 1111']);
        $private = User::factory()->member()->create(['name' => 'Kofi Private', 'public_profile' => false, 'xp' => 500]);

        $this->get(route('site.community.index'))->assertOk()->assertSee('Akosua Public')->assertDontSee('Kofi Private')->assertDontSee('+233 24 000 1111');
        $this->get(route('site.community.show', $public))->assertOk()->assertDontSee($public->email);
        $this->get(route('site.community.show', $private))->assertNotFound();
        $this->get(route('site.community.show', $this->staff(Role::SuperAdmin)))->assertNotFound();

        $this->getJson('/api/v1/community')->assertOk()->assertJsonCount(1, 'data')->assertJsonMissing(['email' => $public->email]);
    }

    public function test_application_questions_prefill_from_the_profile(): void
    {
        $member = User::factory()->member()->create(['region' => 'Volta']);
        $program = Program::factory()->published()->withFields([
            ['label' => 'Which region are you in?', 'type' => 'short_text', 'prefill' => 'region'],
            ['label' => 'Bad source', 'type' => 'short_text', 'prefill' => 'password'],
        ])->create();

        $this->assertNull($program->fields()[1]['prefill']); // unknown sources are discarded
        $this->actingAs($member)->get(route('portal.apply', $program))->assertOk()->assertSee('value="Volta"', false)->assertSee('Filled from your profile');
    }

    public function test_reviewers_do_not_see_gender_or_disability(): void
    {
        $application = ProgramApplication::factory()->create();
        $application->applicant->forceFill(['gender' => 'female', 'disability' => 'yes'])->save();

        $this->actingAs($this->staff(Role::Reviewer))->get(route('workspace.applications.show', $application))
            ->assertOk()->assertDontSee('Gender')->assertDontSee('Disability');
    }

    public function test_public_programmes_api_hides_drafts_and_forms(): void
    {
        Program::factory()->published()->create(['title' => 'Open Grant']);
        Program::factory()->draft()->create(['title' => 'Secret Draft']);

        $this->getJson('/api/v1/programmes')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Open Grant')->assertJsonMissingPath('data.0.form_schema');
        $draft = Program::where('title', 'Secret Draft')->sole();
        $this->getJson('/api/v1/programmes/'.$draft->slug)->assertNotFound();
    }
}
