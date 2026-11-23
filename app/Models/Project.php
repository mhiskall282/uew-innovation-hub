<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Models\Concerns\FlushesMetrics;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ProjectStatus $status
 */
class Project extends Model
{
    use FlushesMetrics, HasFactory, HasUniqueSlug, Searchable, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'title',
        'summary',
        'category',
        'status',
        'start_date',
        'end_date',
        'beneficiaries_target',
        'beneficiaries_reached',
        'grant_amount',
        'lead_researcher',
        'department',
        'report_file_path',
        'is_public',
        'created_by',
    ];

    protected $attributes = [
        'status' => 'active',
        'is_public' => true,
        'beneficiaries_target' => 0,
        'beneficiaries_reached' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'beneficiaries_target' => 'integer',
            'beneficiaries_reached' => 'integer',
            'grant_amount' => 'decimal:2',
            'is_public' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'project_organizations')
            ->withPivot('role')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * @param  array<string, mixed>  $filters  search, status, category, year, organization
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $query->search($filters['search'] ?? null, ['title', 'summary', 'lead_researcher', 'department']);

        $status = $filters['status'] ?? null;
        if ($status && ProjectStatus::tryFrom($status)) {
            $query->where('status', $status);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        $year = $filters['year'] ?? null;
        if ($year && ctype_digit((string) $year)) {
            $query->whereBetween('start_date', ["{$year}-01-01", "{$year}-12-31"]);
        }

        $organization = $filters['organization'] ?? null;
        if ($organization && ctype_digit((string) $organization)) {
            $query->whereHas('organizations', fn (Builder $q) => $q->where('organizations.id', (int) $organization));
        }

        return $query;
    }

    /**
     * Database-agnostic SQL expression extracting the year from start_date.
     */
    public static function yearExpression(): string
    {
        return match ((new static)->getConnection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%Y', start_date) AS INTEGER)",
            'pgsql' => 'CAST(EXTRACT(YEAR FROM start_date) AS INTEGER)',
            default => 'YEAR(start_date)',
        };
    }

    protected function progress(): Attribute
    {
        return Attribute::get(function (): int {
            if ($this->beneficiaries_target > 0) {
                return (int) min(100, round($this->beneficiaries_reached / $this->beneficiaries_target * 100));
            }

            return $this->status === ProjectStatus::Completed ? 100 : 0;
        });
    }
}
