<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\GrantApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GrantStatusUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public GrantApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Update on your application ('.$this->application->reference_no.')');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.grant-status');
    }
}
