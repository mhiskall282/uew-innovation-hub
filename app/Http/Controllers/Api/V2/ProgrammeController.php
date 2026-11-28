<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Enums\ProgramType;
use App\Http\Resources\Api\V2\ProgrammeResource;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Public programmes (published or closed). Drafts and archived programmes return 404. */
class ProgrammeController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filters = $this->validateList($request, [
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::enum(ProgramType::class)],
            'focus_area' => ['nullable', 'string', Rule::in(config('hub.focus_areas'))],
            'availability' => ['nullable', Rule::in(['open', 'upcoming', 'closed'])],
        ]);

        $query = match ($filters['availability'] ?? null) {
            'open' => Program::open(),
            'upcoming' => Program::upcoming(),
            'closed' => Program::closedForApplications(),
            default => Program::visible(),
        };

        $query = $query->withActiveApplicationsCount()
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['focus_area'] ?? null, fn ($q, $area) => $q->where('focus_area', $area))
            ->search($filters['search'] ?? null, ['title', 'summary']);

        return $this->paginate($request, $query, ProgrammeResource::class);
    }

    public function show(Program $program): JsonResponse
    {
        abort_unless($program->isVisible(), 404);

        $program->loadCount(['applications as active_applications_count' => fn ($q) => $q->whereNotIn('status', Program::inactiveStatuses())]);

        return $this->respond(new ProgrammeResource($program));
    }
}
