<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\PublicationResource;
use App\Models\Publication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', Rule::in(config('hub.publication_types'))],
            'per_page' => ['nullable', 'integer', 'between:1,'.self::MAX_PER_PAGE],
        ]);

        $publications = Publication::query()
            ->with('project:id,title,slug,is_public')
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->search($filters['search'] ?? null, ['title', 'authors', 'journal_name'])
            ->latest('publish_date')
            ->latest('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return $this->paginated(PublicationResource::collection($publications));
    }

    public function show(Publication $publication): JsonResponse
    {
        return $this->ok(new PublicationResource($publication->load('project:id,title,slug,is_public')));
    }
}
