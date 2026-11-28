<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V2;

use App\Models\Organization;
use Illuminate\Http\Request;

/**
 * Partner organisation. The contact e-mail is deliberately never exposed.
 *
 * @mixin Organization
 */
class PartnerResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'website' => $this->website,
            'public_projects' => (int) ($this->projects_count ?? 0),
            'updated_at' => self::timestamp($this->updated_at),
        ];
    }
}
