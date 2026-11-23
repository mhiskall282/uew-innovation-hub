<?php

namespace App\Services;

use App\Models\Project;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function exportCsv(array $filters): StreamedResponse
    {
        $query = Project::with('organizations')
            ->filter($filters)
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="uew_innovation_hub_projects_' . date('Y-m-d') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            // Output UTF-8 BOM for Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Project ID',
                'Project Title',
                'Category',
                'Status',
                'Start Date',
                'End Date',
                'Beneficiaries Target',
                'Beneficiaries Reached',
                'Grant Amount (GHS)',
                'Lead Researcher',
                'Partner Organizations & Roles',
                'Report Uploaded',
            ]);

            $totalBeneficiaries = 0;
            $totalGrant = 0;

            $count = 0;
            $query->chunk(500, function ($projects) use ($handle, &$totalBeneficiaries, &$totalGrant, &$count) {
            foreach ($projects as $p) {
                $count++;
                $orgsList = $p->organizations->map(fn($o) => "{$o->name} ({$o->pivot->role})")->join('; ');
                $totalBeneficiaries += $p->beneficiaries_reached;
                $totalGrant += (float) $p->grant_amount;

                fputcsv($handle, [
                    $p->id,
                    $this->safe($p->title),
                    $p->category,
                    strtoupper($p->status),
                    $p->start_date ? $p->start_date->format('d M Y') : 'N/A',
                    $p->end_date ? $p->end_date->format('d M Y') : 'N/A',
                    $p->beneficiaries_target,
                    $p->beneficiaries_reached,
                    number_format((float) $p->grant_amount, 2),
                    $this->safe($p->lead_researcher ?? 'N/A'),
                    $this->safe($orgsList ?: 'None'),
                    $p->report_file_path ? 'Yes' : 'No',
                ]);
            }
            });

            // Summary row
            fputcsv($handle, [
                '',
                'TOTAL (' . $count . ' Projects)',
                '',
                '',
                '',
                '',
                '',
                $totalBeneficiaries,
                number_format($totalGrant, 2),
                '',
                '',
                '',
            ]);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Neutralise spreadsheet formula injection (=, +, -, @) in user-supplied cells.
     */
    private function safe(?string $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'" . $value
            : $value;
    }
}