<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\BroadcastStatus;
use App\Mail\TemplatedMail;
use App\Models\Broadcast;
use App\Models\User;
use App\Services\BroadcastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Fans a broadcast out into one queued e-mail per recipient, reading
 * recipients in chunks so memory stays flat for any audience size.
 */
class SendBroadcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public int $broadcastId) {}

    public function handle(BroadcastService $broadcasts): void
    {
        $broadcast = Broadcast::with('program')->find($this->broadcastId);

        if (! $broadcast || $broadcast->status !== BroadcastStatus::Sending) {
            return;
        }

        $actionUrl = $broadcast->program?->isVisible() ? route('site.programs.show', $broadcast->program) : null;
        $sent = 0;

        $broadcasts->recipients($broadcast->audience ?? [])
            ->select(['id', 'name', 'email'])
            ->lazyById(200)
            ->each(function (User $user) use ($broadcast, $actionUrl, &$sent) {
                $unsubscribe = URL::signedRoute('site.unsubscribe', ['user' => $user->id]);

                Mail::to($user)->queue(new TemplatedMail(
                    templateKey: 'program_launched',
                    values: ['name' => $user->name, 'first_name' => $user->firstName()],
                    actionUrl: $actionUrl,
                    footerNote: 'You receive this because you asked for announcements from the UEW Innovation Hub. <a href="'.e($unsubscribe).'">Unsubscribe</a> or change your preferences in your account.',
                    customSubject: $broadcast->subject,
                    customBody: $broadcast->body,
                ));
                $sent++;
            });

        $broadcast->forceFill([
            'status' => BroadcastStatus::Sent,
            'recipients_count' => $sent,
            'sent_at' => now(),
        ])->save();
    }
}
