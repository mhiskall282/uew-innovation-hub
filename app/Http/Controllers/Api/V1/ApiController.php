<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Every v1 response uses one envelope:
 *
 *   { "success": true, "data": ..., "meta": {...}, "links": {...} }
 *   { "success": false, "message": "...", "errors": {...} }   (see bootstrap/app.php)
 *
 * Responses are public, read-only data, so they carry a short shared cache
 * lifetime that lets a CDN or browser absorb repeated traffic.
 */
abstract class ApiController extends Controller
{
    protected const CACHE_SECONDS = 60;

    protected const MAX_PER_PAGE = 50;

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function ok(JsonResource|array $data, array $meta = []): JsonResponse
    {
        $payload = ['success' => true, 'data' => $data instanceof JsonResource ? $data->resolve() : $data];

        if ($meta) {
            $payload['meta'] = $meta;
        }

        return $this->cached(response()->json($payload));
    }

    protected function paginated(AnonymousResourceCollection $collection): JsonResponse
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = $collection->resource;

        return $this->cached(response()->json([
            'success' => true,
            'data' => $collection->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
                'last' => $paginator->url($paginator->lastPage()),
            ],
        ]));
    }

    protected function perPage(): int
    {
        return max(1, min(self::MAX_PER_PAGE, (int) request()->integer('per_page', 15)));
    }

    private function cached(JsonResponse $response): JsonResponse
    {
        $seconds = (int) config('api.cache_seconds', static::CACHE_SECONDS);

        $response
            ->setPublic()
            ->setMaxAge($seconds)
            ->setSharedMaxAge($seconds)
            // Additive: a weak ETag lets clients revalidate and receive 304 Not Modified.
            ->setEtag(md5((string) $response->getContent()), true);
        $response->isNotModified(request());

        return $response;
    }
}
