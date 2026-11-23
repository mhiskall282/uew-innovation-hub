<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Single entry point for the audit trail.
 *
 * Actor and IP are always taken from the server-side session and request,
 * never from client input, so an entry cannot be spoofed.
 */
final class AuditLogger
{
    /**
     * @param  array<string, mixed>  $properties  extra context, e.g. changed fields
     */
    public function record(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $actor = null,
    ): ActivityLog {
        $actor ??= Auth::user();

        return ActivityLog::create([
            'user_id' => $actor?->getKey(),
            'action' => $action,
            'description' => $description,
            'ip_address' => Request::ip(),
            'properties' => $properties ?: null,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
        ]);
    }

    /**
     * Field-level diff of a model's unsaved changes. Call this BEFORE save():
     * saving resets the model's "original" values.
     *
     * Secrets are never recorded: password and remember_token changes are
     * logged as "changed" without values.
     *
     * @return array<string, array{from: mixed, to: mixed}|string>
     */
    public function diff(Model $model): array
    {
        $diff = [];

        foreach (array_keys($model->getDirty()) as $field) {
            if (in_array($field, ['updated_at', 'created_at'], true)) {
                continue;
            }

            if (in_array($field, ['password', 'remember_token'], true)) {
                $diff[$field] = 'changed';

                continue;
            }

            $diff[$field] = [
                'from' => $this->scalar($model->getOriginal($field)),
                'to' => $this->scalar($model->getAttribute($field)),
            ];
        }

        return $diff;
    }

    private function scalar(mixed $value): mixed
    {
        return match (true) {
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
            default => $value,
        };
    }
}
