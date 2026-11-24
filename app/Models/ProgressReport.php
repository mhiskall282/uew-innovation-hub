<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A progress and budget report from an accepted applicant (incubatee,
 * grantee or fellow) to the Hub. Written only by ProgressReportService.
 *
 * line_items: list of {category, description, budgeted, spent}
 *
 * @property ReportStatus $status
 */
class ProgressReport extends Model
{
    protected $fillable = [];

    protected $attributes = ['status' => 'submitted'];

    public const CATEGORIES = [
        'Equipment & materials', 'Software & hosting', 'Staff & stipends', 'Travel & transport',
        'Training & workshops', 'Marketing & outreach', 'Professional services', 'Other',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'period_end' => 'date',
            'beneficiaries' => 'integer',
            'budget_received' => 'decimal:2',
            'budget_spent' => 'decimal:2',
            'line_items' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ProgressReport $report) {
            $report->reference ??= static::generateReference();
        });
    }

    public static function generateReference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $reference = 'UEW-PR-'.now()->year.'-'.$code;
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ProgramApplication::class, 'program_application_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Money received but not yet spent. Negative means overspent. */
    public function balance(): float
    {
        return (float) $this->budget_received - (float) $this->budget_spent;
    }

    /** Percentage of received funds spent, or null when nothing was received. */
    public function burnRate(): ?int
    {
        return (float) $this->budget_received > 0
            ? (int) round((float) $this->budget_spent / (float) $this->budget_received * 100)
            : null;
    }

    public function isEditable(): bool
    {
        return $this->status === ReportStatus::ChangesRequested;
    }
}
