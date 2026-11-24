<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\InsightsService;
use App\Support\DateRange;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Programme, membership, inclusion and incubation reporting for a chosen
 * period, on screen and as a branded PDF. Gated by can:reports.view.
 */
class ReportController extends Controller
{
    public const SECTIONS = [
        'programmes' => 'Programmes and applications',
        'membership' => 'Membership and interests',
        'inclusion' => 'Inclusion (anonymous)',
        'incubation' => 'Incubation budgets',
        'engagement' => 'Most active members',
    ];

    public function __construct(private readonly InsightsService $insights) {}

    public function index(Request $request): View
    {
        $range = DateRange::fromRequest($request);

        return view('workspace.reports.index', $this->insights->build($range) + [
            'presets' => DateRange::PRESETS,
            'sections' => self::SECTIONS,
        ]);
    }

    public function pdf(Request $request, AuditLogger $audit): Response
    {
        $range = DateRange::fromRequest($request);
        $chosen = array_values(array_intersect(array_keys(self::SECTIONS), (array) $request->query('sections', [])));
        $chosen = $chosen ?: array_keys(self::SECTIONS);

        $data = $this->insights->build($range) + [
            'sections' => $chosen,
            'sectionLabels' => self::SECTIONS,
            'preparedBy' => $request->user(),
        ];

        $audit->record('report.exported', 'Exported the Hub report as PDF ('.$range->describe().')', null, ['range' => $range->query(), 'sections' => $chosen]);

        return Pdf::loadView('workspace.reports.pdf', $data)
            ->setPaper('a4')
            ->setOption(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isFontSubsettingEnabled' => true, 'defaultFont' => 'DejaVu Sans'])
            ->download('uew-innovation-hub-report-'.$range->slug().'.pdf');
    }
}
