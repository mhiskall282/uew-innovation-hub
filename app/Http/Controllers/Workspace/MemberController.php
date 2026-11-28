<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Enums\AchievementType;
use App\Enums\Affiliation;
use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ProfileRequest;
use App\Models\Program;
use App\Models\User;
use App\Services\ApplicationService;
use App\Services\AuditLogger;
use App\Services\UserService;
use App\Support\Demographics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Member (applicant) accounts. Routes are gated by can:members.manage. */
class MemberController extends Controller
{
    public function index(Request $request): View
    {
        return view('workspace.members.index', [
            'members' => $this->query($request)
                ->withCount(['applications as submitted_count' => fn ($q) => $q->where('status', '!=', ApplicationStatus::Draft)])
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'affiliations' => Affiliation::options(),
            'interests' => ProfileRequest::interestOptions(),
            'filters' => $request->only(['search', 'affiliation', 'interest', 'state']),
            'totals' => [
                'all' => User::members()->count(),
                'verified' => User::members()->whereNotNull('email_verified_at')->count(),
                'subscribed' => User::members()->where('marketing_opt_in', true)->count(),
                'month' => User::members()->where('created_at', '>=', now()->startOfMonth())->count(),
            ],
        ]);
    }

    public function show(User $member): View
    {
        abort_unless($member->isMember(), 404);

        return view('workspace.members.show', [
            'member' => $member,
            'applications' => $member->applications()->with('program:id,title,slug')->latest()->get(),
            'achievements' => $member->achievements()->with('program:id,title,slug', 'awarder:id,name')->get(),
            'achievementTypes' => AchievementType::options(),
            'programs' => Program::query()->whereIn('id', $member->applications()->select('program_id'))->orderBy('title')->pluck('title', 'id')->all(),
        ]);
    }

    public function toggle(User $member, UserService $users): RedirectResponse
    {
        abort_unless($member->isMember(), 404);

        $users->setActive($member, ! $member->is_active);

        return back()->with('status', $member->is_active ? "{$member->name} can sign in again." : "{$member->name} has been suspended and signed out.");
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->query($request)->orderBy('id');
        app(AuditLogger::class)->record('member.exported', 'Exported the member list as CSV (contains personal data)', null, ['filters' => array_filter($request->only(['search', 'affiliation', 'interest', 'state']))]);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Name', 'Email', 'Phone', 'Gender', 'Age', 'Region', 'Education', 'Affiliation', 'Institution', 'Department', 'Interests', 'Announcements', 'Verified', 'Joined']);

            $query->lazy(500)->each(fn (User $u) => fputcsv($out, array_map([ApplicationService::class, 'cell'], [
                $u->name, $u->email, $u->phone,
                Demographics::GENDERS[$u->gender ?? ''] ?? '', $u->age(), $u->region,
                Demographics::EDUCATION_LEVELS[$u->education_level ?? ''] ?? '',
                $u->affiliation?->label(), $u->institution, $u->department,
                implode('; ', $u->interests ?? []), $u->marketing_opt_in ? 'Yes' : 'No',
                $u->email_verified_at ? 'Yes' : 'No', $u->created_at?->format('Y-m-d'),
            ])));

            fclose($out);
        }, 'uew-innovation-hub-members-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    private function query(Request $request)
    {
        $affiliation = Affiliation::tryFrom((string) $request->query('affiliation'));
        $interest = in_array($request->query('interest'), ProfileRequest::interestOptions(), true) ? $request->query('interest') : null;

        return User::query()
            ->members()
            ->search($request->query('search'), ['name', 'email', 'institution', 'department', 'phone'])
            ->when($affiliation, fn ($q) => $q->where('affiliation', $affiliation))
            ->when($interest, fn ($q) => $q->whereJsonContains('interests', $interest))
            ->when($request->query('state') === 'unverified', fn ($q) => $q->whereNull('email_verified_at'))
            ->when($request->query('state') === 'subscribed', fn ($q) => $q->where('marketing_opt_in', true))
            ->when($request->query('state') === 'suspended', fn ($q) => $q->where('is_active', false));
    }
}
