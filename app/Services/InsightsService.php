<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Http\Requests\Portal\ProfileRequest;
use App\Models\Achievement;
use App\Models\Program;
use App\Models\ProgramApplication;
use App\Models\ProgressReport;
use App\Models\User;
use App\Support\DateRange;
use App\Support\Demographics;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every figure on the Reports page and in the PDF export, for one period.
 *
 * Period rules:
 *  - applications count when they were submitted in the period;
 *  - acceptances count when the decision was made in the period;
 *  - membership, interests and inclusion describe members who joined in it;
 *  - budgets use progress reports whose reporting period ended in it;
 *  - "most active" is XP earned in the period (all-time XP for "All time").
 *
 * Demographics are only ever returned as totals.
 */
final class InsightsService
{
    public const AGE_BANDS = ['Under 18', '18–24', '25–35', '36–50', 'Over 50'];

    public function __construct(
        private readonly MetricsService $metrics,
        private readonly ProgressReportService $reports,
    ) {}

    /** @return array<string, mixed> */
    public function build(DateRange $range): array
    {
        $joined = fn () => User::members()->whereBetween('created_at', [$range->from, $range->to]);
        $submitted = fn () => ProgramApplication::query()
            ->where('status', '!=', ApplicationStatus::Draft->value)
            ->whereBetween('submitted_at', [$range->from, $range->to]);

        $applications = $submitted()->count();
        $approved = ProgramApplication::query()
            ->where('status', ApplicationStatus::Approved->value)
            ->whereBetween('decided_at', [$range->from, $range->to])
            ->count();
        $decided = ProgramApplication::query()
            ->whereIn('status', [ApplicationStatus::Approved->value, ApplicationStatus::Rejected->value])
            ->whereBetween('decided_at', [$range->from, $range->to])
            ->count();

        $budgets = $this->reports->budgetByProgramme($range);

        return [
            'range' => $range,
            'generated_at' => now(),
            'totals' => [
                'members_total' => User::members()->where('created_at', '<=', $range->to)->count(),
                'members_new' => $joined()->count(),
                'verified_new' => $joined()->whereNotNull('email_verified_at')->count(),
                'applications' => $applications,
                'approved' => $approved,
                'decided' => $decided,
                'acceptance' => $decided > 0 ? (int) round($approved / $decided * 100) : null,
                'open_programs' => Program::open()->count(),
                'reports' => ProgressReport::query()->whereBetween('period_end', [$range->from->toDateString(), $range->to->toDateString()])->count(),
                'disbursed' => (float) $budgets->sum('received'),
                'spent' => (float) $budgets->sum('spent'),
                'reached' => (int) $budgets->sum('reached'),
            ],
            'programs' => $this->programmes($range),
            'months' => $this->growth($range),
            'interests' => $this->interests($joined()),
            'affiliations' => $joined()->whereNotNull('affiliation')
                ->selectRaw('affiliation, COUNT(*) as aggregate')->groupBy('affiliation')
                ->toBase()->pluck('aggregate', 'affiliation')->map(fn ($n) => (int) $n),
            'genders' => $joined()->selectRaw('gender, COUNT(*) as aggregate')->groupBy('gender')
                ->toBase()->pluck('aggregate', 'gender')->map(fn ($n) => (int) $n),
            'regions' => $joined()->whereNotNull('region')->selectRaw('region, COUNT(*) as aggregate')
                ->groupBy('region')->orderByDesc('aggregate')->toBase()->pluck('aggregate', 'region')->map(fn ($n) => (int) $n),
            'ageBands' => $this->ageBands($joined()),
            'leaders' => $this->leaders($range),
            'budgets' => $budgets,
            'portfolio' => $this->metrics->workspaceStats(),
        ];
    }

    /** Programmes with applications in the period, or that opened or closed in it. */
    private function programmes(DateRange $range): Collection
    {
        // toBase(): plain rows, so `status` stays a string usable as an array key.
        $counts = ProgramApplication::query()
            ->selectRaw('program_id, status, COUNT(*) as aggregate')
            ->where('status', '!=', ApplicationStatus::Draft->value)
            ->whereBetween('submitted_at', [$range->from, $range->to])
            ->groupBy('program_id', 'status')
            ->toBase()
            ->get()
            ->groupBy('program_id');

        return Program::query()
            ->where(fn ($q) => $q
                ->whereIn('id', $counts->keys()->all())
                ->orWhereBetween('opens_at', [$range->from, $range->to])
                ->orWhereBetween('closes_at', [$range->from, $range->to]))
            ->latest('opens_at')->latest('id')
            ->take(50)
            ->get(['id', 'title', 'slug', 'type', 'status', 'opens_at', 'closes_at', 'capacity', 'funding_amount'])
            ->map(function (Program $p) use ($counts) {
                $rows = collect($counts->get($p->id, []))->pluck('aggregate', 'status')->map(fn ($n) => (int) $n);
                $total = $rows->except(ApplicationStatus::Withdrawn->value)->sum();
                $approved = $rows[ApplicationStatus::Approved->value] ?? 0;
                $rejected = $rows[ApplicationStatus::Rejected->value] ?? 0;

                return (object) [
                    'program' => $p,
                    'total' => $total,
                    'pending' => collect(ApplicationStatus::open())->sum(fn ($s) => $rows[$s->value] ?? 0),
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'acceptance' => ($approved + $rejected) > 0 ? (int) round($approved / ($approved + $rejected) * 100) : null,
                ];
            });
    }

