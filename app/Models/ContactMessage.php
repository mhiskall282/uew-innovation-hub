<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactStatus;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * A message sent through the public contact form.
 *
 * @property ContactStatus $status
 */
class ContactMessage extends Model
{
    use HasFactory, Searchable;

    public const NEW_COUNT_CACHE_KEY = 'hub.messages.new';

    /** Status, handler and IP are set server-side only. */
    protected $fillable = ['name', 'email', 'phone', 'subject', 'message'];

    protected $attributes = ['status' => 'new'];

    protected function casts(): array
    {
        return [
            'status' => ContactStatus::class,
            'read_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $forget = fn () => Cache::forget(self::NEW_COUNT_CACHE_KEY);
        static::saved($forget);
        static::deleted($forget);
    }

    public static function newCount(): int
    {
        return (int) Cache::remember(self::NEW_COUNT_CACHE_KEY, 60, fn () => static::where('status', ContactStatus::New)->count());
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
