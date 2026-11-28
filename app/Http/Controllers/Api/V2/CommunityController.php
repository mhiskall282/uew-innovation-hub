<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Leaderboard of members who opted in to the public community page. Only
 * display name, affiliation, XP, level and badges are returned; never
 * e-mail, phone or demographic data.
 */
class CommunityController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['limit' => ['nullable', 'integer', 'between:1,100']]);

        $members = User::query()
            ->onLeaderboard()
            ->with(['achievements' => fn ($q) => $q->select(['id', 'user_id', 'type', 'title', 'awarded_at'])])
            ->orderByDesc('xp')
            ->orderBy('id')
            ->take($request->integer('limit', 25))
            ->get(['id', 'name', 'affiliation', 'xp']);

        return $this->respond($members->values()->map(fn (User $u, int $i) => [
            'rank' => $i + 1,
            'name' => $u->name,
            'affiliation' => $u->affiliation?->label(),
            'xp' => (int) $u->xp,
            'level' => $u->level()['name'],
            'badges' => $u->achievements->map(fn ($a) => [
                'type' => $a->type->value,
                'title' => $a->title,
                'awarded_on' => $a->awarded_at?->toDateString(),
            ])->values()->all(),
            'links' => ['html' => route('site.community.show', $u)],
        ])->all());
    }
}
