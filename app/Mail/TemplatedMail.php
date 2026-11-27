<?php

declare(strict_types=1);

namespace App\Mail;

use App\Support\EmailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Sends any system e-mail defined in App\Support\EmailTemplates.
 *
 * Not ShouldQueue on purpose: callers choose. Verification and reset codes
 * use send() so they arrive immediately; everything else uses queue().
 *
 * Bulk mail (broadcasts) passes $unsubscribeUrl, which adds the RFC 8058
 * one-click List-Unsubscribe headers that Gmail and Yahoo require.
 */
class TemplatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    private const EYEBROWS = [
        'Accounts' => 'Your account',
        'Applications' => 'Application update',
        'Announcements' => 'New opportunity',
        'Staff' => 'Staff notice',
    ];

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
        public ?string $unsubscribeUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->rendered()['subject']);
    }

    public function headers(): Headers
    {
        return new Headers(text: $this->unsubscribeUrl ? [
            'List-Unsubscribe' => '<'.$this->unsubscribeUrl.'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ] : []);
    }

    public function content(): Content
    {
        $definition = EmailTemplates::definitions()[$this->templateKey] ?? [];
        $rendered = $this->rendered();

        return new Content(
            view: 'emails.templated',
            text: 'emails.templated-text',
            with: [
                'rendered' => $rendered,
                'actionUrl' => $this->actionUrl,
                'actionLabel' => $definition['action'] ?? 'Open',
                'footerNote' => $this->footerNote,
                'eyebrow' => self::EYEBROWS[$definition['group'] ?? ''] ?? null,
                'preheader' => self::preheader($rendered['text']),
            ],
        );
    }

    /** @return array{subject: string, html: string, text: string} */
    public function rendered(): array
    {
        return EmailTemplates::render($this->templateKey, $this->values, $this->customSubject, $this->customBody);
    }

    /** First real sentence of the message (after the greeting), as plain text. */
    public static function preheader(string $markdown): string
    {
        $paragraphs = array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', $markdown) ?: [])));
        if (isset($paragraphs[0]) && preg_match('/^(hello|hi|dear)\b/i', $paragraphs[0])) {
            array_shift($paragraphs);
        }

        $plain = preg_replace(['/\\\\(.)/', '/[#*_>`]+/', '/\[([^\]]*)\]\([^)]*\)/', '/\s+/'], ['$1', '', '$1', ' '], implode(' ', $paragraphs)) ?? '';

        return Str::limit(trim($plain), 140);
    }
}
