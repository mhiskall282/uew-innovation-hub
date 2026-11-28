<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V2;

use DateTimeInterface;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formatting rules shared by every v2 resource, so all endpoints agree:
 *  - ids are strings (treat them as opaque)
 *  - money is { "amount": "1500.00", "currency": "GHS" } (decimal string, never a float)
 *  - dates are "YYYY-MM-DD", timestamps ISO 8601 with offset
 */
abstract class Resource extends JsonResource
{
    public const CURRENCY = 'GHS';

    /** @return array{amount: string, currency: string}|null */
    protected static function money(mixed $amount): ?array
    {
        return $amount === null ? null : ['amount' => number_format((float) $amount, 2, '.', ''), 'currency' => self::CURRENCY];
    }

    protected static function date(?DateTimeInterface $value): ?string
    {
        return $value?->format('Y-m-d');
    }

    protected static function timestamp(?DateTimeInterface $value): ?string
    {
        return $value?->format(DateTimeInterface::ATOM);
    }

    /** True on the single-record endpoint named `api.v2.{$name}.show`. */
    protected static function isDetail(string $name): bool
    {
        return request()->routeIs("api.v2.{$name}.show");
    }
}
