<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AchievementType;
use App\Models\Achievement;
use App\Models\Program;
use App\Models\User;
use App\Notifications\HubNotification;
use Illuminate\Support\Facades\DB;

/**
 * Achievements (badges) and experience points.
 *
 * XP sources:
 *  - submitting an application        +10
 *  - submitting a progress report     +20
 *  - an achievement                   its own XP (winner 300, finalist 150 ...)
 *
 * users.xp is a running total kept in step with these events, so leaderboards
 * are a single indexed query.
 */
final class EngagementService
{
    public const XP_APPLICATION = 10;

    public const XP_REPORT = 20;

    /** Level thresholds (minimum XP => name). */
    public const LEVELS = [
        0 => 'Newcomer',
        50 => 'Explorer',
        150 => 'Builder',
        400 => 'Innovator',
        800 => 'Trailblazer',
        1500 => 'Hub Legend',
    ];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly NotificationCenter $notifications,
    ) {}

    public function addXp(User $user, int $points): void
    {
        if ($points > 0) {
            User::whereKey($user->id)->increment('xp', $points);
            $user->xp = (int) $user->xp + $points;
        }
    }

    /**
     * Award an achievement. Awarding the same type for the same programme
     * twice returns the existing one and adds no XP.
     */
    public function award(
        User $user,
        AchievementType $type,
        ?Program $program = null,
        ?string $title = null,
        ?string $description = null,
        ?int $xp = null,
        ?User $by = null,
    ): Achievement {
        $existing = Achievement::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->where('program_id', $program?->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $achievement = DB::transaction(function () use ($user, $type, $program, $title, $description, $xp, $by) {
            $achievement = new Achievement;
            $achievement->forceFill([
                'user_id' => $user->id,
                'program_id' => $program?->id,
                'type' => $type,
                'title' => $title ?: self::defaultTitle($type, $program),
                'description' => $description,
                'xp' => max(0, min(1000, $xp ?? $type->xp())),
                'awarded_by' => $by?->id,
                'awarded_at' => now(),
            ])->save();

            $this->addXp($user, $achievement->xp);

            $this->audit->record('achievement.awarded', "Awarded \"{$achievement->title}\" to {$user->name}", $user, ['type' => $type->value, 'xp' => $achievement->xp], $by);

            return $achievement;
        });

        $this->notifications->toUser($user, new HubNotification(
            title: "You earned a badge: {$achievement->title}",
            body: "+{$achievement->xp} XP. It now shows on your profile.",
            url: route('portal.dashboard'),
            icon: $type->icon(),
            kind: 'achievement',
        ));

        return $achievement;
    }

    public function revoke(Achievement $achievement, User $by): void
    {
        DB::transaction(function () use ($achievement, $by) {
            $user = $achievement->loadMissing('user')->user;
            if ($user) {
                User::whereKey($user->id)->update(['xp' => DB::raw('CASE WHEN xp > '.(int) $achievement->xp.' THEN xp - '.(int) $achievement->xp.' ELSE 0 END')]);
            }

            $this->audit->record('achievement.revoked', "Removed \"{$achievement->title}\" from ".($user?->name ?? 'a deleted account'), $user, ['type' => $achievement->type->value], $by);
            $achievement->delete();
        });
    }

    public static function defaultTitle(AchievementType $type, ?Program $program): string
    {
        $suffix = $program ? ': '.$program->title : '';

        return match ($type) {
            AchievementType::Winner => 'Winner'.$suffix,
            AchievementType::Finalist => 'Finalist'.$suffix,
            AchievementType::Participant => ($program?->type->value === 'incubation' ? 'Incubatee' : 'Selected').$suffix,
            AchievementType::Alumni => 'Alumni'.$suffix,
            AchievementType::Mentor => 'Mentor'.$suffix,
            AchievementType::Custom => 'Recognition'.$suffix,
        };
    }

    /**
     * @return array{name: string, xp: int, floor: int, next: ?int, next_name: ?string, progress: int}
     */
    public static function level(int $xp): array
    {
        $floor = 0;
        $name = self::LEVELS[0];
        $next = null;
        $nextName = null;

        foreach (self::LEVELS as $min => $label) {
            if ($xp >= $min) {
                $floor = $min;
                $name = $label;
            } elseif ($next === null) {
                $next = $min;
                $nextName = $label;
            }
        }

        $progress = $next === null ? 100 : (int) floor(($xp - $floor) / max(1, $next - $floor) * 100);

        return ['name' => $name, 'xp' => $xp, 'floor' => $floor, 'next' => $next, 'next_name' => $nextName, 'progress' => $progress];
    }
}
