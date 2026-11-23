<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BroadcastStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An e-mail sent to a segment of members who opted in to announcements.
 *
 * audience: {"interests": ["STEM", ...], "affiliations": ["student", ...]}
 * Empty lists mean "everyone who opted in".
 *
 * @property BroadcastStatus $status
 */
class Broadcast extends Model
{
    protected $fillable = ['subject', 'body', 'audience', 'program_id'];

    protected $attributes = ['status' => 'draft', 'recipients_count' => 0];

    protected function casts(): array
    {
        return [
            'status' => BroadcastStatus::class,
            'audience' => 'array',
            'sent_at' => 'datetime',
            'recipients_count' => 'integer',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class)->withTrashed();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return list<string> */
    public function interests(): array
    {
        return array_values($this->audience['interests'] ?? []);
    }

    /** @return list<string> */
    public function affiliations(): array
    {
        return array_values($this->audience['affiliations'] ?? []);
    }
}
