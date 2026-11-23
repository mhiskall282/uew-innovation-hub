<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Display formatting used in views. Pure PHP, so it does not depend on the
 * intl extension (not always available on low-cost hosting).
 */
final class Format
{
    /** 1250 => "1.3K", 2_400_000 => "2.4M" */
    public static function compact(float|int|null $value): string
    {
        $value = (float) $value;

        foreach ([1_000_000_000 => 'B', 1_000_000 => 'M', 1_000 => 'K'] as $size => $suffix) {
            if (abs($value) >= $size) {
                $short = number_format($value / $size, 1);

                return (str_ends_with($short, '.0') ? substr($short, 0, -2) : $short).$suffix;
            }
        }

        return number_format($value);
    }

    /** Full currency amount, e.g. "GH₵ 185,000.00" */
    public static function money(float|int|string|null $value, int $decimals = 2): string
    {
        return config('hub.currency').' '.number_format((float) $value, $decimals);
    }

    /** Template placeholder as shown to editors, e.g. "{{first_name}}". */
    public static function placeholder(string $key): string
    {
        return '{'.'{'.$key.'}'.'}';
    }

    /** Short currency amount, e.g. "GH₵ 1.7M" */
    public static function moneyCompact(float|int|string|null $value): string
    {
        return config('hub.currency').' '.self::compact((float) $value);
    }
}
