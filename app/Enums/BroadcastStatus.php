<?php

declare(strict_types=1);

namespace App\Enums;

enum BroadcastStatus: string
{
    case Draft = 'draft';
    case Sending = 'sending';
    case Sent = 'sent';
    /** The fan-out job failed; see failed_jobs. Recipients queued before the failure still get the e-mail. */
    case Failed = 'failed';

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
            self::Failed => 'red',
        };
    }
}