    /** @return Collection<string, int> Y-m => new members */
    private function growth(DateRange $range): Collection
    {
        $month = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $counts = User::members()
            ->whereBetween('created_at', [$range->from, $range->to])
            ->selectRaw("{$month} as month, COUNT(*) as aggregate")
            ->groupBy(DB::raw($month))
            ->toBase()
            ->pluck('aggregate', 'month');

        $first = User::members()->min('created_at');
        $earliest = $first ? CarbonImmutable::parse($first) : null;

        return collect($range->months($earliest))->mapWithKeys(fn ($m) => [$m => (int) ($counts[$m] ?? 0)]);
    }

    /** @return array<string, int> */
    private function interests($members): array
    {
        // Counted in PHP from a lean column read: portable across databases.
        $interests = collect(ProfileRequest::interestOptions())->mapWithKeys(fn ($i) => [$i => 0])->all();
        $members->whereNotNull('interests')->select(['id', 'interests'])->lazyById(1000)
            ->each(function (User $u) use (&$interests) {
                foreach ($u->interests ?? [] as $i) {
                    if (isset($interests[$i])) {
                        $interests[$i]++;
                    }
                }
            });
        arsort($interests);

        return $interests;
    }

    /** @return Collection<string, int> */
    private function ageBands($members): Collection
    {
        $bands = [];
        $members->whereNotNull('date_of_birth')->select(['id', 'date_of_birth'])->lazyById(1000)
            ->each(function (User $u) use (&$bands) {
                $band = Demographics::ageBand($u->age());
                $bands[$band] = ($bands[$band] ?? 0) + 1;
            });

        return collect(self::AGE_BANDS)->mapWithKeys(fn ($b) => [$b => $bands[$b] ?? 0]);
    }

    /**
     * Top 10 members by XP earned in the period, using the same XP rules as
     * EngagementService. All time uses the stored running total.
     *
     * @return Collection<int, object{user: User, xp: int, badges: int}>
     */
    private function leaders(DateRange $range): Collection
    {
        if ($range->isAllTime()) {
            return User::members()->active()->where('xp', '>', 0)->withCount('achievements')
                ->orderByDesc('xp')->take(10)->get(['id', 'name', 'xp', 'public_profile'])
                ->map(fn (User $u) => (object) ['user' => $u, 'xp' => (int) $u->xp, 'badges' => (int) $u->achievements_count]);
        }

        $xp = [];
        $badges = [];
        $add = function ($rows, int $per = 0) use (&$xp) {
            foreach ($rows as $userId => $value) {
                $xp[$userId] = ($xp[$userId] ?? 0) + ($per ? $per * (int) $value : (int) $value);
            }
        };

        $achievements = Achievement::query()->whereBetween('awarded_at', [$range->from, $range->to])
            ->selectRaw('user_id, SUM(xp) as xp, COUNT(*) as n')->groupBy('user_id')->toBase()->get();
        $add($achievements->pluck('xp', 'user_id'));
        $badges = $achievements->pluck('n', 'user_id')->map(fn ($n) => (int) $n)->all();

        $add(ProgramApplication::query()->where('status', '!=', ApplicationStatus::Draft->value)
            ->whereBetween('submitted_at', [$range->from, $range->to])
            ->selectRaw('user_id, COUNT(*) as n')->groupBy('user_id')->toBase()->pluck('n', 'user_id'), EngagementService::XP_APPLICATION);

        $add(ProgressReport::query()->whereBetween('created_at', [$range->from, $range->to])
            ->selectRaw('user_id, COUNT(*) as n')->groupBy('user_id')->toBase()->pluck('n', 'user_id'), EngagementService::XP_REPORT);

        arsort($xp);
        $top = array_slice($xp, 0, 10, true);
        $users = User::members()->active()->whereKey(array_keys($top))->get(['id', 'name', 'xp', 'public_profile'])->keyBy('id');

        return collect($top)
            ->filter(fn ($points, $id) => $users->has($id) && $points > 0)
            ->map(fn ($points, $id) => (object) ['user' => $users[$id], 'xp' => (int) $points, 'badges' => $badges[$id] ?? 0])
            ->values();
    }
}
