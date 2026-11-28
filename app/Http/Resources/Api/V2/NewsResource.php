<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V2;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Published news, blog post or announcement. `content` (Markdown) is only
 * returned by the single-post endpoint, which keeps lists small.
 *
 * @mixin Post
 */
class NewsResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'title' => $this->title,
            'type' => $this->type->value,
            'category' => $this->category,
            'summary' => $this->summary ?? Str::limit(strip_tags((string) $this->content), 200),
            'content' => $this->when(self::isDetail('news'), fn () => $this->content),
            'content_format' => $this->when(self::isDetail('news'), 'markdown'),
            'author' => $this->whenLoaded('author', fn () => $this->author?->name),
            'is_featured' => (bool) $this->is_featured,
            'published_at' => self::timestamp($this->published_at),
            'reading_minutes' => $this->readingMinutes(),
            'updated_at' => self::timestamp($this->updated_at),
            'links' => [
                'self' => route('api.v2.news.show', $this->resource),
                'html' => route('site.news.show', $this->resource),
            ],
        ];
    }
}
