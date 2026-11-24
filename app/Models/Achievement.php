<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AchievementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A badge on a member's profile (winner, finalist, alumni ...). Written only
 * by EngagementService, which also keeps users.xp in step.
 *
 * @property AchievementType $type
 */
class Achievement extends Model
{
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'type' => AchievementType::class,
            'xp' => 'integer',
            'awarded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class)->withTrashed();
    }

    public function awarder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }
}
