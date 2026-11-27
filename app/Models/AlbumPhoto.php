<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One processed photo: a 1600px "large" and a 640px "thumb" JPEG, re-encoded
 * from the upload (metadata such as GPS location removed). Files are served by
 * Site\GalleryMediaController, which checks the album is published.
 */
class AlbumPhoto extends Model
{
    public const SIZES = ['large' => 1600, 'thumb' => 640];

    protected $fillable = ['caption'];

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'size_bytes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function url(string $size = 'thumb'): string
    {
        // updated_at in the URL busts caches when a photo is replaced.
        return route('media.photo', ['photo' => $this->id, 'size' => $size === 'large' ? 'large' : 'thumb', 'v' => $this->updated_at?->timestamp]);
    }

    public function path(string $size): string
    {
        return $size === 'large' ? $this->path_large : $this->path_thumb;
    }

    /** Pixel size of a rendition, for width/height attributes (prevents layout shift). */
    public function dimensions(string $size = 'thumb'): array
    {
        $max = self::SIZES[$size] ?? self::SIZES['thumb'];
        $scale = min(1, $max / max($this->width, $this->height, 1));

        return [(int) round($this->width * $scale), (int) round($this->height * $scale)];
    }

    public function altText(): string
    {
        return $this->caption ?: 'Photo from '.($this->album?->title ?? 'a UEW Innovation Hub event');
    }
}
