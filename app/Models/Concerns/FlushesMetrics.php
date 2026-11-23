<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Services\MetricsService;

/**
 * Invalidates cached dashboard and public metrics whenever a model that
 * feeds them is written. Keeps cache invalidation in one place instead of
 * sprinkling flush calls through controllers.
 */
trait FlushesMetrics
{
    protected static function bootFlushesMetrics(): void
    {
        $flush = fn () => app(MetricsService::class)->flush();

        static::saved($flush);
        static::deleted($flush);

        if (method_exists(static::class, 'restored')) {
            static::restored($flush);
        }
    }
}
