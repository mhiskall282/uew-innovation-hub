<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\FlushesMetrics;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A partner organisation (funder, agency, institution) linked to projects.
 */
class Organization extends Model
{
    use FlushesMetrics, HasFactory, Searchable;

    protected $fillable = [
        'name',
        'type',
        'website',
        'contact_email',
    ];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_organizations')
            ->withPivot('role')
            ->withTimestamps();
    }
}
