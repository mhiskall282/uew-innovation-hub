<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Permission;
use App\Models\ApplicationMessage;
use App\Models\ProgramApplication;
use App\Models\User;
use App\Notifications\HubNotification;
use Illuminate\Support\Str;

/**
 * Messages between an applicant and Hub staff about one application. Plain
 * text only; views escape it. Authorisation is checked by the controllers
 * through ProgramApplicationPolicy (view / message).
 */
final class ConversationService
{
    public function __construct(
        private readonly NotificationCenter $notifications,
        private readonly AuditLogger $audit,
    ) {}

    public function post(ProgramApplication $application, User $author, string $body): ApplicationMessage
    {
        $fromStaff = $author->id !== $application->user_id;

        $message = new ApplicationMessage;
        $message->forceFill([
            'program_application_id' => $application->id,
            'user_id' => $author->id,
            'from_staff' => $fromStaff,
            'body' => trim($body),
        ])->save();

        $application->loadMissing('program:id,title,slug', 'applicant');
        $preview = Str::limit(preg_replace('/\s+/', ' ', $message->body), 120);

        if ($fromStaff) {
            $this->audit->record('application.message', "Messaged the applicant on {$application->reference}", $application, actor: $author);
            $this->notifications->toUser($application->applicant, new HubNotification(
                title: "New message about {$application->program->title}",
                body: $preview,
                url: route('portal.applications.show', $application).'#messages',
                icon: 'chat',
                kind: 'message',
            ));
        } else {
            $notice = new HubNotification(
                title: "{$author->name} sent a message",
                body: "{$application->reference}: {$preview}",
                url: route('workspace.applications.show', $application).'#messages',
                icon: 'chat',
                kind: 'message',
            );

            // The reviewer who last handled it, otherwise everyone who reviews applications.
            $reviewer = $application->reviewed_by ? User::find($application->reviewed_by) : null;
            $reviewer && $reviewer->hasPermission(Permission::ApplicationsReview)
                ? $this->notifications->toUser($reviewer, $notice)
                : $this->notifications->toStaff(Permission::ApplicationsReview, $notice, except: $author);
        }

        return $message;
    }

    /** Mark the other side's messages as read for the viewer. */
    public function markRead(ProgramApplication $application, User $viewer): void
    {
        $viewerIsApplicant = $viewer->id === $application->user_id;

        ApplicationMessage::query()
            ->where('program_application_id', $application->id)
            ->where('from_staff', $viewerIsApplicant)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
