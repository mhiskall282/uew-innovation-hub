<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contact email is deliberately omitted from the public API.
 *
 * @mixin Organization
 */
class PartnerResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'website' => $this->website,
            'public_projects' => $this->whenCounted('projects'),
        ];
    }
}
