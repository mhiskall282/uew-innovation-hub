<?php

namespace App\Mail;

use App\Models\GrantApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GrantStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GrantApplication $application
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'UEW Innovation Hub — Grant Application Determination (' . $this->application->reference_no . ')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.grant-status',
        );
    }
}
