<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GrantApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_no',
        'applicant_name',
        'email',
        'phone',
        'institution_or_org',
        'project_title',
        'category',
        'funding_requested',
        'summary',
        'proposal_file_path',
        'status',
        'reviewer_notes',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'funding_requested' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GrantApplication $app) {
            if (empty($app->reference_no)) {
                $app->reference_no = 'UEW-GR-' . strtoupper(Str::random(6)) . '-' . date('Y');
            }
        });
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
