<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/** A CMS page (About, FAQ, Privacy, Terms, ...), written in Markdown. */
class Page extends Model
{
    public const FOOTER_CACHE_KEY = 'hub.pages.footer';

    protected $fillable = ['slug', 'title', 'summary', 'body', 'is_published', 'show_in_footer', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_in_footer' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $forget = fn () => Cache::forget(self::FOOTER_CACHE_KEY);
        static::saved($forget);
        static::deleted($forget);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** @return Collection<int, array{slug: string, title: string}> */
    public static function footerLinks(): Collection
    {
        return collect(Cache::remember(self::FOOTER_CACHE_KEY, 3600, fn () => static::published()
            ->where('show_in_footer', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['slug', 'title'])
            ->toArray()));
    }
}
