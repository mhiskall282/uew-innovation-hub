<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactStatus: string
{
    case New = 'new';
    case Read = 'read';
    case Replied = 'replied';
    case Archived = 'archived';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'gold',
            self::Read => 'blue',
            self::Replied => 'green',
            self::Archived => 'slate',
        };
    }
}
