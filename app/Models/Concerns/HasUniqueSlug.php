<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Generates a unique, URL-safe slug from the model's title on create.
 * Soft-deleted rows are included in the uniqueness check so a restored row
 * can never collide with a newer one.
 */
trait HasUniqueSlug
{
    protected static function bootHasUniqueSlug(): void
    {
        static::creating(function ($model) {
            if (! empty($model->slug)) {
                return;
            }

            $base = Str::slug((string) $model->title) ?: 'item';
            $slug = $base;
            $suffix = 2;

            $query = fn (string $candidate) => method_exists($model, 'bootSoftDeletes')
                ? static::withTrashed()->where('slug', $candidate)->exists()
                : static::where('slug', $candidate)->exists();

            while ($query($slug)) {
                $slug = "{$base}-{$suffix}";
                $suffix++;
            }

            $model->slug = $slug;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
