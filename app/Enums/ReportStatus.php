<?php

declare(strict_types=1);

namespace App\Enums;

/** Review state of an incubation progress and budget report. */
enum ReportStatus: string
{
    case Submitted = 'submitted';
    case Accepted = 'accepted';
    case ChangesRequested = 'changes_requested';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Awaiting review',
            self::Accepted => 'Accepted',
            self::ChangesRequested => 'Changes requested',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Submitted => 'blue',
            self::Accepted => 'green',
            self::ChangesRequested => 'gold',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
