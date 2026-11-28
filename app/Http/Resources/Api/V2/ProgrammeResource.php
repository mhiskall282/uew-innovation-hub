<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V2;

use App\Enums\ProgramStatus;
use App\Models\Program;
use Illuminate\Http\Request;

/**
 * Public programme. Application forms and anything about applicants are
 * never exposed. Controllers load `active_applications_count`, so capacity
 * checks cost no extra query per programme.
 *
 * @mixin Program
 */
class ProgrammeResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $availability = match (true) {
            $this->hasClosed() => 'closed',
            ! $this->hasOpened() => 'upcoming',
            default => 'open',
        };

        $full = $this->capacity > 0 && (int) ($this->active_applications_count ?? 0) >= $this->capacity;

        return [
            'id' => $this->slug,
            'title' => $this->title,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'focus_area' => $this->focus_area,
            'summary' => $this->summary,
            'description' => $this->when(self::isDetail('programmes'), fn () => $this->description),
            'eligibility' => $this->when(self::isDetail('programmes'), fn () => $this->eligibility),
            'availability' => $availability,
            'is_accepting_applications' => $this->status === ProgramStatus::Published && $availability === 'open' && ! $full,
            'is_featured' => (bool) $this->is_featured,
            'funding' => self::money($this->funding_amount),
            'location' => $this->location,
            'opens_at' => self::timestamp($this->opens_at),
            'closes_at' => self::timestamp($this->closes_at),
            'starts_at' => self::timestamp($this->starts_at),
            'updated_at' => self::timestamp($this->updated_at),
            'links' => [
                'self' => route('api.v2.programmes.show', $this->resource),
                'html' => route('site.programs.show', $this->resource),
                'apply' => route('portal.apply', $this->resource),
            ],
        ];
    }
}
