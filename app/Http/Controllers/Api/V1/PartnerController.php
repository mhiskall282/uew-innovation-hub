<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\PartnerResource;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;

class PartnerController extends ApiController
{
    public function index(): JsonResponse
    {
        $partners = Organization::query()
            ->withCount(['projects' => fn ($q) => $q->where('is_public', true)])
            ->orderBy('name')
            ->paginate($this->perPage())
            ->withQueryString();

        return $this->paginated(PartnerResource::collection($partners));
    }
}
