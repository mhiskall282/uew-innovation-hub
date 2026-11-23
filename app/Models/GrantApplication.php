<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GrantStatus;
use App\Models\Concerns\FlushesMetrics;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property GrantStatus $status
 */
class GrantApplication extends Model
{
    use FlushesMetrics, HasFactory, Searchable, SoftDeletes;

    /**
     * Only applicant-supplied fields are mass assignable. Status, reviewer
     * and notes are written by GrantApplicationService during review.
     */
    protected $fillable = [
        'applicant_name',
        'email',
        'phone',
        'institution_or_org',
        'project_title',
        'category',
        'funding_requested',
        'summary',
    ];

    protected $attributes = [
        'status' => 'submitted',
    ];

    protected function casts(): array
    {
        return [
            'status' => GrantStatus::class,
            'funding_requested' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GrantApplication $application) {
            if (empty($application->reference_no)) {
                $application->reference_no = static::generateReference();
            }
        });
    }

    /**
     * Reference format: UEW-GR-2026-7K3QX9. Uses an unambiguous alphabet
     * (no 0/O/1/I) so applicants can read it back over the phone.
     */
    public static function generateReference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = collect(range(1, 6))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->join('');
            $reference = 'UEW-GR-'.now()->year.'-'.$code;
        } while (static::withTrashed()->where('reference_no', $reference)->exists());

        return $reference;
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function hasProposal(): bool
    {
        return ! empty($this->proposal_file_path);
    }

    public static function normaliseReference(string $reference): string
    {
        return Str::upper(trim($reference));
    }
}
