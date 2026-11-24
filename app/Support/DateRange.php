<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Reporting period chosen on the Reports page: a preset (last 30 days, this
 * year...) or a custom from/to. Invalid or reversed input falls back to a
 * safe default, so a crafted query string can never break a report.
 */
final class DateRange
{
    public const PRESETS = [
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
        '12m' => 'Last 12 months',
        'ytd' => 'This year',
        'last_year' => 'Last year',
        'all' => 'All time',
        'custom' => 'Custom range',
    ];

    /** Earliest date a report may start from. */
    private const FLOOR = '2015-01-01';

    private function __construct(
        public readonly string $preset,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
    ) {}

    public static function fromRequest(Request $request, string $default = '12m'): self
    {
        $preset = (string) $request->query('range', $default);
        if (! array_key_exists($preset, self::PRESETS)) {
            $preset = $default;
        }

        if ($preset === 'custom') {
            $from = self::parse($request->query('from'));
            $to = self::parse($request->query('to'));

            if ($from && $to && $from->lte($to)) {
                return new self('custom', $from->startOfDay(), $to->endOfDay());
            }

            $preset = $default; // incomplete or reversed custom range
        }

        return self::preset($preset);
    }

    public static function preset(string $preset): self
    {
        $now = CarbonImmutable::now();

        [$from, $to] = match ($preset) {
            '30d' => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
            '90d' => [$now->subDays(89)->startOfDay(), $now->endOfDay()],
            'ytd' => [$now->startOfYear(), $now->endOfDay()],
            'last_year' => [$now->subYear()->startOfYear(), $now->subYear()->endOfYear()],
            'all' => [CarbonImmutable::parse(self::FLOOR)->startOfDay(), $now->endOfDay()],
            default => [$now->subMonths(11)->startOfMonth(), $now->endOfDay()],
        };

        return new self(array_key_exists($preset, self::PRESETS) ? $preset : '12m', $from, $to);
    }

    private static function parse(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        if (! $date || $date->format('Y-m-d') !== $value) {
            return null; // e.g. 2026-02-31
        }

        return $date->max(CarbonImmutable::parse(self::FLOOR))->min(CarbonImmutable::now()->endOfDay());
    }

    public function isAllTime(): bool
    {
        return $this->preset === 'all';
    }

    public function label(): string
    {
        return $this->preset === 'custom' ? 'Custom range' : self::PRESETS[$this->preset];
    }

    /** e.g. "1 Dec 2025 – 24 Nov 2026" */
    public function describe(): string
    {
        return $this->isAllTime()
            ? 'All time, to '.$this->to->format('j M Y')
            : $this->from->format('j M Y').' – '.$this->to->format('j M Y');
    }

    /** Query string for links and the PDF button. @return array<string, string> */
    public function query(): array
    {
        return $this->preset === 'custom'
            ? ['range' => 'custom', 'from' => $this->from->toDateString(), 'to' => $this->to->toDateString()]
            : ['range' => $this->preset];
    }

    /** Month buckets (Y-m) for charts, at most the last 24. @return list<string> */
    public function months(?CarbonImmutable $earliest = null): array
    {
        $start = $this->isAllTime() && $earliest ? $earliest->startOfMonth() : $this->from->startOfMonth();
        $months = [];
        for ($m = $start; $m->lte($this->to); $m = $m->addMonth()) {
            $months[] = $m->format('Y-m');
        }

        return array_slice($months, -24);
    }

    public function slug(): string
    {
        return $this->from->format('Ymd').'-'.$this->to->format('Ymd');
    }
}
