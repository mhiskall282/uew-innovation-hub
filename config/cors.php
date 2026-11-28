<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS)
|--------------------------------------------------------------------------
| The open data API is public and read-only, so any origin may call it with
| GET, without cookies. The exposed headers let browser apps read caching,
| rate-limit and versioning information (otherwise browsers hide them).
*/

return [

    'paths' => ['api', 'api/*'],

    'allowed_methods' => ['GET', 'HEAD', 'OPTIONS'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'ETag',
        'API-Version',
        'Deprecation',
        'Sunset',
        'Link',
        'Retry-After',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
    ],

    // Browsers may cache the preflight answer for a day.
    'max_age' => 86400,

    'supports_credentials' => false,

];
