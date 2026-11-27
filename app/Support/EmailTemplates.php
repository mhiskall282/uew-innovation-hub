<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\EmailTemplate;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Registry and renderer for system e-mails.
 *
 * Each template has a default subject and Markdown body with {{ placeholders }}.
 * Admins can override both from the workspace; overrides are stored in
 * email_templates and cached. Placeholder values are escaped before they are
 * inserted, so member-supplied text (names, titles) cannot inject HTML or
 * Markdown links.
 */
final class EmailTemplates
{
    private const CACHE_KEY = 'hub.email_templates';

    /**
     * @return array<string, array{name: string, description: string, group: string, subject: string, body: string, placeholders: array<string, string>, action: ?string}>
     */
    public static function definitions(): array
    {
        $common = ['name' => 'Recipient\'s full name', 'first_name' => 'Recipient\'s first name', 'site_name' => 'UEW Innovation Hub', 'contact_email' => 'Hub contact e-mail'];

        return [
            'verification_code' => [
                'name' => 'E-mail verification code',
                'description' => 'Sent when someone creates an account, or asks for a new code.',
                'group' => 'Accounts',
                'subject' => 'Your verification code: {{code}}',
                'body' => "Hello {{first_name}},\n\nConfirm your e-mail address to finish setting up your {{site_name}} account. Press the button below, or enter this code on the verification page:\n\n# {{code}}\n\nThe code and the button work once and expire in {{minutes}} minutes. If you did not create an account, you can ignore this e-mail.",
                'placeholders' => $common + ['code' => 'Six-digit code', 'minutes' => 'Minutes until the code expires'],
                'action' => 'Verify my e-mail',
            ],
            'password_reset_code' => [
                'name' => 'Password reset code',
                'description' => 'Sent when someone uses "Forgot password".',
                'group' => 'Accounts',
                'subject' => 'Your password reset code: {{code}}',
                'body' => "Hello {{first_name}},\n\nWe received a request to reset the password for your {{site_name}} account. Press the button below to choose a new password, or enter this code on the reset page:\n\n# {{code}}\n\nThe code and the button work once and expire in {{minutes}} minutes. If you did not ask for this, ignore this e-mail; your password will not change.",
                'placeholders' => $common + ['code' => 'Six-digit code', 'minutes' => 'Minutes until the code expires'],
                'action' => 'Choose a new password',
            ],
            'welcome' => [
                'name' => 'Welcome',
                'description' => 'Sent after a new member verifies their e-mail.',
                'group' => 'Accounts',
                'subject' => 'Welcome to the {{site_name}}',
                'body' => "Hello {{first_name}},\n\nYour account is ready. You can now apply to open programmes, follow your applications and hear about new opportunities that match your interests.\n\n**Next step:** finish your profile so we can recommend the right programmes.",
                'placeholders' => $common,
                'action' => 'Go to my account',
            ],
            'application_submitted' => [
                'name' => 'Application received',
                'description' => 'Sent to the applicant when they submit an application.',
                'group' => 'Applications',
                'subject' => 'Application received: {{program}}',
                'body' => "Hello {{first_name}},\n\nThank you for applying to **{{program}}**. We have received your application.\n\n**Reference:** {{reference}}\n\nThe review panel will assess every application. You can follow the status of yours at any time from your account.",
                'placeholders' => $common + ['program' => 'Programme title', 'reference' => 'Application reference'],
                'action' => 'View my application',
            ],
            'application_status' => [
                'name' => 'Application status update',
                'description' => 'Sent to the applicant when a reviewer changes the status (if "notify" is ticked).',
                'group' => 'Applications',
                'subject' => 'Update on your application to {{program}}',
                'body' => "Hello {{first_name}},\n\nThere is an update on your application to **{{program}}** ({{reference}}).\n\n**Status:** {{status}}\n\n{{status_message}}\n\n{{decision_message}}",
                'placeholders' => $common + ['program' => 'Programme title', 'reference' => 'Application reference', 'status' => 'New status', 'status_message' => 'Standard text for the status', 'decision_message' => 'Optional message written by the reviewer'],
                'action' => 'View my application',
            ],
            'program_launched' => [
                'name' => 'New programme announcement',
                'description' => 'Sent to opted-in members whose interests match, when staff publish a programme with "notify members" ticked.',
                'group' => 'Announcements',
                'subject' => 'New opportunity: {{program}}',
                'body' => "Hello {{first_name}},\n\nA new {{program_type}} that matches your interests is now open at the {{site_name}}.\n\n## {{program}}\n\n{{summary}}\n\n**Deadline:** {{deadline}}",
                'placeholders' => $common + ['program' => 'Programme title', 'program_type' => 'Grant, fellowship, event...', 'summary' => 'Programme summary', 'deadline' => 'Closing date'],
                'action' => 'View and apply',
            ],
            'staff_new_application' => [
                'name' => 'New application (staff alert)',
                'description' => 'Sent to reviewers who have application alerts switched on.',
                'group' => 'Staff',
                'subject' => 'New application: {{program}}',
                'body' => "Hello {{first_name}},\n\n**{{applicant}}** submitted an application to **{{program}}**.\n\n**Reference:** {{reference}}",
                'placeholders' => $common + ['program' => 'Programme title', 'applicant' => 'Applicant name', 'reference' => 'Application reference'],
                'action' => 'Review application',
            ],
            'test_email' => [
                'name' => 'Test e-mail',
                'description' => 'Sent from Site settings to check that mail delivery works.',
                'group' => 'Staff',
                'subject' => 'Test e-mail from the {{site_name}}',
                'body' => "Hello {{first_name}},\n\nIf you can read this, e-mail delivery from the {{site_name}} is working.",
                'placeholders' => $common,
                'action' => null,
            ],
        ];
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::definitions());
    }

    /** @return array{subject: string, body: string, customised: bool} */
    public static function content(string $key): array
    {
        $definition = self::definitions()[$key];
        $override = self::overrides()[$key] ?? null;

        return [
            'subject' => $override['subject'] ?? $definition['subject'],
            'body' => $override['body'] ?? $definition['body'],
            'customised' => $override !== null,
        ];
    }

    /**
     * Render a template with values.
     *
     * @param  array<string, scalar|null>  $values
     * @return array{subject: string, html: string, text: string}
     */
    public static function render(string $key, array $values, ?string $subject = null, ?string $body = null): array
    {
        $content = self::content($key);
        $values = self::withDefaults($values);

        $subject = self::fill($subject ?? $content['subject'], $values, escapeMarkdown: false);
        $markdown = self::fill($body ?? $content['body'], $values, escapeMarkdown: true);

        return [
            'subject' => Str::limit(strip_tags($subject), 200, ''),
            'html' => self::inlineStyles((string) Str::markdown($markdown, ['html_input' => 'escape', 'allow_unsafe_links' => false])),
            'text' => $markdown,
        ];
    }

    /**
     * Inline styles on the Markdown output, so Outlook and webmail (which drop
     * or rewrite <style> blocks) show the same design. A level-1 heading is the
     * one-time-code panel.
     */
    public static function inlineStyles(string $html): string
    {
        $styles = [
            'h1' => "margin: 22px 0; padding: 18px 12px; background-color: #eef0fb; border: 2px dashed #25267a; border-radius: 12px; text-align: center; font-family: 'SFMono-Regular', Consolas, 'Courier New', monospace; font-size: 36px; line-height: 44px; letter-spacing: 10px; font-weight: 800; color: #25267a;",
            'h2' => 'margin: 24px 0 8px; font-size: 20px; line-height: 28px; font-weight: 800; color: #25267a;',
            'h3' => 'margin: 20px 0 6px; font-size: 16px; line-height: 24px; font-weight: 700; color: #25267a;',
            'p' => 'margin: 0 0 16px;',
            'ul' => 'margin: 0 0 16px; padding-left: 22px;',
            'ol' => 'margin: 0 0 16px; padding-left: 22px;',
            'li' => 'margin: 0 0 6px;',
            'blockquote' => 'margin: 0 0 16px; padding: 10px 16px; border-left: 4px solid #f59e0b; background-color: #fffbeb; color: #78350f;',
            'strong' => 'color: #0f172a; font-weight: 700;',
            'hr' => 'border: 0; border-top: 1px solid #e2e8f0; margin: 22px 0;',
        ];

        foreach ($styles as $tag => $style) {
            $html = preg_replace('/<'.$tag.'(\s|>)/', '<'.$tag.' style="'.$style.'"$1', $html) ?? $html;
        }

        $html = preg_replace('/<h1 /', '<h1 class="code" ', $html, 1) ?? $html;

        return preg_replace('/<a /', '<a style="color: #25267a; font-weight: 600; text-decoration: underline;" ', $html) ?? $html;
    }

    /** Sample values for previews in the workspace. */
    public static function sample(string $key): array
    {
        return [
            'name' => 'Ama Owusu', 'first_name' => 'Ama', 'code' => '482913', 'minutes' => 10,
            'program' => 'Student Innovation Fellowship 2026', 'program_type' => 'fellowship', 'reference' => 'UEW-AP-2026-7K3QX9',
            'status' => 'Shortlisted', 'status_message' => 'Your application has been shortlisted. The Hub will contact you about next steps.',
            'decision_message' => 'The panel would like you to present your prototype on 12 March.',
            'summary' => 'Seed funding, lab space and mentorship for student teams.', 'deadline' => now()->addWeeks(3)->format('j F Y'),
            'applicant' => 'Kofi Mensah',
        ];
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, array{subject: string, body: string}> */
    private static function overrides(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => EmailTemplate::query()
            ->get(['key', 'subject', 'body'])
            ->mapWithKeys(fn ($t) => [$t->key => ['subject' => $t->subject, 'body' => $t->body]])
            ->all());
    }

    /** @param array<string, mixed> $values */
    private static function withDefaults(array $values): array
    {
        $settings = app(SettingsService::class);

        return $values + [
            'site_name' => config('hub.name'),
            'contact_email' => $settings->get('contact_email'),
            'name' => '', 'first_name' => 'there',
        ];
    }

    /** @param array<string, mixed> $values */
    private static function fill(string $template, array $values, bool $escapeMarkdown): string
    {
        return preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/', function ($m) use ($values, $escapeMarkdown) {
            $value = (string) ($values[$m[1]] ?? '');

            return $escapeMarkdown ? addcslashes($value, '\\`*_{}[]()#+!<>|') : $value;
        }, $template) ?? $template;
    }
}
