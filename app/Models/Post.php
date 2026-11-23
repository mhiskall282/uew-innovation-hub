<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PostType;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A news article, blog post or announcement.
 * Publication state is derived from published_at:
 *   null        => draft
 *   in future   => scheduled
 *   in past/now => published
 *
 * @property PostType $type
 */
class Post extends Model
{
    use HasFactory, HasUniqueSlug, Searchable, SoftDeletes;

    protected $fillable = [
        'title',
        'type',
        'category',
        'summary',
        'content',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->lte(now());
    }

    public function state(): string
    {
        return match (true) {
            $this->published_at === null => 'draft',
            $this->published_at->isFuture() => 'scheduled',
            default => 'published',
        };
    }

    public function stateTone(): string
    {
        return match ($this->state()) {
            'draft' => 'slate',
            'scheduled' => 'gold',
            default => 'green',
        };
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->content)) / 200));
    }
}
