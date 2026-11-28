<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V2;

use App\Models\Publication;
use Illuminate\Http\Request;

/**
 * Public publication. The linked project appears only when it is public.
 *
 * @mixin Publication
 */
class PublicationResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $project = $this->relationLoaded('project') ? $this->project : null;

        return [
            'id' => $this->slug,
            'title' => $this->title,
            'type' => $this->type,
            'authors' => $this->authors,
            'abstract' => $this->abstract,
            'published_on' => self::date($this->publish_date),
            'journal' => $this->journal_name,
            'doi_url' => $this->doi_url,
            'downloads' => (int) $this->downloads_count,
            'document_url' => $this->hasDocument() ? route('site.documents.publication', $this->resource) : null,
            'project' => $project && $project->is_public ? [
                'id' => $project->slug,
                'title' => $project->title,
                'links' => ['self' => route('api.v2.projects.show', $project)],
            ] : null,
            'updated_at' => self::timestamp($this->updated_at),
            'links' => [
                'self' => route('api.v2.publications.show', $this->resource),
            ],
        ];
    }
}
