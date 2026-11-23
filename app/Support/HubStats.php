<?php

namespace App\Support;

use App\Models\GrantApplication;
use App\Models\Publication;
use Illuminate\Support\Facades\Cache;

/**
 * Small cached counters used in the layout chrome so that every page view
 * does not issue extra COUNT queries.
 */
class HubStats
{
    public const TTL = 300;

    public static function publicationCount(): int
    {
        return Cache::remember('hub.publication_count', self::TTL, fn () => Publication::count());
    }

    public static function pendingGrantCount(): int
    {
        return Cache::remember('hub.pending_grant_count', 60, fn () => GrantApplication::where('status', 'submitted')->count());
    }

    public static function flush(): void
    {
        Cache::forget('hub.publication_count');
        Cache::forget('hub.pending_grant_count');
        Cache::forget('hub.dashboard_metrics');
        Cache::forget('hub.landing_metrics');
    }
}
