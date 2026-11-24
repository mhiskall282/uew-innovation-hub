<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Leaderboard of members who chose to appear on the public community page.
 * Only display name, affiliation, XP, level and badge titles are returned;
 * never e-mail, phone or demographic data.
 */
class CommunityController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        $members = User::query()
            ->onLeaderboard()
            ->with(['achievements' => fn ($q) => $q->select(['id', 'user_id', 'type', 'title', 'awarded_at'])])
            ->orderByDesc('xp')
            ->orderBy('id')
            ->take(25)
            ->get(['id', 'name', 'affiliation', 'xp']);

        return $this->ok($members->values()->map(fn (User $u, int $i) => [
            'rank' => $i + 1,
            'name' => $u->name,
            'affiliation' => $u->affiliation?->label(),
            'xp' => $u->xp,
            'level' => $u->level()['name'],
            'badges' => $u->achievements->map(fn ($a) => ['type' => $a->type->value, 'title' => $a->title, 'awarded_at' => $a->awarded_at?->toDateString()])->all(),
            'profile_url' => route('site.community.show', $u),
        ])->all());
    }
}
