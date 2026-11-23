<?php

declare(strict_types=1);

namespace App\Enums;

enum ProgramType: string
{
    case Grant = 'grant';
    case Fellowship = 'fellowship';
    case Incubation = 'incubation';
    case Competition = 'competition';
    case Training = 'training';
    case Event = 'event';

    public function label(): string
    {
        return match ($this) {
            self::Grant => 'Grant',
            self::Fellowship => 'Fellowship',
            self::Incubation => 'Incubation',
            self::Competition => 'Competition',
            self::Training => 'Training',
            self::Event => 'Event',
        };
    }

    public function plural(): string
    {
        return match ($this) {
            self::Incubation => 'Incubation programmes',
            self::Training => 'Training',
            default => $this->label().'s',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Grant => 'banknotes',
            self::Fellowship => 'academic',
            self::Incubation => 'sparkles',
            self::Competition => 'chart',
            self::Training => 'book',
            self::Event => 'calendar',
        };
    }

    public function isEvent(): bool
    {
        return $this === self::Event;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}
