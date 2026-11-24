<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Affiliation;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Concerns\Searchable;
use App\Services\EngagementService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Staff and member accounts share one table; `role` tells them apart.
 *
 * @property Role $role
 * @property Affiliation|null $affiliation
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, Searchable;

    /**
     * `role`, `is_active`, `email_verified_at` and `onboarded_at` are NOT mass
     * assignable. Services set them explicitly so a crafted request can never
     * escalate privileges or skip verification through fill().
     */
    protected $fillable = [
        'name',
        'email',
        'department',
        'password',
        'phone',
        'gender',
        'date_of_birth',
        'region',
        'affiliation',
        'institution',
        'education_level',
        'disability',
        'bio',
        'interests',
        'marketing_opt_in',
        'public_profile',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'role' => 'viewer',
        'is_active' => true,
        'marketing_opt_in' => false,
        'xp' => 0,
        'public_profile' => false,
    ];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'affiliation' => Affiliation::class,
            'is_active' => 'boolean',
            'notify_new_grants' => 'boolean',
            'notify_contact_messages' => 'boolean',
            'marketing_opt_in' => 'boolean',
            'interests' => 'array',
            'xp' => 'integer',
            'public_profile' => 'boolean',
            'date_of_birth' => 'date',
            'email_verified_at' => 'datetime',
            'onboarded_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProgramApplication::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class)->latest('awarded_at');
    }

    public function progressReports(): HasMany
    {
        return $this->hasMany(ProgressReport::class);
    }

    /** Level name and progress towards the next level, from XP. */
    public function level(): array
    {
        return EngagementService::level((int) $this->xp);
    }

    public function scopeOnLeaderboard(Builder $query): Builder
    {
        return $query->members()->active()->where('public_profile', true)->where('xp', '>', 0);
    }

    public function hasPermission(Permission $permission): bool
    {
        return $this->is_active && $this->role->allows($permission);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }

    public function isMember(): bool
    {
        return $this->role === Role::Member;
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /** Members must finish onboarding; staff never need to. */
    public function needsOnboarding(): bool
    {
        return $this->isMember() && $this->onboarded_at === null;
    }

    /** Where this account lands after signing in. */
    public function homeUrl(): string
    {
        return $this->isStaff() ? route('workspace.dashboard') : route('portal.dashboard');
    }

    public function firstName(): string
    {
        return explode(' ', trim($this->name))[0] ?: $this->name;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)) ?: [])
            ->filter(fn ($part) => preg_match('/^\p{L}/u', $part))
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->join('') ?: 'U';
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /** Profile fields filled in, for the portal completeness meter. */
    public function profileCompleteness(): int
    {
        $checks = [
            filled($this->name), filled($this->phone), filled($this->gender), $this->date_of_birth !== null,
            filled($this->region), $this->affiliation !== null, filled($this->institution),
            filled($this->education_level), filled($this->bio), ! empty($this->interests), $this->hasVerifiedEmail(),
        ];

        return (int) round(count(array_filter($checks)) / count($checks) * 100);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeStaff(Builder $query): Builder
    {
        return $query->where('role', '!=', Role::Member->value);
    }

    public function scopeMembers(Builder $query): Builder
    {
        return $query->where('role', Role::Member->value);
    }

    /** Active users whose role grants the permission. */
    public function scopeWithPermission(Builder $query, Permission $permission): Builder
    {
        $roles = collect(Role::cases())->filter(fn (Role $r) => $r->allows($permission))->map->value->all();

        return $query->active()->whereIn('role', $roles);
    }
}
