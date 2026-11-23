<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\UpdatePasswordRequest;
use App\Http\Requests\Workspace\UpdateProfileRequest;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** "My account": profile, password, notification preferences and sessions. */
class ProfileController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('workspace.profile.edit', [
            'user' => $user,
            'sessions' => $this->sessions($request),
            'canReceiveGrants' => $user->hasPermission(Permission::GrantsReview),
            'canReceiveMessages' => $user->hasPermission(Permission::MessagesManage),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $this->users->updateOwnProfile($request->user(), $request->validated());

        return back()->with('status', 'Your profile was updated.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $this->users->changeOwnPassword($request->user(), $request->validated('password'));

        // Keep this session valid after the password hash changes.
        $request->session()->regenerate();

        return back()->with('status', 'Your password was changed. Other devices have been signed out.');
    }

    public function destroyOtherSessions(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'string', 'current_password:web']], [
            'current_password.current_password' => 'Your current password is incorrect.',
        ]);

        $this->users->signOutOtherSessions($request->user());

        return back()->with('status', 'You have been signed out of all other devices.');
    }

    /**
     * Active sessions for the current user (database session driver only).
     *
     * @return Collection<int, object>
     */
    private function sessions(Request $request): Collection
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->limit(10)
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn ($s) => (object) [
                'ip' => $s->ip_address,
                'agent' => $this->describeAgent((string) $s->user_agent),
                'last_active' => Carbon::createFromTimestamp($s->last_activity),
                'current' => $s->id === $request->session()->getId(),
            ]);
    }

    private function describeAgent(string $agent): string
    {
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Browser',
        };
        $os = match (true) {
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Unknown device',
        };

        return "{$browser} on {$os}";
    }
}
