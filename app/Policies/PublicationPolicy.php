<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Publication;
use App\Models\User;

final class PublicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::PublicationsManage);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::PublicationsManage);
    }

    public function update(User $user, Publication $publication): bool
    {
        return $user->hasPermission(Permission::PublicationsManage);
    }

    public function delete(User $user, Publication $publication): bool
    {
        return $user->hasPermission(Permission::PublicationsManage);
    }
}
