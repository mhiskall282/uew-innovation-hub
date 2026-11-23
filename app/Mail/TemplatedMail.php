<?php

declare(strict_types=1);

namespace App\Mail;

use App\Support\EmailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sends any system e-mail defined in App\Support\EmailTemplates.
 *
 * Not ShouldQueue on purpose: callers choose. Verification and reset codes
 * use send() so they arrive immediately; everything else uses queue().
 */
class TemplatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<string, scalar|null>  $values
     */
    public function __construct(
        public string $templateKey,
        public array $values = [],
        public ?string $actionUrl = null,
        public ?string $footerNote = null,
        public ?string $customSubject = null,
        public ?string $customBody = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->rendered()['subject']);
    }

    public function content(): Content
    {
        $definition = EmailTemplates::definitions()[$this->templateKey] ?? [];

        return new Content(
            view: 'emails.templated',
            text: 'emails.templated-text',
            with: [
                'rendered' => $this->rendered(),
                'actionUrl' => $this->actionUrl,
                'actionLabel' => $definition['action'] ?? 'Open',
                'footerNote' => $this->footerNote,
            ],
        );
    }

    /** @return array{subject: string, html: string, text: string} */
    public function rendered(): array
    {
        return EmailTemplates::render($this->templateKey, $this->values, $this->customSubject, $this->customBody);
    }
}
