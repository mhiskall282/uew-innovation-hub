<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Permission;
use App\Models\User;
use Closure;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Queues an email to every active staff member who holds a permission and
 * has not opted out (per-user preference column). Failures are logged and
 * never break the request that triggered them.
 */
final class StaffNotifier
{
    /**
     * @param  Closure(User): Mailable  $mail
     */
    public function notify(Permission $permission, string $preference, Closure $mail): int
    {
        $sent = 0;

        try {
            User::query()
                ->withPermission($permission)
                ->where($preference, true)
                ->select(['id', 'name', 'email', 'role', 'is_active'])
                ->lazyById(100)
                ->each(function (User $user) use ($mail, &$sent) {
                    Mail::to($user)->queue($mail($user));
                    $sent++;
                });
        } catch (Throwable $e) {
            Log::warning('Staff notification could not be queued.', ['permission' => $permission->value, 'error' => $e->getMessage()]);
        }

        return $sent;
    }
}
