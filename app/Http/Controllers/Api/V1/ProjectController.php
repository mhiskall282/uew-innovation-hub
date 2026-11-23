<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'focus_area' => ['nullable', 'string', Rule::in(config('hub.focus_areas'))],
            'status' => ['nullable', Rule::enum(ProjectStatus::class)],
            'year' => ['nullable', 'integer', 'between:1990,2100'],
            'per_page' => ['nullable', 'integer', 'between:1,'.self::MAX_PER_PAGE],
        ]);

        $projects = Project::public()
            ->with('organizations:id,name')
            ->filter([
                'search' => $filters['search'] ?? null,
                'category' => $filters['focus_area'] ?? null,
                'status' => $filters['status'] ?? null,
                'year' => $filters['year'] ?? null,
            ])
            ->latest('start_date')
            ->latest('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return $this->paginated(ProjectResource::collection($projects));
    }

    public function show(Project $project): JsonResponse
    {
        abort_unless($project->is_public, 404);

        return $this->ok(new ProjectResource($project->load('organizations')));
    }
}
