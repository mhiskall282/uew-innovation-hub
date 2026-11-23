<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Site settings editable by the Hub Director from the workspace: contact
 * details, homepage copy, social links, announcement banner and e-mail.
 *
 * Stored as key/value rows, merged over defaults, cached until changed, and
 * memoised per request. Registered as a singleton in AppServiceProvider.
 */
final class SettingsService
{
    private const CACHE_KEY = 'hub.settings';

    /** @var array<string, mixed>|null */
    private ?array $loaded = null;

    public function __construct(private readonly AuditLogger $audit) {}

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            // Contact
            'contact_email' => config('hub.contact_email'),
            'contact_phone' => '+233 (0) 20 000 0000',
            'address' => config('hub.address'),
            'office_hours' => 'Monday to Friday, 8:00 to 17:00',
            'map_url' => 'https://maps.google.com/?q=University+of+Education+Winneba',

            // Social
            'social_facebook' => null,
            'social_x' => null,
            'social_linkedin' => null,
            'social_youtube' => null,
            'social_instagram' => null,

            // Homepage content
            'hero_eyebrow' => 'University of Education, Winneba',
            'hero_title' => 'Research that changes how Ghana teaches and learns',
            'hero_subtitle' => 'The UEW Innovation Hub funds and supports projects by our researchers, students and partners, from STEM classrooms to clean water for coastal communities.',
            'cta_title' => 'Have an idea that could improve education or your community?',
            'cta_text' => 'Create a free account to apply for grants, fellowships, incubation and training. Every application is reviewed by an academic panel and you can follow its progress online.',
            'footer_about' => 'We support researchers, students and partners at the University of Education, Winneba to turn ideas into projects that improve teaching, learning and communities across Ghana.',

            // Announcement banner
            'announcement_enabled' => false,
            'announcement_text' => null,
            'announcement_url' => null,

            // Registration
            'registration_open' => true,

            // E-mail (see MailSettings)
            'mail_mailer' => 'env',
            'mail_host' => null,
            'mail_port' => 587,
            'mail_encryption' => 'tls',
            'mail_username' => null,
            'mail_password' => null,
            'mail_from_address' => null,
            'mail_from_name' => null,
        ];
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            return $this->loaded = Cache::rememberForever(self::CACHE_KEY, fn () => array_merge(
                $this->defaults(),
                Setting::query()->pluck('value', 'key')->all(),
            ));
        } catch (\Throwable $e) {
            // Database not migrated yet (fresh install) or unreachable: serve defaults
            // rather than taking the whole site down. Not cached, so it recovers on its own.
            report($e);

            return $this->defaults();
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function bool(string $key): bool
    {
        return (bool) $this->get($key, false);
    }

    /** @return array<string, string> label => url, only those that are set */
    public function socialLinks(): array
    {
        return collect([
            'Facebook' => $this->get('social_facebook'),
            'X' => $this->get('social_x'),
            'LinkedIn' => $this->get('social_linkedin'),
            'YouTube' => $this->get('social_youtube'),
            'Instagram' => $this->get('social_instagram'),
        ])->filter()->all();
    }

    /**
     * @param  array<string, mixed>  $values  only keys that exist in defaults() are stored
     * @param  list<string>  $redact  keys whose values must never appear in the audit log
     */
    public function update(array $values, array $redact = []): void
    {
        $current = $this->all();
        $changes = [];

        foreach (array_intersect_key($values, $this->defaults()) as $key => $value) {
            if (($current[$key] ?? null) !== $value) {
                $changes[$key] = in_array($key, $redact, true)
                    ? 'changed'
                    : ['from' => $current[$key] ?? null, 'to' => $value];
            }

            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
        $this->loaded = null;

        if ($changes) {
            $this->audit->record('settings.updated', 'Updated site settings: '.implode(', ', array_keys($changes)), properties: $changes);
        }
    }
}
