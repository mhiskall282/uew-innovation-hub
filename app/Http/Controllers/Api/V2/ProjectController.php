<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Enums\ProjectStatus;
use App\Http\Resources\Api\V2\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Public projects only; internal projects return 404. */
class ProjectController extends ApiController
{
    protected function includes(): array
    {
        return ['partners'];
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $this->validateList($request, [
            'search' => ['nullable', 'string', 'max:100'],
            'focus_area' => ['nullable', 'string', Rule::in(config('hub.focus_areas'))],
            'status' => ['nullable', Rule::enum(ProjectStatus::class)],
            'year' => ['nullable', 'integer', 'between:1990,2100'],
        ]);

        $query = Project::public()
            ->when(in_array('partners', $this->requestedIncludes($request), true), fn ($q) => $q->with('organizations:id,name'))
            ->filter([
                'search' => $filters['search'] ?? null,
                'category' => $filters['focus_area'] ?? null,
                'status' => $filters['status'] ?? null,
                'year' => $filters['year'] ?? null,
            ]);

        return $this->paginate($request, $query, ProjectResource::class);
    }

    public function show(Project $project): JsonResponse
    {
        abort_unless($project->is_public, 404);

        return $this->respond(new ProjectResource($project->load('organizations:id,name')));
    }
}
