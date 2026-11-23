<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ProfileRequest;
use App\Models\Program;
use App\Models\ProgramApplication;
use App\Models\User;
use App\Services\MetricsService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Programme, application and membership reporting. Gated by can:reports.view. */
class ReportController extends Controller
{
    public function __invoke(MetricsService $metrics): View
    {
        $statusCounts = ProgramApplication::query()
            ->selectRaw('program_id, status, COUNT(*) as aggregate')
            ->where('status', '!=', ApplicationStatus::Draft)
            ->groupBy('program_id', 'status')
            ->get()
            ->groupBy('program_id');

        $programs = Program::query()->latest('opens_at')->latest('id')->take(30)->get(['id', 'title', 'slug', 'type', 'status', 'closes_at', 'capacity'])
            ->map(function (Program $p) use ($statusCounts) {
                $rows = $statusCounts->get($p->id, collect())->pluck('aggregate', 'status')->map(fn ($n) => (int) $n);
                $total = $rows->except(ApplicationStatus::Withdrawn->value)->sum();

                return (object) [
                    'program' => $p,
                    'total' => $total,
                    'pending' => collect(ApplicationStatus::open())->sum(fn ($s) => $rows[$s->value] ?? 0),
                    'approved' => $rows[ApplicationStatus::Approved->value] ?? 0,
                    'rejected' => $rows[ApplicationStatus::Rejected->value] ?? 0,
                    'acceptance' => $total > 0 ? (int) round(($rows[ApplicationStatus::Approved->value] ?? 0) / $total * 100) : null,
                ];
            });

        $members = User::members();
        $month = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $growth = User::members()
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("{$month} as month, COUNT(*) as aggregate")
            ->groupBy(DB::raw($month))
            ->pluck('aggregate', 'month');

        $months = collect(range(11, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'))
            ->mapWithKeys(fn ($m) => [$m => (int) ($growth[$m] ?? 0)]);

        // Interest distribution is computed in PHP from a lean column read; fine at
        // tens of thousands of members and portable across databases.
        $interests = collect(ProfileRequest::interestOptions())->mapWithKeys(fn ($i) => [$i => 0])->all();
        User::members()->whereNotNull('interests')->select(['id', 'interests'])->lazyById(1000)
            ->each(function (User $u) use (&$interests) {
                foreach ($u->interests ?? [] as $i) {
                    if (isset($interests[$i])) {
                        $interests[$i]++;
                    }
                }
            });
        arsort($interests);

        $affiliations = User::members()->whereNotNull('affiliation')
            ->selectRaw('affiliation, COUNT(*) as aggregate')->groupBy('affiliation')->pluck('aggregate', 'affiliation');

        return view('workspace.reports.index', [
            'programs' => $programs,
            'months' => $months,
            'interests' => $interests,
            'affiliations' => $affiliations,
            'totals' => [
                'members' => (clone $members)->count(),
                'verified' => User::members()->whereNotNull('email_verified_at')->count(),
                'applications' => ProgramApplication::where('status', '!=', ApplicationStatus::Draft)->count(),
                'approved' => ProgramApplication::where('status', ApplicationStatus::Approved)->count(),
                'open_programs' => Program::open()->count(),
            ],
            'portfolio' => $metrics->workspaceStats(),
        ]);
    }
}
