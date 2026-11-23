<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'title',
        'slug',
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
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'beneficiaries_target' => 'integer',
            'beneficiaries_reached' => 'integer',
            'grant_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if (empty($project->slug)) {
                $baseSlug = Str::slug($project->title);
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }
                $project->slug = $slug;
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'project_organizations')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('lead_researcher', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        });

        $query->when($filters['status'] ?? null, function ($q, $status) {
            if ($status !== 'all') {
                $q->where('status', $status);
            }
        });

        $query->when($filters['category'] ?? null, function ($q, $category) {
            if ($category !== 'all') {
                $q->where('category', $category);
            }
        });

        $query->when($filters['year'] ?? null, function ($q, $year) {
            if ($year !== 'all') {
                $q->whereYear('start_date', $year);
            }
        });

        $query->when($filters['organization'] ?? null, function ($q, $orgId) {
            if ($orgId !== 'all') {
                $q->whereHas('organizations', function ($orgQuery) use ($orgId) {
                    $orgQuery->where('organizations.id', $orgId);
                });
            }
        });
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->beneficiaries_target > 0) {
            return min(100, (int) round(($this->beneficiaries_reached / $this->beneficiaries_target) * 100));
        }
        return $this->status === 'completed' ? 100 : ($this->status === 'active' ? 50 : 0);
    }
}
