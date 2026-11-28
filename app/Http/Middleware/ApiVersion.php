<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tags every API response with the version that produced it and, once a
 * version is being retired, the standard deprecation headers:
 *
 *   API-Version: v1
 *   Deprecation: @1767225600                       (RFC 9745)
 *   Sunset: Wed, 01 Jul 2026 00:00:00 GMT           (RFC 8594)
 *   Link: <docs>; rel="deprecation"
 *
 * Dates come from config/api.php, so retiring a version needs no code change.
 */
class ApiVersion
{
    public function handle(Request $request, Closure $next, string $version): Response
    {
        $request->attributes->set('api_version', $version);

        $response = $next($request);

        $config = config("api.versions.{$version}", []);
        $response->headers->set('API-Version', $version);

        if (! empty($config['deprecated_at'])) {
            $response->headers->set('Deprecation', '@'.Carbon::parse($config['deprecated_at'])->getTimestamp());
            $response->headers->set('Link', '<'.config('api.docs_url').'>; rel="deprecation"', false);
        }

        if (! empty($config['sunset_at'])) {
            $response->headers->set('Sunset', Carbon::parse($config['sunset_at'])->utc()->format('D, d M Y H:i:s').' GMT');
        }

        return $response;
    }
}
