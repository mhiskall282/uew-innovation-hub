<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContactStatus;
use App\Enums\Permission;
use App\Mail\NewContactMessageStaffMail;
use App\Models\ContactMessage;
use App\Models\User;

final class ContactMessageService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly StaffNotifier $staff,
    ) {}

    /**
     * @param  array{name: string, email: string, phone?: ?string, subject: string, message: string}  $data
     */
    public function receive(array $data, ?string $ip): ContactMessage
    {
        $message = new ContactMessage($data);
        $message->ip_address = $ip;
        $message->save();

        $this->audit->record('message.received', "Enquiry from {$message->name}: \"{$message->subject}\"", $message);
        $this->staff->notify(Permission::MessagesManage, 'notify_contact_messages', fn () => new NewContactMessageStaffMail($message));

        return $message;
    }

    /** Opening a new message marks it read. */
    public function markRead(ContactMessage $message, User $user): void
    {
        if ($message->status !== ContactStatus::New) {
            return;
        }

        $message->status = ContactStatus::Read;
        $message->read_at = now();
        $message->handled_by = $user->id;
        $message->save();
    }

    public function setStatus(ContactMessage $message, ContactStatus $status, User $user): void
    {
        $from = $message->status;
        $message->status = $status;
        $message->handled_by = $user->id;
        $message->read_at ??= now();
        $message->save();

        $this->audit->record('message.updated', "Marked enquiry \"{$message->subject}\" as {$status->label()}", $message, [
            'status' => ['from' => $from->value, 'to' => $status->value],
        ]);
    }

    public function delete(ContactMessage $message): void
    {
        $message->delete();
        $this->audit->record('message.deleted', "Deleted enquiry \"{$message->subject}\" from {$message->name}", $message);
    }
}
