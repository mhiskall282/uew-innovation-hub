<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\GrantApplication;
use App\Models\User;

/**
 * Applications contain applicants' personal data (Data Protection Act, 2012
 * (Act 843)); only the review panel may see them or their proposal files.
 */
final class GrantApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::GrantsReview);
    }

    public function view(User $user, GrantApplication $application): bool
    {
        return $user->hasPermission(Permission::GrantsReview);
    }

    public function review(User $user, GrantApplication $application): bool
    {
        return $user->hasPermission(Permission::GrantsReview);
    }
}
