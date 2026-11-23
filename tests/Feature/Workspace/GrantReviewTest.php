<?php

namespace Tests\Feature\Workspace;

use App\Enums\GrantStatus;
use App\Enums\Role;
use App\Mail\GrantStatusUpdatedMail;
use App\Models\GrantApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GrantReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_reviewer_records_a_decision_and_the_applicant_is_emailed(): void
    {
        $reviewer = $this->signInAs(Role::Reviewer);
        $application = GrantApplication::factory()->create();

        $this->put("/workspace/grants/{$application->reference_no}", [
            'status' => 'shortlisted',
            'reviewer_notes' => 'Invite to present.',
            'notify_applicant' => '1',
        ])->assertRedirect(route('workspace.grants.show', $application));

        $application->refresh();
        $this->assertSame(GrantStatus::Shortlisted, $application->status);
        $this->assertSame($reviewer->id, $application->reviewed_by);
        $this->assertNotNull($application->reviewed_at);
        Mail::assertQueued(GrantStatusUpdatedMail::class, fn ($m) => $m->hasTo($application->email));
        $this->assertDatabaseHas('activity_logs', ['action' => 'grant.reviewed', 'subject_id' => $application->id]);
    }

    public function test_no_email_when_notification_is_unticked_or_status_unchanged(): void
    {
        $this->signInAs(Role::Officer);
        $application = GrantApplication::factory()->status(GrantStatus::UnderReview)->create();

        $this->put("/workspace/grants/{$application->reference_no}", ['status' => 'approved']);
        $this->put("/workspace/grants/{$application->reference_no}", ['status' => 'approved', 'notify_applicant' => '1', 'reviewer_notes' => 'same']);

        Mail::assertNothingQueued();
    }

    public function test_declining_requires_a_note(): void
    {
        $this->signInAs(Role::Reviewer);
        $application = GrantApplication::factory()->create();

        $this->put("/workspace/grants/{$application->reference_no}", ['status' => 'rejected'])
            ->assertSessionHasErrors('reviewer_notes');

        $this->assertSame(GrantStatus::Submitted, $application->fresh()->status);
    }

    public function test_queue_shows_counts_and_filters_by_status(): void
    {
        $this->signInAs(Role::Reviewer);
        GrantApplication::factory()->create(['project_title' => 'Waiting One']);
        GrantApplication::factory()->status(GrantStatus::Approved)->create(['project_title' => 'Funded One']);

        $this->get('/workspace/grants?status=submitted')->assertOk()->assertSee('Waiting One')->assertDontSee('Funded One');
        $this->get('/workspace/grants?search=Funded')->assertSee('Funded One')->assertDontSee('Waiting One');
    }

    public function test_non_reviewers_cannot_see_or_decide_applications(): void
    {
        $application = GrantApplication::factory()->create();

        foreach ([Role::Intern, Role::Viewer] as $role) {
            $this->signInAs($role);
            $this->get("/workspace/grants/{$application->reference_no}")->assertForbidden();
            $this->put("/workspace/grants/{$application->reference_no}", ['status' => 'approved'])->assertForbidden();
        }

        $this->assertSame(GrantStatus::Submitted, $application->fresh()->status);
    }
}
