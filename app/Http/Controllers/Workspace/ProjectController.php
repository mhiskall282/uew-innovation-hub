<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\ProjectRequest;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Publication;
use App\Services\DocumentStorage;
use App\Services\MetricsService;
use App\Services\ProjectService;
use App\Services\ReportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    private const FILTERS = ['search', 'status', 'category', 'year', 'organization'];

    public function __construct(private readonly ProjectService $projects) {}

    public function index(Request $request, MetricsService $metrics): View
    {
        Gate::authorize('viewAny', Project::class);

        $filters = $request->only(self::FILTERS);

        return view('workspace.projects.index', [
            'projects' => Project::query()
                ->with('organizations:id,name')
                ->filter($filters)
                ->latest('start_date')
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
            'statuses' => ProjectStatus::options(),
            'focusAreas' => config('hub.focus_areas'),
            'years' => $metrics->projectYears(),
            'organizations' => Organization::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Project::class);

        return view('workspace.projects.create', $this->formData(new Project));
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        Gate::authorize('create', Project::class);

        $project = $this->projects->create($request->validated(), $request->user());

        return redirect()->route('workspace.projects.show', $project)->with('status', 'Project created.');
    }

    public function show(Project $project): View
    {
        Gate::authorize('view', $project);

        $project->load(['organizations', 'creator:id,name', 'publications']);

        return view('workspace.projects.show', [
            'project' => $project,
            'history' => ActivityLog::query()
                ->with('user:id,name')
                ->where('subject_type', $project->getMorphClass())
                ->where('subject_id', $project->id)
                ->latest()
                ->take(15)
                ->get(),
        ]);
    }

    public function edit(Project $project): View
    {
        Gate::authorize('update', $project);

        $project->load('organizations');

        return view('workspace.projects.edit', $this->formData($project));
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $this->projects->update($project, $request->validated());

        return redirect()->route('workspace.projects.show', $project)->with('status', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $this->projects->delete($project);

        return redirect()->route('workspace.projects.index')->with('status', "\"{$project->title}\" was archived.");
    }

    public function export(Request $request, ReportExportService $export): StreamedResponse
    {
        Gate::authorize('export', Project::class);

        return $export->projectsCsv($request->only(self::FILTERS));
    }

    public function report(Project $project, DocumentStorage $documents): StreamedResponse
    {
        Gate::authorize('view', $project);

        return $documents->download($project->report_file_path, DocumentStorage::REPORTS, $project->slug.'-report');
    }

    /** @return array<string, mixed> */
    private function formData(Project $project): array
    {
        return [
            'project' => $project,
            'statuses' => ProjectStatus::options(),
            'focusAreas' => config('hub.focus_areas'),
            'partnerRoles' => config('hub.partner_roles'),
            'organizations' => Organization::orderBy('name')->get(['id', 'name', 'type']),
            'linked' => $project->exists
                ? $project->organizations->mapWithKeys(fn ($o) => [$o->id => $o->pivot->role])->all()
                : [],
            'publicationCount' => $project->exists ? Publication::where('project_id', $project->id)->count() : 0,
        ];
    }
}
