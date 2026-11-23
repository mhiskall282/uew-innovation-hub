<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Project;
use App\Models\User;

final class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ProjectsView);
    }

    public function view(User $user, Project $project): bool
    {
        return $user->hasPermission(Permission::ProjectsView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ProjectsManage);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasPermission(Permission::ProjectsManage);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasPermission(Permission::ProjectsDelete);
    }

    public function export(User $user): bool
    {
        return $user->hasPermission(Permission::ReportsExport);
    }
}
