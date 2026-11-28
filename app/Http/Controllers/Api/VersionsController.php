<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/** GET /api: which versions exist, which one is current, and when old ones retire. */
class VersionsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $date = fn (?string $value) => $value ? Carbon::parse($value)->toDateString() : null;

        $versions = collect(config('api.versions'))->map(fn (array $v, string $name) => [
            'version' => $name,
            'status' => $v['deprecated_at'] ? 'deprecated' : $v['status'],
            'url' => url("api/{$name}"),
            'deprecated_at' => $date($v['deprecated_at']),
            'sunset_at' => $date($v['sunset_at']),
        ])->values();

        return response()->json([
            'data' => [
                'name' => config('hub.name').' API',
                'current' => config('api.current'),
                'documentation' => config('api.docs_url'),
                'versions' => $versions,
            ],
        ])->setPublic()->setMaxAge(config('api.cache_seconds'))->setSharedMaxAge(config('api.cache_seconds'));
    }
}
