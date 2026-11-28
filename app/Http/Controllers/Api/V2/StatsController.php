<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Services\MetricsService;
use Illuminate\Http\JsonResponse;

/** Headline impact figures (public projects only), cached by MetricsService. */
class StatsController extends ApiController
{
    public function __invoke(MetricsService $metrics): JsonResponse
    {
        $stats = $metrics->publicStats();

        return $this->respond([
            'projects' => (int) $stats['projects'],
            'active_projects' => (int) $stats['active_projects'],
            'beneficiaries_reached' => (int) $stats['beneficiaries'],
            'funding_secured' => ['amount' => number_format((float) $stats['funding'], 2, '.', ''), 'currency' => 'GHS'],
            'partners' => (int) $stats['partners'],
            'publications' => (int) $stats['publications'],
        ], ['generated_at' => now()->toIso8601String()]);
    }
}
