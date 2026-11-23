<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'category', 'status']);

        return view('site.projects.index', [
            'projects' => Project::public()
                ->with('organizations:id,name')
                ->filter($filters)
                ->latest('start_date')
                ->paginate(9)
                ->withQueryString(),
            'filters' => $filters,
            'focusAreas' => config('hub.focus_areas'),
            'statuses' => [ProjectStatus::Active->value => 'Active', ProjectStatus::Completed->value => 'Completed'],
        ]);
    }

    public function show(Project $project): View
    {
        abort_unless($project->is_public, 404);

        $project->load(['organizations', 'publications' => fn ($q) => $q->latest('publish_date')]);

        return view('site.projects.show', [
            'project' => $project,
            'related' => Project::public()
                ->where('category', $project->category)
                ->whereKeyNot($project->id)
                ->latest('start_date')
                ->take(3)
                ->get(),
        ]);
    }
}
