<?php

declare(strict_types=1);

namespace App\Enums;

enum ProgramStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Published => 'green',
            self::Closed => 'gold',
            self::Archived => 'slate',
        };
    }

    /** Statuses visible on the public site. */
    public static function publicValues(): array
    {
        return [self::Published->value, self::Closed->value];
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
