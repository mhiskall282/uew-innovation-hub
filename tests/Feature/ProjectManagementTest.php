<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $officer;
    protected User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.test@uew.edu.gh',
            'role' => 'super_admin',
            'password' => Hash::make('password'),
        ]);

        $this->officer = User::create([
            'name' => 'Officer User',
            'email' => 'officer.test@uew.edu.gh',
            'role' => 'officer',
            'password' => Hash::make('password'),
        ]);

        $this->viewer = User::create([
            'name' => 'Viewer User',
            'email' => 'viewer.test@uew.edu.gh',
            'role' => 'viewer',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_projects_index_is_accessible(): void
    {
        $response = $this->get('/projects');
        $response->assertStatus(200);
        $response->assertSee('Projects & Research Initiatives', false);
    }

    public function test_authorized_officer_can_create_project(): void
    {
        $org = Organization::create([
            'name' => 'UNESCO Ghana',
            'type' => 'Funding Partner',
        ]);

        $response = $this->actingAs($this->officer)->post('/projects', [
            'title' => 'Mobile STEM Innovation Lab',
            'summary' => 'Deploying mobile STEM vans across rural Central Region basic schools.',
            'category' => 'STEM',
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'beneficiaries_target' => 2000,
            'beneficiaries_reached' => 450,
            'grant_amount' => 120000.00,
            'lead_researcher' => 'Dr. K. Mensah',
            'organizations' => [
                $org->id => 'Funding Partner',
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'title' => 'Mobile STEM Innovation Lab',
            'category' => 'STEM',
        ]);
    }

    public function test_super_admin_can_soft_delete_project(): void
    {
        $project = Project::create([
            'title' => 'To Be Deleted Project',
            'summary' => 'A project to test deletion.',
            'category' => 'STEM',
            'status' => 'suspended',
        ]);

        $response = $this->actingAs($this->admin)->delete("/projects/{$project->slug}");

        $response->assertRedirect(route('projects.index'));
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_projects_csv_export_returns_streamed_file(): void
    {
        Project::create([
            'title' => 'Exportable Initiative',
            'category' => 'EdTech',
            'status' => 'active',
            'beneficiaries_target' => 500,
            'beneficiaries_reached' => 250,
        ]);

        $response = $this->get('/projects/export/csv');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
