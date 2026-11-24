<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\GrantStatus;
use App\Models\GrantApplication;
use App\Models\Organization;
use App\Models\ProgramApplication;
use App\Models\Project;
use App\Models\Publication;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Aggregated numbers for the public site, the API and the staff dashboard.
 *
 * Every figure is computed with a handful of aggregate queries and cached
 * for hub.metrics_ttl seconds. Writes to the underlying models call flush()
 * through the FlushesMetrics trait, so cached numbers are never stale after
 * an edit.
 */
final class MetricsService
{
    private const KEYS = ['hub.metrics.public', 'hub.metrics.workspace', 'hub.metrics.years', 'hub.metrics.open_applications'];

    /**
     * Public impact figures. Only counts projects marked public.
     *
     * @return array{projects: int, active_projects: int, beneficiaries: int, funding: float, partners: int, publications: int}
     */
    public function publicStats(): array
    {
        return Cache::remember('hub.metrics.public', $this->ttl(), function () {
            $totals = Project::public()
                ->selectRaw('COUNT(*) as projects')
                ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_projects")
                ->selectRaw('COALESCE(SUM(beneficiaries_reached), 0) as beneficiaries')
                ->selectRaw('COALESCE(SUM(grant_amount), 0) as funding')
                ->toBase()
                ->first();

            return [
                'projects' => (int) $totals->projects,
                'active_projects' => (int) $totals->active_projects,
                'beneficiaries' => (int) $totals->beneficiaries,
                'funding' => (float) $totals->funding,
                'partners' => Organization::count(),
                'publications' => Publication::count(),
            ];
        });
    }

    /**
     * Internal dashboard figures, including private projects and grants.
     *
     * @return array<string, mixed>
     */
    public function workspaceStats(): array
    {
        return Cache::remember('hub.metrics.workspace', $this->ttl(), function () {
            $projects = Project::query()
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active")
                ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
                ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
                ->selectRaw('COALESCE(SUM(beneficiaries_reached), 0) as reached')
                ->selectRaw('COALESCE(SUM(beneficiaries_target), 0) as target')
                ->selectRaw('COALESCE(SUM(grant_amount), 0) as funding')
                ->toBase()
                ->first();

            $grants = GrantApplication::query()
                ->select('status', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('status')
                ->pluck('aggregate', 'status')
                ->map(fn ($count) => (int) $count)
                ->all();

            $byCategory = Project::query()
                ->select('category', DB::raw('COUNT(*) as aggregate'), DB::raw('COALESCE(SUM(beneficiaries_reached), 0) as reached'))
                ->groupBy('category')
                ->orderByDesc('aggregate')
                ->get()
                ->map(fn ($row) => ['category' => $row->category, 'projects' => (int) $row->aggregate, 'reached' => (int) $row->reached])
                ->all();

            $year = Project::yearExpression();
            $byYear = Project::query()
                ->whereNotNull('start_date')
                ->select(DB::raw("{$year} as year"), DB::raw('COUNT(*) as aggregate'))
                ->groupBy(DB::raw($year))
                ->orderBy(DB::raw($year))
                ->get()
                ->map(fn ($row) => ['year' => (int) $row->year, 'projects' => (int) $row->aggregate])
                ->all();

            $target = (int) $projects->target;

            return [
                'projects' => [
                    'total' => (int) $projects->total,
                    'active' => (int) $projects->active,
                    'completed' => (int) $projects->completed,
                    'pending' => (int) $projects->pending,
                ],
                'beneficiaries' => [
                    'reached' => (int) $projects->reached,
                    'target' => $target,
                    'progress' => $target > 0 ? (int) min(100, round($projects->reached / $target * 100)) : 0,
                ],
                'funding' => (float) $projects->funding,
                'partners' => Organization::count(),
                'publications' => Publication::count(),
                'grants' => [
                    'total' => array_sum($grants),
                    'open' => ($grants[GrantStatus::Submitted->value] ?? 0) + ($grants[GrantStatus::UnderReview->value] ?? 0) + ($grants[GrantStatus::Shortlisted->value] ?? 0),
                    'by_status' => collect(GrantStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $grants[$s->value] ?? 0])->all(),
                ],
                'by_category' => $byCategory,
                'by_year' => $byYear,
            ];
        });
    }

    /** @return list<int> distinct project start years, newest first */
    public function projectYears(): array
    {
        return Cache::remember('hub.metrics.years', $this->ttl(), function () {
            $year = Project::yearExpression();

            return Project::query()
                ->whereNotNull('start_date')
                ->selectRaw("{$year} as year")
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year')
                ->map(fn ($y) => (int) $y)
                ->all();
        });
    }

    /** Count of legacy grant applications waiting for a decision, for the sidebar badge. */
    public function openGrantCount(): int
    {
        return $this->workspaceStats()['grants']['open'];
    }

    /** Programme applications waiting for a decision (cached briefly). */
    public function openApplicationCount(): int
    {
        return (int) Cache::remember('hub.metrics.open_applications', 60, fn () => ProgramApplication::query()
            ->whereIn('status', ApplicationStatus::open())
            ->count());
    }

    public function flush(): void
    {
        foreach (self::KEYS as $key) {
            Cache::forget($key);
        }
    }

    private function ttl(): int
    {
        return (int) config('hub.metrics_ttl', 300);
    }
}
