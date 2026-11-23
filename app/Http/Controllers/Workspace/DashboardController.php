<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Enums\ApplicationStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\Program;
use App\Models\ProgramApplication;
use App\Models\Project;
use App\Models\User;
use App\Services\MetricsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, MetricsService $metrics): View
    {
        $user = $request->user();
        $reviewer = $user->hasPermission(Permission::ApplicationsReview);

        return view('workspace.dashboard', [
            'stats' => $metrics->workspaceStats(),
            'openApplicationCount' => $reviewer ? $metrics->openApplicationCount() : 0,
            'memberCount' => $user->hasPermission(Permission::MembersManage) ? User::members()->count() : null,
            'openPrograms' => $user->hasPermission(Permission::ProgramsManage)
                ? Program::open()->withCount(['applications as submitted_count' => fn ($q) => $q->whereNotIn('status', [ApplicationStatus::Draft, ApplicationStatus::Withdrawn])])
                    ->orderByRaw('CASE WHEN closes_at IS NULL THEN 1 ELSE 0 END')->orderBy('closes_at')->take(5)->get()
                : collect(),
            'recentProjects' => Project::query()
                ->with('organizations:id,name')
                ->latest('updated_at')
                ->take(6)
                ->get(),
            'waiting' => $reviewer
                ? ProgramApplication::query()
                    ->whereIn('status', ApplicationStatus::open())
                    ->with(['program:id,title', 'applicant:id,name'])
                    ->oldest('submitted_at')
                    ->take(5)
                    ->get()
                : collect(),
            'myDrafts' => $user->hasPermission(Permission::ContentDraft)
                ? Post::query()->whereNull('published_at')->where('author_id', $user->id)->latest()->take(5)->get()
                : collect(),
            'recentActivity' => $user->hasPermission(Permission::AuditView)
                ? ActivityLog::with('user:id,name')->latest('id')->take(8)->get()
                : collect(),
        ]);
    }
}
