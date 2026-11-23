<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Publication;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        $metrics = [
            'total_projects' => Project::count(),
            'total_beneficiaries' => (int) Project::sum('beneficiaries_reached'),
            'total_funding' => (float) Project::sum('grant_amount'),
            'total_organizations' => Organization::count(),
            'total_publications' => Publication::count(),
        ];

        $featuredProjects = Project::with('organizations')
            ->where('status', 'active')
            ->latest('start_date')
            ->take(6)
            ->get();

        $publications = Publication::latest('publish_date')->take(4)->get();
        $organizations = Organization::orderBy('name')->take(8)->get();

        return view('landing', compact('metrics', 'featuredProjects', 'publications', 'organizations'));
    }
}
