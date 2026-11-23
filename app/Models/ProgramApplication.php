<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Models\Concerns\FlushesMetrics;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A member's application to a programme. One per member per programme.
 * Every field except answers is written by ApplicationService.
 *
 * @property ApplicationStatus $status
 */
class ProgramApplication extends Model
{
    use FlushesMetrics, HasFactory, Searchable;

    protected $fillable = [];

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'answers' => 'array',
            'score' => 'integer',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ProgramApplication $application) {
            $application->reference ??= static::generateReference();
        });
    }

    /** e.g. UEW-AP-2026-7K3QX9 (unambiguous alphabet). */
    public static function generateReference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $reference = 'UEW-AP-'.now()->year.'-'.$code;
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class)->withTrashed();
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function answer(string $key): mixed
    {
        return $this->answers[$key] ?? null;
    }

    public function isEditable(): bool
    {
        return $this->status === ApplicationStatus::Draft;
    }
}
