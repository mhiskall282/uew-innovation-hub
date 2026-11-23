<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Services\ProjectService;
use App\Services\ReportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $projectService,
        protected ReportExportService $exportService
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'category', 'year', 'organization']);
        $projects = $this->projectService->getFilteredPaginatedProjects($filters, 10);
        $organizations = Organization::orderBy('name')->get(['id', 'name']);
        $categories = ['STEM', 'Educational Tech', 'Agriculture', 'Inclusive Education', 'Digital Humanities', 'Community Outreach'];
        $yearExpr = Project::yearExpression();
        $years = Cache::remember('hub.project_years', 300, fn () => Project::whereNotNull('start_date')
            ->selectRaw("{$yearExpr} as year")
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year'));
        return view('projects.index', compact('projects', 'organizations', 'categories', 'years', 'filters'));
    }

    public function create(): View
    {
        $organizations = Organization::orderBy('name')->get(['id', 'name']);
        $categories = ['STEM', 'Educational Tech', 'Agriculture', 'Inclusive Education', 'Digital Humanities', 'Community Outreach'];

        return view('projects.create', compact('organizations', 'categories'));
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = $this->projectService->createProject($request->validated(), $request->user());

        return redirect()->route('projects.show', $project)
            ->with('status', 'Project successfully registered with UEW Innovation Hub.');
    }

    public function show(Project $project): View
    {
        $project->load(['organizations', 'creator', 'publications']);
        return view('projects.show', compact('project'));
    }

    public function edit(Project $project): View
    {
        $organizations = Organization::orderBy('name')->get(['id', 'name']);
        $categories = ['STEM', 'Educational Tech', 'Agriculture', 'Inclusive Education', 'Digital Humanities', 'Community Outreach'];
        $project->load('organizations');

        return view('projects.edit', compact('project', 'organizations', 'categories'));
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->projectService->updateProject($project, $request->validated(), $request->user());

        return redirect()->route('projects.show', $project)
            ->with('status', 'Project successfully updated.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        if (!$request->user() || !$request->user()->isSuperAdmin()) {
            abort(403, 'Only administrators can delete project initiatives.');
        }

        $this->projectService->deleteProject($project, $request->user());

        return redirect()->route('projects.index')
            ->with('status', 'Project record has been archived.');
    }

    public function export(Request $request): StreamedResponse
    {
        return $this->exportService->exportCsv($request->all());
    }
}
