<?php

/*
|--------------------------------------------------------------------------
| Open data API: versions and limits
|--------------------------------------------------------------------------
| Each version keeps its URL prefix (/api/v1, /api/v2) and response shape for
| its whole life. Breaking changes ship as a new version; additive changes
| (new fields, new endpoints) may land in an existing one.
|
| Retiring a version:
|   1. Set API_V1_DEPRECATED_AT. Responses then carry `Deprecation` and
|      `Link: <...>; rel="deprecation"` headers (RFC 9745).
|   2. Set API_V1_SUNSET_AT at least 6 months later. Responses carry a
|      `Sunset` header (RFC 8594).
|   3. After the sunset date, remove the version's routes in a release.
*/

return [

    /* The version new clients should use. */
    'current' => 'v2',

    'versions' => [
        'v1' => [
            'status' => 'supported',
            'deprecated_at' => env('API_V1_DEPRECATED_AT'),
            'sunset_at' => env('API_V1_SUNSET_AT'),
        ],
        'v2' => [
            'status' => 'current',
            'deprecated_at' => env('API_V2_DEPRECATED_AT'),
            'sunset_at' => env('API_V2_SUNSET_AT'),
        ],
    ],

    'docs_url' => env('API_DOCS_URL', 'https://github.com/mhiskall282/uew-innovation-hub/tree/main/docs/06-api-reference'),

    /* Requests per minute per client IP, shared across all versions. */
    'rate_limit_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),

    /* Shared cache lifetime (seconds) for successful responses. */
    'cache_seconds' => (int) env('API_CACHE_SECONDS', 60),

    /* v2 cursor pagination: default and maximum page size. */
    'v2' => [
        'default_limit' => 20,
        'max_limit' => 100,
    ],
];
