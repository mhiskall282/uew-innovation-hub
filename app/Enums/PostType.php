<?php

declare(strict_types=1);

namespace App\Enums;

enum PostType: string
{
    case News = 'news';
    case Blog = 'blog';
    case Announcement = 'announcement';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::News => 'blue',
            self::Blog => 'purple',
            self::Announcement => 'gold',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
