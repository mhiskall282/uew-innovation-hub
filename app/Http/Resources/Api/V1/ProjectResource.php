<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public project representation. Internal fields (creator, legacy id,
 * visibility flag, file paths) are never exposed.
 *
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
            'focus_area' => $this->category,
            'status' => $this->status->value,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'beneficiaries' => [
                'target' => $this->beneficiaries_target,
                'reached' => $this->beneficiaries_reached,
                'progress_percent' => $this->progress,
            ],
            'grant_amount' => $this->grant_amount !== null ? (float) $this->grant_amount : null,
            'currency' => 'GHS',
            'lead_researcher' => $this->lead_researcher,
            'department' => $this->department,
            'partners' => $this->whenLoaded('organizations', fn () => $this->organizations->map(fn ($o) => [
                'name' => $o->name,
                'role' => $o->pivot->role,
            ])->values()),
            'report_url' => $this->report_file_path ? route('site.documents.project-report', $this->resource) : null,
            'url' => route('site.projects.show', $this->resource),
        ];
    }
}
