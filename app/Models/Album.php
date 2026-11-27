<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A photo album for one event (pitch day, bootcamp, workshop...). Publishing
 * fields and cover_photo_id are written by GalleryService only.
 */
class Album extends Model
{
    use HasUniqueSlug;

    protected $fillable = ['title', 'summary', 'description', 'event_date', 'location', 'program_id'];

    protected $attributes = ['is_published' => false, 'is_featured' => false];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function photos(): HasMany
    {
        return $this->hasMany(AlbumPhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(AlbumPhoto::class, 'cover_photo_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** Newest events first; albums without a date by publication time. */
    public function scopeLatestEvents(Builder $query): Builder
    {
        return $query->orderByRaw('CASE WHEN event_date IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('event_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }
}
