<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\FlushesMetrics;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Publication extends Model
{
    use FlushesMetrics, HasFactory, HasUniqueSlug, Searchable, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'project_id',
        'title',
        'type',
        'authors',
        'abstract',
        'publish_date',
        'journal_name',
        'doi_url',
        'pdf_file_path',
    ];

    protected $attributes = [
        'downloads_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'publish_date' => 'date',
            'downloads_count' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function hasDocument(): bool
    {
        return ! empty($this->pdf_file_path);
    }
}
