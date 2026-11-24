<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Enums\AchievementType;
use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Program;
use App\Models\User;
use App\Services\EngagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Staff award and remove member badges. Routes are gated by can:members.manage. */
class AchievementController extends Controller
{
    public function __construct(private readonly EngagementService $engagement) {}

    public function store(Request $request, User $member): RedirectResponse
    {
        abort_unless($member->isMember(), 404);

        $data = $request->validate([
            'type' => ['required', Rule::enum(AchievementType::class)],
            'program_id' => ['nullable', 'integer', Rule::exists('programs', 'id')],
            'title' => [Rule::requiredIf($request->input('type') === AchievementType::Custom->value), 'nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'xp' => ['nullable', 'integer', 'between:0,1000'],
        ], ['title.required' => 'Give a custom award a title.']);

        $type = AchievementType::from($data['type']);
        $program = isset($data['program_id']) ? Program::find($data['program_id']) : null;

        $achievement = $this->engagement->award(
            $member, $type, $program,
            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            xp: $type === AchievementType::Custom ? ($data['xp'] ?? null) : null, // standard badges use fixed XP
            by: $request->user(),
        );

        $message = $achievement->wasRecentlyCreated
            ? "Awarded \"{$achievement->title}\" (+{$achievement->xp} XP)."
            : "{$member->name} already has \"{$achievement->title}\".";

        return back()->with('status', $message);
    }

    public function destroy(Request $request, Achievement $achievement): RedirectResponse
    {
        $this->engagement->revoke($achievement, $request->user());

        return back()->with('status', 'Badge removed and its XP taken back.');
    }
}
