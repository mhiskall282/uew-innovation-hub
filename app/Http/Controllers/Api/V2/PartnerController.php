<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\Api\V2\PartnerResource;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filters = $this->validateList($request, [
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Organization::query()
            ->withCount(['projects' => fn ($q) => $q->where('is_public', true)])
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when(trim((string) ($filters['search'] ?? '')) !== '', function ($q) use ($filters) {
                $operator = $q->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
                $q->where('name', $operator, '%'.addcslashes(trim($filters['search']), '%_\\').'%');
            });

        return $this->paginate($request, $query, PartnerResource::class);
    }
}
