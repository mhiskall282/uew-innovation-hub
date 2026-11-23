<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Append-only audit record. Written exclusively through AuditLogger; rows are
 * never updated or deleted by the application.
 */
class ActivityLog extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'properties',
        'subject_type',
        'subject_id',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }

    /** Area of the system an action belongs to, e.g. "project" for "project.created". */
    public function area(): string
    {
        return explode('.', $this->action)[0];
    }

    public function tone(): string
    {
        return match (true) {
            str_contains($this->action, 'deleted'), str_contains($this->action, 'failed'), str_contains($this->action, 'deactivated') => 'red',
            str_contains($this->action, 'created'), str_contains($this->action, 'submitted'), str_contains($this->action, 'published') => 'green',
            str_starts_with($this->action, 'auth.') => 'purple',
            default => 'blue',
        };
    }
}
