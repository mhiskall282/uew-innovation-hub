<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\Api\V2\PublicationResource;
use App\Models\Publication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filters = $this->validateList($request, [
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', Rule::in(config('hub.publication_types'))],
            'project' => ['nullable', 'string', 'max:255'],
        ]);

        $query = Publication::query()
            ->with('project:id,title,slug,is_public')
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['project'] ?? null, fn ($q, $slug) => $q->whereHas('project', fn ($p) => $p->public()->where('slug', $slug)))
            ->search($filters['search'] ?? null, ['title', 'authors', 'journal_name']);

        return $this->paginate($request, $query, PublicationResource::class);
    }

    public function show(Publication $publication): JsonResponse
    {
        return $this->respond(new PublicationResource($publication->load('project:id,title,slug,is_public')));
    }
}
