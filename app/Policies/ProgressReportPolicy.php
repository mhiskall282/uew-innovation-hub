<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ProgressReport;
use App\Models\User;

/** Authors see and fix their own reports; reviewers see all and review others'. */
final class ProgressReportPolicy
{
    public function view(User $user, ProgressReport $report): bool
    {
        return $report->user_id === $user->id || $user->hasPermission(Permission::ApplicationsReview);
    }

    public function update(User $user, ProgressReport $report): bool
    {
        return $report->user_id === $user->id && $report->isEditable();
    }

    public function review(User $user, ProgressReport $report): bool
    {
        return $user->hasPermission(Permission::ApplicationsReview) && $report->user_id !== $user->id;
    }
}
