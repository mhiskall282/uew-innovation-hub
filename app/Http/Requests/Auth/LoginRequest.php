<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Console\Commands\SecureDemoAccounts;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Failed attempts per e-mail + IP before a 15-minute lockout. */
    private const MAX_ATTEMPTS = 5;

    /** Failed attempts per account from all IPs combined (credential stuffing). */
    private const MAX_ACCOUNT_ATTEMPTS = 20;

    private const DECAY_SECONDS = 15 * 60;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Attempts sign-in. Wrong password, unknown email and deactivated account
     * all return the same message, so the form cannot be used to discover
     * which staff emails exist.
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            'email' => Str::lower($this->string('email')->trim()->value()),
            'password' => $this->string('password')->value(),
            'is_active' => true,
        ];

        // Fail closed on well-known default passwords where they are not allowed
        // (production). Checked before the attempt and shown for every account,
        // so the message reveals nothing about which accounts exist.
        if (config('hub.block_default_passwords') && in_array(Str::lower($credentials['password']), SecureDemoAccounts::WEAK, true)) {
            $this->hit();
            app(AuditLogger::class)->record('auth.default_password_refused', "Sign-in with a default password refused for {$credentials['email']}");

            throw ValidationException::withMessages([
                'email' => 'This password is not allowed on this site. Use "Forgot password?" to set a new one, or ask the Hub administrator.',
            ]);
        }

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            $this->hit();

            app(AuditLogger::class)->record(
                'auth.login_failed',
                "Failed sign-in attempt for {$credentials['email']}",
            );

            throw ValidationException::withMessages([
                'email' => 'These details do not match an active account.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        /** @var User */
        return Auth::user();
    }

    private function hit(): void
    {
        RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);
        RateLimiter::hit($this->accountKey(), self::DECAY_SECONDS);
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        $key = match (true) {
            RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS) => $this->throttleKey(),
            RateLimiter::tooManyAttempts($this->accountKey(), self::MAX_ACCOUNT_ATTEMPTS) => $this->accountKey(),
            default => null,
        };

        if ($key === null) {
            return;
        }

        event(new Lockout($this));

        $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

        throw ValidationException::withMessages([
            'email' => "Too many sign-in attempts. Try again in {$minutes} minute(s).",
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('email')).'|'.$this->ip());
    }

    /** Not cleared by a successful sign-in from another IP, by design. */
    private function accountKey(): string
    {
        return 'login-account|'.hash('sha256', Str::lower(trim((string) $this->input('email'))));
    }
}
