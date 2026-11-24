<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\User;
use Illuminate\View\View;

/**
 * Public community page: most active members and their badges. Only members
 * who switched on "Show me on the community page" appear, and only their
 * name, affiliation, institution, bio, XP and badges are shown.
 */
class CommunityController extends Controller
{
    public function index(): View
    {
        $leaders = User::query()
            ->onLeaderboard()
            ->withCount('achievements')
            ->orderByDesc('xp')
            ->orderBy('id')
            ->take(30)
            ->get(['id', 'name', 'affiliation', 'institution', 'xp']);

        return view('site.community.index', [
            'leaders' => $leaders,
            'recent' => Achievement::query()
                ->whereHas('user', fn ($q) => $q->onLeaderboard())
                ->with('user:id,name', 'program:id,title,slug')
                ->latest('awarded_at')
                ->take(8)
                ->get(),
        ]);
    }

    public function show(User $user): View
    {
        abort_unless($user->isMember() && $user->is_active && $user->public_profile, 404);

        $rank = User::query()->onLeaderboard()->where('xp', '>', $user->xp)->count() + 1;

        return view('site.community.show', [
            'member' => $user,
            'rank' => $user->xp > 0 ? $rank : null,
            'achievements' => $user->achievements()->with('program:id,title,slug')->get(),
        ]);
    }
}
