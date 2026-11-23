<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Organization;
use App\Models\User;

final class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ProjectsView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::PartnersManage);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->hasPermission(Permission::PartnersManage);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->hasPermission(Permission::PartnersManage);
    }
}
