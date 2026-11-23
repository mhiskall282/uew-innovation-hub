<?php

namespace Tests\Feature\Workspace;

use App\Enums\ProjectStatus;
use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Mobile Science Labs',
            'summary' => 'Science kits for rural schools.',
            'category' => 'STEM',
            'status' => 'active',
            'start_date' => '2026-01-10',
            'end_date' => '2026-12-31',
            'beneficiaries_target' => 500,
            'beneficiaries_reached' => 125,
            'grant_amount' => 50000,
            'lead_researcher' => 'Dr. Ama Mensah',
            'department' => 'Department of Science Education',
            'is_public' => '1',
        ], $overrides);
    }

    public function test_officer_creates_a_project_with_partners_and_a_report(): void
    {
        $officer = $this->signInAs(Role::Officer);
        [$funder, $agency, $unused] = Organization::factory()->count(3)->create();

        $response = $this->post('/workspace/projects', $this->payload([
            'report_file' => $this->pdf('Final Report.pdf'),
            'partners' => [
                ['organization_id' => $funder->id, 'role' => 'Funding Partner'],
                ['organization_id' => $agency->id, 'role' => 'Implementing Partner'],
                ['role' => 'Partner'], // unticked row
            ],
        ]));

        $project = Project::sole();
        $response->assertRedirect(route('workspace.projects.show', $project));

        $this->assertSame('mobile-science-labs', $project->slug);
        $this->assertSame($officer->id, $project->created_by);
        $this->assertSame(25, $project->progress);
        $this->assertEqualsCanonicalizing(
            [$funder->id => 'Funding Partner', $agency->id => 'Implementing Partner'],
            $project->organizations->mapWithKeys(fn ($o) => [$o->id => $o->pivot->role])->all(),
        );
        $this->assertStringStartsWith('reports/project_', $project->report_file_path);
        Storage::disk('private')->assertExists($project->report_file_path);
        $this->assertDatabaseHas('activity_logs', ['action' => 'project.created', 'subject_id' => $project->id, 'user_id' => $officer->id]);
    }

    public function test_update_records_a_field_level_diff_and_syncs_partners(): void
    {
        $this->signInAs(Role::Officer);
        $partner = Organization::factory()->create();
        $project = Project::factory()->create(['title' => 'Old Title', 'status' => ProjectStatus::Active]);
        $project->organizations()->attach($partner, ['role' => 'Partner']);

        $this->put("/workspace/projects/{$project->slug}", $this->payload(['title' => 'New Title', 'status' => 'completed', 'is_public' => '0', 'partners' => []]))
            ->assertRedirect();

        $project->refresh();
        $this->assertSame('New Title', $project->title);
        $this->assertSame(ProjectStatus::Completed, $project->status);
        $this->assertFalse($project->is_public);
        $this->assertCount(0, $project->organizations);

        $log = ActivityLog::where('action', 'project.updated')->sole();
        $this->assertSame(['from' => 'Old Title', 'to' => 'New Title'], $log->properties['title']);
        $this->assertSame(['from' => 'active', 'to' => 'completed'], $log->properties['status']);
        $this->assertArrayHasKey('partners', $log->properties);
    }

    public function test_replacing_a_report_deletes_the_old_file(): void
    {
        $this->signInAs(Role::Officer);
        Storage::disk('private')->put('reports/old.pdf', '%PDF');
        $project = Project::factory()->create(['report_file_path' => 'reports/old.pdf']);

        $this->put("/workspace/projects/{$project->slug}", $this->payload(['report_file' => $this->pdf()]));

        Storage::disk('private')->assertMissing('reports/old.pdf');
        Storage::disk('private')->assertExists($project->fresh()->report_file_path);
    }

    public function test_validation_rejects_bad_input(): void
    {
        $this->signInAs(Role::Officer);

        $this->post('/workspace/projects', $this->payload([
            'title' => '',
            'category' => 'Not a real area',
            'status' => 'approved',
            'end_date' => '2025-01-01',
            'partners' => [['organization_id' => 999, 'role' => 'Overlord']],
        ]))->assertSessionHasErrors(['title', 'category', 'status', 'end_date', 'partners.0.organization_id', 'partners.0.role']);

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_read_only_roles_cannot_write(): void
    {
        $project = Project::factory()->create();

        foreach ([Role::Viewer, Role::Intern, Role::Reviewer] as $role) {
            $this->signInAs($role);
            $this->post('/workspace/projects', $this->payload())->assertForbidden();
            $this->put("/workspace/projects/{$project->slug}", $this->payload())->assertForbidden();
            $this->delete("/workspace/projects/{$project->slug}")->assertForbidden();
        }

        $this->assertDatabaseCount('projects', 1);
    }

    public function test_only_the_hub_director_can_archive(): void
    {
        $project = Project::factory()->create();

        $this->signInAs(Role::Officer);
        $this->delete("/workspace/projects/{$project->slug}")->assertForbidden();

        $this->signInAs(Role::SuperAdmin);
        $this->delete("/workspace/projects/{$project->slug}")->assertRedirect(route('workspace.projects.index'));

        $this->assertSoftDeleted($project);
    }

    public function test_csv_export_streams_filtered_rows_and_neutralises_formulas(): void
    {
        $this->signInAs(Role::Reviewer);
        Project::factory()->create(['title' => '=HYPERLINK("http://evil")', 'category' => 'STEM']);
        Project::factory()->create(['title' => 'Farm Project', 'category' => 'Agriculture']);

        $response = $this->get('/workspace/projects/export?category=STEM');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('Farm Project', $csv);
        $this->assertStringContainsString('TOTAL (1 projects)', $csv);
    }

    public function test_index_filters_include_internal_projects_for_staff(): void
    {
        $this->signInAs(Role::Viewer);
        Project::factory()->internal()->create(['title' => 'Confidential Pilot']);

        $this->get('/workspace/projects')->assertSee('Confidential Pilot')->assertSee('Internal');
    }
}
