<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V2;

use App\Models\Album;
use App\Models\AlbumPhoto;
use Illuminate\Http\Request;

/**
 * Published gallery album. Photos are listed only on the single-album
 * endpoint, and only those whose files still exist.
 *
 * @mixin Album
 */
class AlbumResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
            'description' => $this->when(self::isDetail('albums'), fn () => $this->description),
            'event_date' => self::date($this->event_date),
            'location' => $this->location,
            'photo_count' => (int) ($this->photos_count ?? 0),
            // null when the album has no cover or its files are gone (see AlbumPhoto::isAvailable()).
            'cover' => $this->cover && $this->cover->isAvailable()
                ? self::photo($this->cover->setRelation('album', $this->resource))
                : null,
            'programme' => $this->whenLoaded('program', fn () => $this->program && $this->program->isVisible() ? [
                'id' => $this->program->slug,
                'title' => $this->program->title,
                'links' => ['self' => route('api.v2.programmes.show', $this->program)],
            ] : null),
            'photos' => $this->when(self::isDetail('albums') && $this->relationLoaded('photos'), fn () => $this->photos
                ->filter(fn (AlbumPhoto $p) => $p->isAvailable())
                ->map(fn (AlbumPhoto $p) => self::photo($p))
                ->values()->all()),
            'published_at' => self::timestamp($this->published_at),
            'updated_at' => self::timestamp($this->updated_at),
            'links' => [
                'self' => route('api.v2.albums.show', $this->resource),
                'html' => route('site.gallery.show', $this->resource),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function photo(AlbumPhoto $photo): array
    {
        [$width, $height] = $photo->dimensions('large');
        [$thumbWidth, $thumbHeight] = $photo->dimensions('thumb');

        return [
            'id' => (string) $photo->id,
            'caption' => $photo->caption,
            'alt' => $photo->altText(),
            'large' => ['url' => $photo->url('large'), 'width' => $width, 'height' => $height],
            'thumb' => ['url' => $photo->url('thumb'), 'width' => $thumbWidth, 'height' => $thumbHeight],
        ];
    }
}
