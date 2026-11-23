<?php

declare(strict_types=1);

namespace App\Enums;

enum Affiliation: string
{
    case Student = 'student';
    case Staff = 'staff';
    case Alumni = 'alumni';
    case Partner = 'partner';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'UEW student',
            self::Staff => 'UEW staff or faculty',
            self::Alumni => 'UEW alumni',
            self::Partner => 'Partner organisation',
            self::External => 'Member of the public',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $a) => [$a->value => $a->label()])->all();
    }
}
