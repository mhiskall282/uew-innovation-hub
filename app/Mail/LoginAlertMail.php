<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $ipAddress
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cockpit Sign-In Notification — UEW Innovation Hub',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login-alert',
        );
    }
}
