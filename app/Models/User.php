<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'role',
        'department',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
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

    // Role Checks
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isOfficer(): bool
    {
        return in_array($this->role, ['super_admin', 'officer']);
    }

    public function isReviewer(): bool
    {
        return in_array($this->role, ['super_admin', 'officer', 'reviewer']);
    }

    public function isIntern(): bool
    {
        return $this->role === 'intern';
    }

    public function canManageProjects(): bool
    {
        return in_array($this->role, ['super_admin', 'officer']);
    }

    public function canManagePosts(): bool
    {
        return in_array($this->role, ['super_admin', 'officer', 'intern']);
    }

    public function canReviewApplications(): bool
    {
        return in_array($this->role, ['super_admin', 'officer', 'reviewer']);
    }
}
