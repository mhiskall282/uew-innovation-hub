<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\ProgramStatus;
use App\Enums\ProgramType;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An opportunity members can apply to: a grant call, fellowship, incubation
 * cohort, competition, training or event. Each programme carries its own
 * application form (form_schema, see App\Support\FormSchema).
 *
 * @property ProgramStatus $status
 * @property ProgramType $type
 */
class Program extends Model
{
    use HasFactory, HasUniqueSlug, Searchable, SoftDeletes;

    /** status, published_at and created_by are set by ProgramService. */
    protected $fillable = [
        'title', 'type', 'focus_area', 'summary', 'description', 'eligibility', 'funding_amount',
        'location', 'starts_at', 'opens_at', 'closes_at', 'capacity', 'is_featured', 'form_schema',
    ];

    protected $attributes = ['status' => 'draft', 'is_featured' => false];

    protected function casts(): array
    {
        return [
            'status' => ProgramStatus::class,
            'type' => ProgramType::class,
            'funding_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'published_at' => 'datetime',
            'capacity' => 'integer',
            'is_featured' => 'boolean',
            'form_schema' => 'array',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProgramApplication::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Visible on the public site (published or closed). */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereIn('status', ProgramStatus::publicValues());
    }

    /** Published and inside its application window. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', ProgramStatus::Published)
            ->where(fn ($q) => $q->whereNull('opens_at')->orWhere('opens_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('closes_at')->orWhere('closes_at', '>', now()));
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', ProgramStatus::Published)->where('opens_at', '>', now());
    }

    public function scopeClosedForApplications(Builder $query): Builder
    {
        return $query->visible()->where(fn ($q) => $q
            ->where('status', ProgramStatus::Closed)
            ->orWhere('closes_at', '<=', now()));
    }

    public function isVisible(): bool
    {
        return in_array($this->status, [ProgramStatus::Published, ProgramStatus::Closed], true);
    }

    public function hasOpened(): bool
    {
        return $this->opens_at === null || $this->opens_at->lte(now());
    }

    public function hasClosed(): bool
    {
        return $this->status === ProgramStatus::Closed || ($this->closes_at !== null && $this->closes_at->lte(now()));
    }

    public function isFull(): bool
    {
        if (! $this->capacity) {
            return false;
        }

        return $this->applications()
            ->whereNotIn('status', [ApplicationStatus::Draft, ApplicationStatus::Withdrawn])
            ->count() >= $this->capacity;
    }

    public function isAcceptingApplications(): bool
    {
        return $this->status === ProgramStatus::Published && $this->hasOpened() && ! $this->hasClosed() && ! $this->isFull();
    }

    /** Human state for badges: Open, Opens soon, Closed, Draft, Archived. */
    public function availability(): array
    {
        return match (true) {
            $this->status === ProgramStatus::Draft => ['Draft', 'slate'],
            $this->status === ProgramStatus::Archived => ['Archived', 'slate'],
            $this->hasClosed() => ['Closed', 'red'],
            ! $this->hasOpened() => ['Opens '.$this->opens_at->format('j M'), 'gold'],
            default => ['Open', 'green'],
        };
    }

    /** @return list<array<string, mixed>> */
    public function fields(): array
    {
        return $this->form_schema ?? [];
    }
}
