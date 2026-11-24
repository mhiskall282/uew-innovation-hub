<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

/** Discovery document listing the available endpoints. */
class IndexController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        return $this->ok([
            'name' => config('hub.name').' API',
            'version' => 'v1',
            'documentation' => config('hub.api_docs_url'),
            'endpoints' => [
                'stats' => route('api.v1.stats'),
                'projects' => route('api.v1.projects.index'),
                'publications' => route('api.v1.publications.index'),
                'news' => route('api.v1.news.index'),
                'partners' => route('api.v1.partners.index'),
                'programmes' => route('api.v1.programmes.index'),
                'community' => route('api.v1.community'),
            ],
        ]);
    }
}
