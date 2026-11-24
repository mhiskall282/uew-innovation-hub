<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ProgressReportRequest;
use App\Models\ProgramApplication;
use App\Models\ProgressReport;
use App\Services\ProgressReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Progress and budget reports for members whose application was accepted
 * (incubatees, grantees, fellows). Members only ever see their own.
 */
class ProgressReportController extends Controller
{
    public function __construct(private readonly ProgressReportService $reports) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('portal.reports.index', [
            'accepted' => ProgramApplication::query()
                ->where('user_id', $user->id)
                ->where('status', ApplicationStatus::Approved)
                ->with('program:id,title,slug,type,funding_amount')
                ->withCount('progressReports')
                ->latest('decided_at')
                ->get(),
            'reports' => ProgressReport::query()
                ->where('user_id', $user->id)
                ->with('application:id,reference,program_id', 'application.program:id,title,slug')
                ->latest('period_end')
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function create(Request $request, ProgramApplication $application): View
    {
        Gate::authorize('report', $application);

        $previous = $application->progressReports()->first();

        return view('portal.reports.form', [
            'application' => $application->load('program:id,title,slug,type,funding_amount'),
            'report' => new ProgressReport([]),
            'previous' => $previous,
        ]);
    }

    public function store(ProgressReportRequest $request, ProgramApplication $application): RedirectResponse
    {
        Gate::authorize('report', $application);

        $report = $this->reports->submit($application, $request->user(), $request->validated());

        return redirect()->route('portal.reports.show', $report)->with('status', 'Report submitted. The Hub team has been notified.');
    }

    public function show(ProgressReport $report): View
    {
        Gate::authorize('view', $report);
        abort_unless($report->user_id === request()->user()->id, 404);

        return view('portal.reports.show', ['report' => $report->load('application.program:id,title,slug', 'reviewer:id,name')]);
    }

    public function edit(ProgressReport $report): View
    {
        Gate::authorize('update', $report);

        return view('portal.reports.form', [
            'application' => $report->load('application.program:id,title,slug,type,funding_amount')->application,
            'report' => $report,
            'previous' => null,
        ]);
    }

    public function update(ProgressReportRequest $request, ProgressReport $report): RedirectResponse
    {
        Gate::authorize('update', $report);

        $report->loadMissing('application');
        $this->reports->submit($report->application, $request->user(), $request->validated(), $report);

        return redirect()->route('portal.reports.show', $report)->with('status', 'Report updated and sent back for review.');
    }
}
