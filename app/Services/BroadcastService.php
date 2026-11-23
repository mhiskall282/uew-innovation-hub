<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BroadcastStatus;
use App\Jobs\SendBroadcast;
use App\Models\Broadcast;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Announcement e-mails to members. Only verified, active members who opted
 * in to announcements are ever included; every message carries a one-click
 * unsubscribe link.
 */
final class BroadcastService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{interests?: list<string>, affiliations?: list<string>}  $audience
     */
    public function recipients(array $audience): Builder
    {
        $interests = array_values(array_filter($audience['interests'] ?? []));
        $affiliations = array_values(array_filter($audience['affiliations'] ?? []));

        return User::query()
            ->members()
            ->active()
            ->whereNotNull('email_verified_at')
            ->where('marketing_opt_in', true)
            ->when($affiliations, fn (Builder $q) => $q->whereIn('affiliation', $affiliations))
            ->when($interests, fn (Builder $q) => $q->where(function (Builder $q) use ($interests) {
                foreach ($interests as $interest) {
                    $q->orWhereJsonContains('interests', $interest);
                }
            }));
    }

    public function count(array $audience): int
    {
        return $this->recipients($audience)->count();
    }

    /** Queue a draft for delivery. */
    public function send(Broadcast $broadcast): void
    {
        if ($broadcast->status !== BroadcastStatus::Draft) {
            return;
        }

        $broadcast->status = BroadcastStatus::Sending;
        $broadcast->recipients_count = $this->count($broadcast->audience ?? []);
        $broadcast->save();

        $this->audit->record('broadcast.sent', "Sent broadcast \"{$broadcast->subject}\" to {$broadcast->recipients_count} member(s)", $broadcast);

        SendBroadcast::dispatch($broadcast->id);
    }
}
