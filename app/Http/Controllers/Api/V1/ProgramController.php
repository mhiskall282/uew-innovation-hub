<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProgramType;
use App\Http\Resources\Api\V1\ProgramResource;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Public programmes (published or closed). Drafts and archived programmes are never returned. */
class ProgramController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::enum(ProgramType::class)],
            'focus_area' => ['nullable', 'string', Rule::in(config('hub.focus_areas'))],
            'availability' => ['nullable', Rule::in(['open', 'upcoming', 'closed'])],
            'per_page' => ['nullable', 'integer', 'between:1,'.self::MAX_PER_PAGE],
        ]);

        $query = match ($filters['availability'] ?? null) {
            'open' => Program::open(),
            'upcoming' => Program::upcoming(),
            'closed' => Program::closedForApplications(),
            default => Program::visible(),
        };

        $programs = $query
            ->withActiveApplicationsCount()
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['focus_area'] ?? null, fn ($q, $area) => $q->where('focus_area', $area))
            ->search($filters['search'] ?? null, ['title', 'summary'])
            ->orderByDesc('is_featured')
            ->latest('opens_at')
            ->latest('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return $this->paginated(ProgramResource::collection($programs));
    }

    public function show(Program $program): JsonResponse
    {
        abort_unless($program->isVisible(), 404);

        return $this->ok(new ProgramResource($program));
    }
}
