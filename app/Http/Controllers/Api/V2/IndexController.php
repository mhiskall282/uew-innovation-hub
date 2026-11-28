<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;

/** Discovery document: every v2 endpoint and the conventions it follows. */
class IndexController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        return $this->respond([
            'name' => config('hub.name').' API',
            'version' => 'v2',
            'documentation' => config('api.docs_url'),
            'endpoints' => [
                'stats' => route('api.v2.stats'),
                'reference' => route('api.v2.reference'),
                'projects' => route('api.v2.projects.index'),
                'publications' => route('api.v2.publications.index'),
                'news' => route('api.v2.news.index'),
                'partners' => route('api.v2.partners.index'),
                'programmes' => route('api.v2.programmes.index'),
                'albums' => route('api.v2.albums.index'),
                'community' => route('api.v2.community'),
            ],
            'conventions' => [
                'pagination' => 'cursor: pass meta.next_cursor as ?cursor=; limit 1-'.config('api.v2.max_limit'),
                'sync' => '?updated_since=<ISO 8601> on every list',
                'ids' => 'strings; treat as opaque',
                'money' => '{ "amount": "1000.00", "currency": "GHS" }',
                'caching' => 'ETag + If-None-Match returns 304',
            ],
        ]);
    }
}
