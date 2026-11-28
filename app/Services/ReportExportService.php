<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the project portfolio as CSV. Rows are read in chunks so memory use
 * stays flat regardless of portfolio size.
 */
final class ReportExportService
{
    private const HEADINGS = [
        'ID', 'Title', 'Focus area', 'Status', 'Visibility', 'Start date', 'End date',
        'Beneficiaries target', 'Beneficiaries reached', 'Progress %', 'Grant amount (GHS)',
        'Lead researcher', 'Department', 'Partners', 'Report on file',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function projectsCsv(array $filters): StreamedResponse
    {
        app(AuditLogger::class)->record('report.projects_exported', 'Exported the project portfolio as CSV', null, ['filters' => array_filter($filters)]);

        $query = Project::query()
            ->with('organizations:id,name')
            ->filter($filters)
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        $filename = 'uew-innovation-hub-projects-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel detects the encoding
            fputcsv($out, self::HEADINGS);

            $count = 0;
            $reached = 0;
            $funding = 0.0;

            // lazy() pages through results 500 at a time while keeping the sort order.
            foreach ($query->lazy(500) as $project) {
                $count++;
                $reached += $project->beneficiaries_reached;
                $funding += (float) $project->grant_amount;

                fputcsv($out, [
                    $project->id,
                    $this->cell($project->title),
                    $this->cell($project->category),
                    $project->status->label(),
                    $project->is_public ? 'Public' : 'Internal',
                    $project->start_date?->format('Y-m-d') ?? '',
                    $project->end_date?->format('Y-m-d') ?? '',
                    $project->beneficiaries_target,
                    $project->beneficiaries_reached,
                    $project->progress,
                    number_format((float) $project->grant_amount, 2, '.', ''),
                    $this->cell($project->lead_researcher),
                    $this->cell($project->department),
                    $this->cell($project->organizations->map(fn ($o) => "{$o->name} ({$o->pivot->role})")->join('; ')),
                    $project->report_file_path ? 'Yes' : 'No',
                ]);
            }

            fputcsv($out, ['', "TOTAL ({$count} projects)", '', '', '', '', '', '', $reached, '', number_format($funding, 2, '.', ''), '', '', '', '']);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Prevents spreadsheet formula injection from user-entered text.
     */
    private function cell(?string $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'{$value}" : $value;
    }
}
