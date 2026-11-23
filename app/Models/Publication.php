<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Publication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'project_id',
        'title',
        'slug',
        'type',
        'authors',
        'publish_date',
        'journal_name',
        'doi_url',
        'pdf_file_path',
        'downloads_count',
    ];

    protected function casts(): array
    {
        return [
            'publish_date' => 'date',
            'downloads_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Publication $publication) {
            if (empty($publication->slug)) {
                $baseSlug = Str::slug($publication->title);
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }
                $publication->slug = $slug;
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
