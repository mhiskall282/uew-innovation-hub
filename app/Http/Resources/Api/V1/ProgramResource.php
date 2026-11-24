<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public view of a programme. Application forms, applicant counts by status
 * and anything about applicants are never exposed.
 *
 * @mixin Program
 */
class ProgramResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        [$availability] = $this->availability();

        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'focus_area' => $this->focus_area,
            'summary' => $this->summary,
            'description' => $this->when($request->routeIs('api.v1.programmes.show'), fn () => $this->description),
            'eligibility' => $this->when($request->routeIs('api.v1.programmes.show'), fn () => $this->eligibility),
            'availability' => $availability,
            'is_accepting_applications' => $this->isAcceptingApplications(),
            'is_featured' => $this->is_featured,
            'funding_amount' => $this->funding_amount !== null ? (float) $this->funding_amount : null,
            'currency' => 'GHS',
            'location' => $this->location,
            'opens_at' => $this->opens_at?->toIso8601String(),
            'closes_at' => $this->closes_at?->toIso8601String(),
            'starts_at' => $this->starts_at?->toIso8601String(),
            'url' => route('site.programs.show', $this->resource),
            'apply_url' => route('portal.apply', $this->resource),
        ];
    }
}
