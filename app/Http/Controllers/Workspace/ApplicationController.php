<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Enums\AchievementType;
use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Program;
use App\Models\ProgramApplication;
use App\Services\ApplicationService;
use App\Services\ConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Reviewing programme applications. Routes are gated by can:applications.review. */
class ApplicationController extends Controller
{
    public function __construct(private readonly ApplicationService $applications) {}

    public function index(Request $request): View
    {
        $status = ApplicationStatus::tryFrom((string) $request->query('status'));

        return view('workspace.applications.index', [
            'applications' => ProgramApplication::query()
                ->where('status', '!=', ApplicationStatus::Draft)
                ->when($status, fn ($q) => $q->where('status', $status), fn ($q) => $q->whereIn('status', ApplicationStatus::open()))
                ->with(['program:id,title,slug,type', 'applicant:id,name,email', 'reviewer:id,name'])
                ->latest('submitted_at')
                ->paginate(25)
                ->withQueryString(),
            'statuses' => ApplicationStatus::reviewable(),
            'activeStatus' => $status,
        ]);
    }

    public function show(ProgramApplication $application): View
    {
        Gate::authorize('view', $application);

        $application->load(['program', 'applicant', 'reviewer:id,name', 'messages.author:id,name', 'progressReports']);

        if ($application->user_id !== request()->user()->id) {
            app(ConversationService::class)->markRead($application, request()->user());
        }

        return view('workspace.applications.show', [
            'application' => $application,
            'statuses' => ApplicationStatus::reviewable(),
            'achievementTypes' => AchievementType::options(),
            'history' => ActivityLog::query()
                ->with('user:id,name')
                ->where('subject_type', $application->getMorphClass())
                ->where('subject_id', $application->id)
                ->latest('id')
                ->get(),
            'otherApplications' => ProgramApplication::query()
                ->where('user_id', $application->user_id)
                ->whereKeyNot($application->id)
                ->where('status', '!=', ApplicationStatus::Draft)
                ->with('program:id,title')
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }

    public function update(Request $request, ProgramApplication $application): RedirectResponse
    {
        Gate::authorize('review', $application);

        $data = $request->validate([
            'status' => ['required', Rule::enum(ApplicationStatus::class)->only(ApplicationStatus::reviewable())],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
            'decision_message' => ['nullable', 'string', 'max:2000'],
            'score' => ['nullable', 'integer', 'between:0,100'],
            'notify' => ['nullable', 'boolean'],
        ]);
        $data['notify'] = $request->boolean('notify');

        $this->applications->review($application, $data, $request->user());

        return redirect()->route('workspace.applications.show', $application)
            ->with('status', "Application {$application->reference} is now \"{$application->status->label()}\".");
    }

    public function bulk(Request $request, Program $program): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer'],
            'status' => ['required', Rule::enum(ApplicationStatus::class)->only(ApplicationStatus::reviewable())],
        ], ['ids.required' => 'Select at least one application.']);

        $count = $this->applications->bulkReview(
            $program,
            array_map('intval', $data['ids']),
            ApplicationStatus::from($data['status']),
            $request->boolean('notify'),
            $request->user(),
        );

        return back()->with('status', "{$count} application(s) updated.");
    }

    public function file(ProgramApplication $application, string $key): StreamedResponse
    {
        Gate::authorize('view', $application);

        return $this->applications->download($application, $key);
    }
}
