<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Shared behaviour for every v2 endpoint.
 *
 * Success:  { "data": ..., "meta": {...}, "links": {...} }
 * Error:    { "error": { "status": 422, "code": "validation_failed", "message": "...", "details": {...} } }
 *           (rendered in bootstrap/app.php)
 *
 * Lists use cursor pagination ordered by an indexed, unique key, so the cost
 * of page 1,000 equals the cost of page 1 and no COUNT(*) runs. Every list
 * accepts `updated_since` so apps can sync only what changed.
 */
abstract class ApiController extends Controller
{
    /**
     * Allowed `sort` values => [column, direction] pairs. The final pair must
     * be unique (id) so the cursor is stable. The first key is the default.
     *
     * @return array<string, list<array{0: string, 1: string}>>
     */
    protected function sorts(): array
    {
        return [
            '-id' => [['id', 'desc']],
            'id' => [['id', 'asc']],
        ];
    }

    /** @return list<string> relations a client may ask for with `include`. */
    protected function includes(): array
    {
        return [];
    }

    /**
     * Validates the shared list parameters plus endpoint filters.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function validateList(Request $request, array $rules = []): array
    {
        return $request->validate($rules + [
            'limit' => ['nullable', 'integer', 'between:1,'.config('api.v2.max_limit')],
            'cursor' => ['nullable', 'string', 'max:512'],
            'sort' => ['nullable', 'string', Rule::in(array_keys($this->sorts()))],
            'updated_since' => ['nullable', 'date'],
            'include' => ['nullable', 'string', 'max:200'],
        ]);
    }

    /** @return list<string> validated `include` values */
    protected function requestedIncludes(Request $request): array
    {
        $requested = array_values(array_filter(array_map('trim', explode(',', (string) $request->query('include', '')))));
        $unknown = array_diff($requested, $this->includes());

        if ($unknown) {
            throw ValidationException::withMessages([
                'include' => 'Unknown include: '.implode(', ', $unknown).'. Allowed: '.(implode(', ', $this->includes()) ?: 'none').'.',
            ]);
        }

        return $requested;
    }

    /**
     * Applies `updated_since` and `sort`, cursor-paginates, and wraps the page.
     *
     * @param  class-string<JsonResource>  $resource
     */
    protected function paginate(Request $request, Builder $query, string $resource): JsonResponse
    {
        if ($since = $request->query('updated_since')) {
            $query->where($query->getModel()->qualifyColumn('updated_at'), '>=', Carbon::parse($since));
        }

        $sorts = $this->sorts();
        foreach ($sorts[$request->query('sort', array_key_first($sorts))] as [$column, $direction]) {
            $query->orderBy($column, $direction);
        }

        $limit = (int) $request->integer('limit', config('api.v2.default_limit'));
        $page = $query->cursorPaginate($limit)->withQueryString();

        return $this->respond($resource::collection($page->getCollection()), [
            'per_page' => $page->perPage(),
            'has_more' => $page->hasMorePages(),
            'next_cursor' => $page->nextCursor()?->encode(),
            'prev_cursor' => $page->previousCursor()?->encode(),
        ], [
            'self' => $request->fullUrl(),
            'next' => $page->nextPageUrl(),
            'prev' => $page->previousPageUrl(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, string|null>  $links
     */
    protected function respond(JsonResource|array $data, array $meta = [], array $links = []): JsonResponse
    {
        $payload = ['data' => $data instanceof JsonResource ? $data->resolve() : $data];
        if ($meta) {
            $payload['meta'] = $meta;
        }
        if ($links) {
            $payload['links'] = $links;
        }

        $seconds = config('api.cache_seconds');
        $response = response()->json($payload, 200, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->setPublic()
            ->setMaxAge($seconds)
            ->setSharedMaxAge($seconds);

        // Weak ETag over the body: clients and CDNs revalidate with If-None-Match and get a 304.
        $response->setEtag(md5((string) $response->getContent()), true);
        $response->isNotModified(request());

        return $response;
    }
}
