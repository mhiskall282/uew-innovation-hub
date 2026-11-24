<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Recognition shown on a member's profile. "Participant" is awarded
 * automatically when an application is accepted; staff award the rest.
 */
enum AchievementType: string
{
    case Winner = 'winner';
    case Finalist = 'finalist';
    case Participant = 'participant';
    case Alumni = 'alumni';
    case Mentor = 'mentor';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Winner => 'Winner',
            self::Finalist => 'Finalist',
            self::Participant => 'Participant',
            self::Alumni => 'Alumni',
            self::Mentor => 'Mentor',
            self::Custom => 'Recognition',
        };
    }

    /** Default XP for the award; staff can change it for custom awards. */
    public function xp(): int
    {
        return match ($this) {
            self::Winner => 300,
            self::Finalist => 150,
            self::Participant => 50,
            self::Alumni => 100,
            self::Mentor => 120,
            self::Custom => 50,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Winner, self::Finalist => 'trophy',
            self::Alumni => 'academic',
            self::Mentor => 'users',
            self::Participant, self::Custom => 'star',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Winner => 'gold',
            self::Finalist => 'purple',
            self::Alumni => 'navy',
            self::Mentor => 'green',
            self::Participant, self::Custom => 'blue',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}
