<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Publication
 */
class PublicationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $project = $this->relationLoaded('project') ? $this->project : null;

        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'type' => $this->type,
            'authors' => $this->authors,
            'abstract' => $this->abstract,
            'publish_date' => $this->publish_date?->toDateString(),
            'journal' => $this->journal_name,
            'doi_url' => $this->doi_url,
            'downloads' => $this->downloads_count,
            'document_url' => $this->hasDocument() ? route('site.documents.publication', $this->resource) : null,
            'project' => $project && $project->is_public ? [
                'slug' => $project->slug,
                'title' => $project->title,
            ] : null,
        ];
    }
}
