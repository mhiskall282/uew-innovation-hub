<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GrantStatus;
use App\Mail\GrantStatusUpdatedMail;
use App\Models\GrantApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Review of legacy grant applications submitted before member accounts and
 * programmes existed. New applications go through programmes
 * (ApplicationService); these records are kept so no data is lost.
 */
final class GrantApplicationService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function review(GrantApplication $application, GrantStatus $status, ?string $notes, User $reviewer, bool $notifyApplicant = true): GrantApplication
    {
        $previous = $application->status;

        DB::transaction(function () use ($application, $status, $notes, $reviewer) {
            $application->status = $status;
            $application->reviewer_notes = $notes;
            $application->reviewed_by = $reviewer->id;
            $application->reviewed_at = now();

            $changes = $this->audit->diff($application);
            unset($changes['reviewed_at']);
            $application->save();

            $this->audit->record(
                'grant.reviewed',
                "Set application {$application->reference_no} to \"{$status->label()}\"",
                $application,
                $changes,
            );
        });

        if ($notifyApplicant && $previous !== $status) {
            try {
                Mail::to($application->email)->queue(new GrantStatusUpdatedMail($application));
            } catch (Throwable $e) {
                Log::warning('Grant notification could not be queued.', ['error' => $e->getMessage()]);
            }
        }

        return $application;
    }
}
