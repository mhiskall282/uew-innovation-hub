<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\Permission;
use App\Enums\ReportStatus;
use App\Models\Program;
use App\Models\ProgramApplication;
use App\Models\ProgressReport;
use App\Models\User;
use App\Notifications\HubNotification;
use App\Support\DateRange;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Progress and budget reports from accepted applicants, and the staff review
 * of them. Totals for spending are always recomputed on the server from the
 * line items, never trusted from the browser.
 */
final class ProgressReportService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly NotificationCenter $notifications,
        private readonly EngagementService $engagement,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated by ProgressReportRequest
     */
    public function submit(ProgramApplication $application, User $user, array $data, ?ProgressReport $report = null): ProgressReport
    {
        if (! $application->canReportProgress() || $application->user_id !== $user->id) {
            throw ValidationException::withMessages(['report' => 'Only accepted applicants can report on this programme.']);
        }

        if ($report && ! $report->isEditable()) {
            throw ValidationException::withMessages(['report' => 'This report is being reviewed and can no longer be changed.']);
        }

        $isNew = $report === null;
        $report ??= new ProgressReport;
        $items = self::cleanLineItems($data['line_items'] ?? []);

        $report->forceFill([
            'program_application_id' => $application->id,
            'user_id' => $user->id,
            'period' => $data['period'],
            'period_end' => $data['period_end'],
            'summary' => $data['summary'],
            'milestones' => $data['milestones'],
            'challenges' => $data['challenges'] ?? null,
            'next_steps' => $data['next_steps'] ?? null,
            'beneficiaries' => $data['beneficiaries'] ?? null,
            'budget_received' => $data['budget_received'] ?? 0,
            // With line items the total is their sum; without, the figure the member entered.
            'budget_spent' => $items !== [] ? round(array_sum(array_column($items, 'spent')), 2) : round((float) ($data['budget_spent'] ?? 0), 2),
            'line_items' => $items,
            'status' => ReportStatus::Submitted,
        ])->save();

        $application->loadMissing('program:id,title,slug');
        $this->audit->record($isNew ? 'report.submitted' : 'report.resubmitted', "{$user->name} ".($isNew ? 'submitted' : 'updated')." progress report {$report->reference} for \"{$application->program->title}\"", $report, actor: $user);

        if ($isNew) {
            $this->engagement->addXp($user, EngagementService::XP_REPORT);
        }

        $this->notifications->toStaff(Permission::ApplicationsReview, new HubNotification(
            title: $isNew ? 'New progress report' : 'Progress report updated',
            body: "{$user->name} · {$application->program->title} · {$report->period}",
            url: route('workspace.progress-reports.show', $report),
            icon: 'clipboard',
            kind: 'report',
        ), except: $user);

        return $report;
    }

    /**
     * @param  array{status: string, feedback?: ?string}  $data
     */
    public function review(ProgressReport $report, array $data, User $reviewer): ProgressReport
    {
        $report->status = ReportStatus::from($data['status']);
        $report->feedback = $data['feedback'] ?? null;
        $report->reviewed_by = $reviewer->id;
        $report->reviewed_at = now();
        $changes = $this->audit->diff($report);
        $report->save();

        $this->audit->record('report.reviewed', "Marked progress report {$report->reference} as \"{$report->status->label()}\"", $report, $changes, $reviewer);

        $report->loadMissing('author', 'application.program:id,title,slug');
        $this->notifications->toUser($report->author, new HubNotification(
            title: "Progress report: {$report->status->label()}",
            body: ($report->application?->program?->title ?? 'Your programme').' · '.$report->period.($report->feedback ? ' · Feedback from the Hub is waiting.' : ''),
            url: route('portal.reports.show', $report),
            icon: 'clipboard',
            kind: 'report',
        ));

        return $report;
    }

    /**
     * Funding versus reported spending for every programme with accepted applicants.
     *
     * @return Collection<int, object>
     */
    public function budgetByProgramme(?DateRange $range = null): Collection
    {
        // Reports whose reporting period ended in the range (all reports without one).
        $inRange = fn ($q) => $range
            ? $q->whereBetween('progress_reports.period_end', [$range->from->toDateString(), $range->to->toDateString()])
            : $q;

        // Latest report per application carries the cumulative figures.
        $latest = $inRange(ProgressReport::query())
            ->selectRaw('MAX(id) as id')
            ->groupBy('program_application_id')
            ->pluck('id');

        $reports = ProgressReport::query()
            ->whereIn('id', $latest)
            ->with('application:id,program_id')
            ->get(['id', 'program_application_id', 'budget_received', 'budget_spent', 'beneficiaries']);

        $reportCounts = $inRange(ProgressReport::query())
            ->join('program_applications', 'program_applications.id', '=', 'progress_reports.program_application_id')
            ->selectRaw('program_applications.program_id as program_id, COUNT(*) as aggregate')
            ->groupBy('program_applications.program_id')
            ->toBase()
            ->pluck('aggregate', 'program_id');

        return Program::query()
            ->whereHas('applications', fn ($q) => $q->where('status', ApplicationStatus::Approved))
            ->withCount(['applications as accepted_count' => fn ($q) => $q->where('status', ApplicationStatus::Approved)])
            ->orderByDesc('id')
            ->get(['id', 'title', 'slug', 'type', 'funding_amount'])
            ->map(function (Program $program) use ($reports, $reportCounts) {
                $mine = $reports->filter(fn ($r) => $r->application?->program_id === $program->id);
                $received = (float) $mine->sum('budget_received');
                $spent = (float) $mine->sum('budget_spent');

                return (object) [
                    'program' => $program,
                    'accepted' => (int) $program->accepted_count,
                    'reporting' => $mine->count(),
                    'reports' => (int) ($reportCounts[$program->id] ?? 0),
                    'funding' => (float) $program->funding_amount,
                    'received' => $received,
                    'spent' => $spent,
                    'reached' => (int) $mine->sum('beneficiaries'),
                    'burn' => $received > 0 ? (int) round($spent / $received * 100) : null,
                ];
            });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return list<array{category: string, description: string, budgeted: float, spent: float}>
     */
    public static function cleanLineItems(array $items): array
    {
        $clean = [];

        foreach (array_slice(array_values($items), 0, 30) as $item) {
            $description = trim((string) ($item['description'] ?? ''));
            $category = (string) ($item['category'] ?? 'Other');
            $budgeted = round(max(0, (float) ($item['budgeted'] ?? 0)), 2);
            $spent = round(max(0, (float) ($item['spent'] ?? 0)), 2);

            if ($description === '' && $budgeted == 0 && $spent == 0) {
                continue;
            }

            $clean[] = [
                'category' => in_array($category, ProgressReport::CATEGORIES, true) ? $category : 'Other',
                'description' => mb_substr($description, 0, 200),
                'budgeted' => $budgeted,
                'spent' => $spent,
            ];
        }

        return $clean;
    }
}
