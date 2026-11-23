<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Post
 */
class PostResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'type' => $this->type->value,
            'category' => $this->category,
            'summary' => $this->summary ?? Str::limit(strip_tags((string) $this->content), 200),
            'content' => $this->when($request->routeIs('api.v1.news.show'), fn () => $this->content),
            'author' => $this->whenLoaded('author', fn () => $this->author?->name),
            'is_featured' => $this->is_featured,
            'published_at' => $this->published_at?->toIso8601String(),
            'reading_minutes' => $this->readingMinutes(),
            'url' => route('site.news.show', $this->resource),
        ];
    }
}
