<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Publication;
use App\Services\DocumentStorage;
use App\Services\PublicationService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Public document downloads. Documents are resolved from the database
 * record, never from a filename in the URL.
 */
class DocumentController extends Controller
{
    public function __construct(private readonly DocumentStorage $documents) {}

    public function projectReport(Project $project): StreamedResponse
    {
        abort_unless($project->is_public && $project->report_file_path, 404);

        return $this->documents->download($project->report_file_path, DocumentStorage::REPORTS, $project->slug.'-report');
    }

    public function publication(Publication $publication, PublicationService $publications): StreamedResponse
    {
        abort_unless($publication->hasDocument(), 404);

        $response = $this->documents->download($publication->pdf_file_path, DocumentStorage::PUBLICATIONS, $publication->slug);
        $publications->recordDownload($publication);

        return $response;
    }
}
