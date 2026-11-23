<?php

namespace Tests\Feature\Site;

use App\Enums\Role;
use App\Models\GrantApplication;
use App\Models\Project;
use App\Models\Publication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_public_project_report_streams_as_pdf_with_nosniff(): void
    {
        Storage::disk('private')->put('reports/project_1.pdf', '%PDF-1.4');
        $project = Project::factory()->create(['report_file_path' => 'project_1.pdf']); // legacy filename-only path

        $response = $this->get(route('site.documents.project-report', $project));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_internal_project_reports_are_not_public(): void
    {
        Storage::disk('private')->put('reports/secret.pdf', '%PDF-1.4');
        $project = Project::factory()->internal()->create(['report_file_path' => 'reports/secret.pdf']);

        $this->get(route('site.documents.project-report', $project))->assertNotFound();

        // ...but staff can open them from the workspace.
        $this->signInAs(Role::Viewer);
        $this->get(route('workspace.projects.report', $project))->assertOk();
    }

    public function test_publication_download_counts_are_incremented(): void
    {
        Storage::disk('private')->put('publications/newsletter.pdf', '%PDF-1.4');
        $publication = Publication::factory()->create(['pdf_file_path' => 'newsletter.pdf', 'downloads_count' => 4]);

        $this->get(route('site.documents.publication', $publication))->assertOk();

        $this->assertSame(5, $publication->fresh()->downloads_count);
    }

    public function test_missing_files_return_404(): void
    {
        $project = Project::factory()->create(['report_file_path' => 'reports/gone.pdf']);

        $this->get(route('site.documents.project-report', $project))->assertNotFound();
    }

    public function test_stored_paths_cannot_escape_their_folder(): void
    {
        Storage::disk('private')->put('proposals/applicant.pdf', '%PDF-1.4');
        $project = Project::factory()->create(['report_file_path' => '../proposals/applicant.pdf']);

        $this->get(route('site.documents.project-report', $project))->assertNotFound();
    }

    public function test_grant_proposals_are_restricted_to_reviewers(): void
    {
        Storage::disk('private')->put('proposals/p.pdf', '%PDF-1.4');
        $application = GrantApplication::factory()->create();
        $application->forceFill(['proposal_file_path' => 'proposals/p.pdf'])->save();

        $url = route('workspace.grants.proposal', $application);

        $this->get($url)->assertRedirect(route('login'));

        $this->signInAs(Role::Intern);
        $this->get($url)->assertForbidden();

        $this->signInAs(Role::Reviewer);
        $this->get($url)->assertOk();
    }

    public function test_legacy_proposals_stored_with_reports_are_still_found(): void
    {
        Storage::disk('private')->put('reports/grant_123_old.pdf', '%PDF-1.4');
        $application = GrantApplication::factory()->create();
        $application->forceFill(['proposal_file_path' => 'grant_123_old.pdf'])->save();

        $this->signInAs(Role::Officer);
        $this->get(route('workspace.grants.proposal', $application))->assertOk();
    }
}
