<?php

declare(strict_types=1);

namespace App\Enums;

enum BroadcastStatus: string
{
    case Draft = 'draft';
    case Sending = 'sending';
    case Sent = 'sent';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Sending => 'gold',
            self::Sent => 'green',
        };
    }
}
