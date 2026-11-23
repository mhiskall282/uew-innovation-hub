<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\MetricsService;
use Illuminate\Http\JsonResponse;

class StatsController extends ApiController
{
    public function __invoke(MetricsService $metrics): JsonResponse
    {
        $stats = $metrics->publicStats();

        return $this->ok([
            'projects' => $stats['projects'],
            'active_projects' => $stats['active_projects'],
            'beneficiaries_reached' => $stats['beneficiaries'],
            'funding_secured' => $stats['funding'],
            'currency' => 'GHS',
            'partners' => $stats['partners'],
            'publications' => $stats['publications'],
        ], ['generated_at' => now()->toIso8601String()]);
    }
}
