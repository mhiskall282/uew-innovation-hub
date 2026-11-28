<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V2;

use App\Models\Project;
use Illuminate\Http\Request;

/**
 * Public project. Internal fields (creator, legacy id, visibility flag, file
 * paths) are never exposed.
 *
 * @mixin Project
 */
class ProjectResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
            'focus_area' => $this->category,
            'status' => $this->status->value,
            'start_date' => self::date($this->start_date),
            'end_date' => self::date($this->end_date),
            'beneficiaries' => [
                'target' => $this->beneficiaries_target,
                'reached' => $this->beneficiaries_reached,
                'progress_percent' => $this->progress,
            ],
            'grant' => self::money($this->grant_amount),
            'lead_researcher' => $this->lead_researcher,
            'department' => $this->department,
            'partners' => $this->whenLoaded('organizations', fn () => $this->organizations->map(fn ($o) => [
                'id' => (string) $o->id,
                'name' => $o->name,
                'role' => $o->pivot->role,
            ])->values()->all()),
            'report_url' => $this->report_file_path ? route('site.documents.project-report', $this->resource) : null,
            'updated_at' => self::timestamp($this->updated_at),
            'links' => [
                'self' => route('api.v2.projects.show', $this->resource),
                'html' => route('site.projects.show', $this->resource),
            ],
        ];
    }
}
