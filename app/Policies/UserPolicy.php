<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::UsersManage);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::UsersManage);
    }

    /** Staff accounts only; members are managed under Members. */
    public function update(User $user, User $target): bool
    {
        return $user->hasPermission(Permission::UsersManage) && $target->isStaff();
    }

    /** Admins cannot deactivate or delete their own account. */
    public function deactivate(User $user, User $target): bool
    {
        return $user->hasPermission(Permission::UsersManage) && $user->isNot($target) && $target->isStaff();
    }

    public function delete(User $user, User $target): bool
    {
        return $this->deactivate($user, $target);
    }
}
