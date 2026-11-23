<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Publication;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ProjectService $projectService
    ) {}

    public function index(Request $request): View
    {
        $metrics = $this->projectService->getDashboardMetrics();
        $recentProjects = Project::with('organizations')
            ->latest('start_date')
            ->take(6)
            ->get();
        $publications = Publication::latest('publish_date')->take(4)->get();
        $organizations = Organization::withCount('projects')->take(8)->get();

        return view('dashboard.index', compact('metrics', 'recentProjects', 'publications', 'organizations'));
    }
}
