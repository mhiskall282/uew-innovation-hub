<?php

declare(strict_types=1);

namespace Tests\Feature\Workspace;

use App\Enums\ApplicationStatus;
use App\Enums\BroadcastStatus;
use App\Enums\Role;
use App\Jobs\SendBroadcast;
use App\Models\ActivityLog;
use App\Models\Broadcast;
use App\Models\Program;
use App\Models\ProgramApplication;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/** Regression tests for gaps found during the documentation audit. */
class OperationalFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_failed_broadcast_is_marked_failed_and_audited_instead_of_stuck_sending(): void
    {
        $broadcast = new Broadcast(['subject' => 'Bootcamp opens', 'body' => 'Hello', 'audience' => []]);
        $broadcast->forceFill(['status' => BroadcastStatus::Sending])->save();

        (new SendBroadcast($broadcast->id))->failed(new RuntimeException('SMTP down'));

        $this->assertSame(BroadcastStatus::Failed, $broadcast->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'broadcast.failed']);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('workspace.broadcasts.show', $broadcast))->assertOk()->assertSee('Failed');
        $this->actingAs($admin)->get(route('workspace.broadcasts.index'))->assertOk();
    }

    public function test_csv_exports_of_members_projects_and_applications_are_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $program = Program::factory()->published()->create();
        Project::factory()->create();

        $this->actingAs($admin)->get(route('workspace.members.export'))->assertOk()->streamedContent();
        $this->actingAs($admin)->get(route('workspace.projects.export'))->assertOk()->streamedContent();
        $this->actingAs($admin)->get(route('workspace.programs.export', $program))->assertOk()->streamedContent();

        foreach (['member.exported', 'report.projects_exported', 'application.exported'] as $action) {
            $this->assertSame(1, ActivityLog::where('action', $action)->where('user_id', $admin->id)->count(), $action);
        }
    }

    public function test_audit_log_can_be_filtered_by_every_recorded_area(): void
    {
        $admin = User::factory()->admin()->create();
        ActivityLog::create(['user_id' => $admin->id, 'action' => 'album.published', 'description' => 'Published the Pitch Day album']);
        ActivityLog::create(['user_id' => $admin->id, 'action' => 'project.updated', 'description' => 'Changed a project title']);

        $this->actingAs($admin)->get(route('workspace.audit.index', ['area' => 'album']))
            ->assertOk()
            ->assertSee('Published the Pitch Day album')
            ->assertDontSee('Changed a project title');
    }

    public function test_announcement_emails_are_not_pre_ticked_during_onboarding(): void
    {
        $member = User::factory()->member()->unonboarded()->create(['marketing_opt_in' => false]);

        $html = $this->actingAs($member)->get(route('onboarding.show'))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/name="marketing_opt_in"[^>]*\schecked/', $html);
    }

    public function test_programme_lists_do_not_count_applications_once_per_programme(): void
    {
        $programs = Program::factory()->published()->count(6)->create(['capacity' => 1]);
        ProgramApplication::factory()->between($programs[0], User::factory()->member()->create())
            ->status(ApplicationStatus::Submitted)->create();

        DB::enableQueryLog();
        $response = $this->getJson('/api/v1/programmes?per_page=50')->assertOk()->assertJsonCount(6, 'data');
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $full = collect($response->json('data'))->firstWhere('slug', $programs[0]->slug);
        $this->assertFalse($full['is_accepting_applications']);
        $this->assertLessThanOrEqual(4, $queries, 'v1 programme list must not run one query per programme');
        $this->assertFalse($programs[0]->fresh()->isAcceptingApplications(), 'isFull() still works without a preloaded count');
    }

    public function test_viewers_cannot_export_member_data(): void
    {
        $viewer = User::factory()->role(Role::Viewer)->create();

        $this->actingAs($viewer)->get(route('workspace.members.export'))->assertForbidden();
        $this->assertSame(0, ActivityLog::where('action', 'member.exported')->count());
    }
}
