<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One message in the conversation between an applicant and Hub staff. */
class ApplicationMessage extends Model
{
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'from_staff' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ProgramApplication::class, 'program_application_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
