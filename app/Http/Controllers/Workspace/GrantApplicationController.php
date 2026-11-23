<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Enums\GrantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\ReviewGrantApplicationRequest;
use App\Models\ActivityLog;
use App\Models\GrantApplication;
use App\Services\DocumentStorage;
use App\Services\GrantApplicationService;
use App\Services\MetricsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GrantApplicationController extends Controller
{
    public function index(Request $request, MetricsService $metrics): View
    {
        Gate::authorize('viewAny', GrantApplication::class);

        $status = GrantStatus::tryFrom((string) $request->query('status'));
        $category = in_array($request->query('category'), config('hub.focus_areas'), true) ? $request->query('category') : null;

        return view('workspace.grants.index', [
            'applications' => GrantApplication::query()
                ->with('reviewer:id,name')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($category, fn ($q) => $q->where('category', $category))
                ->search($request->query('search'), ['reference_no', 'applicant_name', 'email', 'project_title', 'institution_or_org'])
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'counts' => $metrics->workspaceStats()['grants'],
            'statuses' => GrantStatus::cases(),
            'activeStatus' => $status,
            'focusAreas' => config('hub.focus_areas'),
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    public function show(GrantApplication $application): View
    {
        Gate::authorize('view', $application);

        $application->load('reviewer:id,name');

        return view('workspace.grants.show', [
            'application' => $application,
            'statuses' => GrantStatus::cases(),
            'history' => ActivityLog::query()
                ->with('user:id,name')
                ->where('subject_type', $application->getMorphClass())
                ->where('subject_id', $application->id)
                ->latest()
                ->get(),
        ]);
    }

    public function update(ReviewGrantApplicationRequest $request, GrantApplication $application, GrantApplicationService $grants): RedirectResponse
    {
        Gate::authorize('review', $application);

        $grants->review(
            $application,
            GrantStatus::from($request->validated('status')),
            $request->validated('reviewer_notes'),
            $request->user(),
            $request->boolean('notify_applicant'),
        );

        return redirect()
            ->route('workspace.grants.show', $application)
            ->with('status', "Application {$application->reference_no} is now \"{$application->status->label()}\".");
    }

    public function proposal(GrantApplication $application, DocumentStorage $documents): StreamedResponse
    {
        Gate::authorize('view', $application);

        return $documents->download($application->proposal_file_path, DocumentStorage::PROPOSALS, $application->reference_no.'-proposal');
    }
}
