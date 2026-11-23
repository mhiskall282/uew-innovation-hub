<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ProgramApplication;
use App\Models\User;

/**
 * Applicants see only their own applications. Reviewers see every submitted
 * application, never other people's drafts.
 */
final class ProgramApplicationPolicy
{
    public function view(User $user, ProgramApplication $application): bool
    {
        if ($application->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission(Permission::ApplicationsReview) && ! $application->isEditable();
    }

    public function withdraw(User $user, ProgramApplication $application): bool
    {
        return $application->user_id === $user->id && $application->status->canWithdraw();
    }

    public function review(User $user, ProgramApplication $application): bool
    {
        return $user->hasPermission(Permission::ApplicationsReview)
            && ! $application->isEditable()
            && $application->user_id !== $user->id;
    }
}
