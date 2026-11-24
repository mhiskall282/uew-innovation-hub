<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgressReport;
use App\Services\ProgressReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Incubation board: progress and budget reports from accepted applicants. Gated by can:applications.review. */
class ProgressReportController extends Controller
{
    public function __construct(private readonly ProgressReportService $reports) {}

    public function index(Request $request): View
    {
        $status = ReportStatus::tryFrom((string) $request->query('status'));
        $programId = $request->integer('programme') ?: null;

        return view('workspace.progress-reports.index', [
            'reports' => ProgressReport::query()
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($programId, fn ($q) => $q->whereHas('application', fn ($a) => $a->where('program_id', $programId)))
                ->with('author:id,name,email', 'application:id,reference,program_id', 'application.program:id,title,slug')
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'budgets' => $this->reports->budgetByProgramme(),
            'statuses' => ReportStatus::cases(),
            'activeStatus' => $status,
            'programmes' => Program::query()->whereHas('applications.progressReports')->orderBy('title')->pluck('title', 'id'),
            'activeProgramme' => $programId,
            'awaiting' => ProgressReport::where('status', ReportStatus::Submitted)->count(),
        ]);
    }

    public function show(ProgressReport $report): View
    {
        Gate::authorize('view', $report);

        $report->load('author', 'reviewer:id,name', 'application.program:id,title,slug,funding_amount');

        return view('workspace.progress-reports.show', [
            'report' => $report,
            'earlier' => ProgressReport::query()
                ->where('program_application_id', $report->program_application_id)
                ->whereKeyNot($report->id)
                ->latest('period_end')
                ->get(['id', 'reference', 'period', 'period_end', 'status', 'budget_received', 'budget_spent']),
        ]);
    }

    public function update(Request $request, ProgressReport $report): RedirectResponse
    {
        Gate::authorize('review', $report);

        $data = $request->validate([
            'status' => ['required', Rule::enum(ReportStatus::class)->only([ReportStatus::Accepted, ReportStatus::ChangesRequested])],
            'feedback' => [Rule::requiredIf($request->input('status') === ReportStatus::ChangesRequested->value), 'nullable', 'string', 'max:5000'],
        ], ['feedback.required' => 'Tell the member what to change.']);

        $this->reports->review($report, $data, $request->user());

        return redirect()->route('workspace.progress-reports.show', $report)->with('status', "Report {$report->reference} marked \"{$report->status->label()}\".");
    }
}
