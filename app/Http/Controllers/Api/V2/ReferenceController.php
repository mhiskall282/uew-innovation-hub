<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Enums\PostType;
use App\Enums\ProgramType;
use App\Enums\ProjectStatus;
use Illuminate\Http\JsonResponse;

/**
 * The allowed values of every filter, so client apps build their menus from
 * the server instead of hard-coding lists that drift over time.
 */
class ReferenceController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        $options = fn (array $cases) => array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], $cases);

        return $this->respond([
            'focus_areas' => config('hub.focus_areas'),
            'project_statuses' => $options(ProjectStatus::cases()),
            'publication_types' => config('hub.publication_types'),
            'post_types' => $options(PostType::cases()),
            'post_categories' => config('hub.post_categories'),
            'programme_types' => $options(ProgramType::cases()),
            'programme_availability' => ['open', 'upcoming', 'closed'],
            'partner_types' => config('hub.organization_types'),
            'currency' => 'GHS',
        ]);
    }
}
