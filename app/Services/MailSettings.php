<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\TemplatedMail;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Outgoing e-mail configured from the workspace (Site settings > E-mail).
 *
 * Values are stored in the settings table; the SMTP password is encrypted
 * with APP_KEY and never sent back to the browser. When no mailer is chosen
 * in the workspace, the .env configuration is used unchanged.
 */
final class MailSettings
{
    public const MAILERS = [
        'env' => 'Use server configuration (.env)',
        'smtp' => 'SMTP server',
        'log' => 'Log only (no e-mails sent)',
    ];

    public const ENCRYPTIONS = ['tls' => 'STARTTLS (port 587)', 'ssl' => 'SSL/TLS (port 465)', 'none' => 'None'];

    public function __construct(private readonly SettingsService $settings) {}

    /** Apply stored settings to the runtime mail configuration. Safe before migrations run. */
    public function apply(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $mailer = $this->settings->get('mail_mailer', 'env');

            if ($from = $this->settings->get('mail_from_address')) {
                config(['mail.from.address' => $from]);
            }
            if ($fromName = $this->settings->get('mail_from_name')) {
                config(['mail.from.name' => $fromName]);
            }

            if ($mailer === 'log') {
                config(['mail.default' => 'log']);
            } elseif ($mailer === 'smtp' && $this->settings->get('mail_host')) {
                $encryption = $this->settings->get('mail_encryption', 'tls');

                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => $this->settings->get('mail_host'),
                    'mail.mailers.smtp.port' => (int) $this->settings->get('mail_port', 587),
                    'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
                    'mail.mailers.smtp.username' => $this->settings->get('mail_username'),
                    'mail.mailers.smtp.password' => $this->password(),
                ]);
            }
        } catch (Throwable) {
            // Never let mail configuration take the site down.
        }
    }

    /**
     * @param  array<string, mixed>  $data  validated mail fields; empty password keeps the current one
     */
    public function save(array $data): void
    {
        $values = [
            'mail_mailer' => $data['mail_mailer'],
            'mail_host' => $data['mail_host'] ?? null,
            'mail_port' => isset($data['mail_port']) ? (int) $data['mail_port'] : null,
            'mail_encryption' => $data['mail_encryption'] ?? 'tls',
            'mail_username' => $data['mail_username'] ?? null,
            'mail_from_address' => $data['mail_from_address'] ?? null,
            'mail_from_name' => $data['mail_from_name'] ?? null,
        ];

        if (! empty($data['clear_password'])) {
            $values['mail_password'] = null;
        } elseif (! empty($data['mail_password'])) {
            $values['mail_password'] = Crypt::encryptString($data['mail_password']);
        }

        $this->settings->update($values, redact: ['mail_password']);

        // Long-running queue workers read mail settings at boot; restart them.
        try {
            Artisan::call('queue:restart');
        } catch (Throwable) {
        }
    }

    public function hasPassword(): bool
    {
        return filled($this->settings->get('mail_password'));
    }

    /** Sends a test e-mail now. Returns null on success or the error message. */
    public function sendTest(User $to): ?string
    {
        $this->apply();

        try {
            Mail::to($to)->send(new TemplatedMail('test_email', ['name' => $to->name, 'first_name' => $to->firstName()]));

            return null;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }

    private function password(): ?string
    {
        $stored = $this->settings->get('mail_password');

        if (! $stored) {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (Throwable) {
            return null;
        }
    }
}
