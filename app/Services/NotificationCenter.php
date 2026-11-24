<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Permission;
use App\Models\User;
use App\Notifications\HubNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Sends in-app notifications. Failures are logged and never break the action
 * that triggered them.
 */
final class NotificationCenter
{
    public function toUser(?User $user, HubNotification $notification): void
    {
        if (! $user || ! $user->is_active) {
            return;
        }

        $this->safely(fn () => $user->notify($notification));
    }

    /**
     * Every active staff member holding the permission, except the actor.
     */
    public function toStaff(Permission $permission, HubNotification $notification, ?User $except = null): void
    {
        $this->safely(function () use ($permission, $notification, $except) {
            User::query()
                ->withPermission($permission)
                ->when($except, fn ($q) => $q->whereKeyNot($except->id))
                ->select(['id', 'role', 'is_active'])
                ->lazyById(200)
                ->chunk(200)
                // Plain arrays: Notification::send() treats a LazyCollection as one notifiable.
                ->each(fn ($users) => Notification::send($users->values()->all(), $notification));
        });
    }

    private function safely(callable $send): void
    {
        try {
            $send();
        } catch (Throwable $e) {
            Log::warning('In-app notification failed.', ['error' => $e->getMessage()]);
        }
    }
}
