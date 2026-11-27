<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\TemplatedMail;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

/**
 * Six-digit one-time codes sent by e-mail, for e-mail verification and
 * password resets.
 *
 * - Codes are stored as bcrypt hashes, never in plain text.
 * - Each code expires after EXPIRY_MINUTES and allows MAX_ATTEMPTS guesses.
 * - Issuing a new code invalidates earlier ones for the same purpose.
 * - A new code can be requested once per RESEND_SECONDS.
 * - Codes are sent synchronously so they arrive without a queue worker.
 */
final class VerificationService
{
    public const EXPIRY_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_SECONDS = 60;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @throws ValidationException when asked again too soon
     */
    public function issue(User $user, string $purpose): void
    {
        $throttle = "otp:{$purpose}:{$user->id}";

        if (RateLimiter::tooManyAttempts($throttle, 1)) {
            throw ValidationException::withMessages([
                'code' => 'A code was sent recently. You can ask for a new one in '.RateLimiter::availableIn($throttle).' seconds.',
            ]);
        }

        RateLimiter::hit($throttle, self::RESEND_SECONDS);

        VerificationCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = (string) random_int(100000, 999999);

        $record = new VerificationCode;
        $record->user_id = $user->id;
        $record->purpose = $purpose;
        $record->code_hash = Hash::make($code);
        $record->expires_at = now()->addMinutes(self::EXPIRY_MINUTES);
        $record->save();

        $reset = $purpose === VerificationCode::RESET_PASSWORD;

        // A signed link to the same one-time record: the button in the e-mail.
        // It expires with the code, works once, and only opens a confirmation
        // page; nothing changes until the person presses a button there, so
        // mail scanners that pre-open links cannot use it up.
        $link = URL::temporarySignedRoute(
            $reset ? 'password.link' : 'verification.link',
            $record->expires_at,
            ['user' => $user->id, 'record' => $record->id],
        );

        // The code is only ever delivered by e-mail. It is never shown on screen,
        // stored in the session, or written to logs by the application.
        Mail::to($user)->send(new TemplatedMail($reset ? 'password_reset_code' : 'verification_code', [
            'name' => $user->name,
            'first_name' => $user->firstName(),
            'code' => $code,
            'minutes' => self::EXPIRY_MINUTES,
        ], $link));
    }

    /**
     * Uses the signed link from a code e-mail. Consumes the code it belongs to,
     * so the link and the code each work once, whichever is used first.
     *
     * @throws ValidationException when the code was used, replaced or expired
     */
    public function consumeLink(User $user, string $purpose, int $recordId): void
    {
        $used = DB::transaction(function () use ($user, $purpose, $recordId) {
            $record = VerificationCode::query()
                ->whereKey($recordId)
                ->where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->lockForUpdate()
                ->first();

            if (! $record || $record->expires_at->isPast()) {
                return false;
            }

            $record->forceFill(['consumed_at' => now()])->save();

            return true;
        });

        if (! $used) {
            throw ValidationException::withMessages(['code' => 'This link has expired or has already been used. Ask for a new one.']);
        }
    }

    /**
     * Checks a code and consumes it on success.
     *
     * @throws ValidationException on a wrong, expired or exhausted code
     */
    public function verify(User $user, string $purpose, string $code): void
    {
        // Row lock: parallel guesses are counted one after another, so no
        // request can slip past MAX_ATTEMPTS or reuse a code being consumed.
        $error = DB::transaction(function () use ($user, $purpose, $code) {
            try {
                $this->check($user, $purpose, $code);

                return null;
            } catch (ValidationException $e) {
                return $e; // commit the attempt count before failing
            }
        });

        if ($error) {
            throw $error;
        }
    }

    private function check(User $user, string $purpose, string $code): void
    {
        $record = VerificationCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->lockForUpdate()
            ->first();

        $fail = fn (string $message) => throw ValidationException::withMessages(['code' => $message]);

        if (! $record || $record->expires_at->isPast()) {
            $fail('This code has expired. Ask for a new one.');
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            $record->forceFill(['consumed_at' => now()])->save();
            $fail('Too many incorrect attempts. Ask for a new code.');
        }

        if (! Hash::check(preg_replace('/\D/', '', $code), $record->code_hash)) {
            $record->increment('attempts');
            $left = self::MAX_ATTEMPTS - $record->attempts;
            $this->audit->record('auth.code_failed', "Incorrect {$purpose} code for {$user->email}", $user, actor: $user);
            $fail($left > 0 ? "That code is not correct. {$left} attempt(s) left." : 'Too many incorrect attempts. Ask for a new code.');
        }

        $record->forceFill(['consumed_at' => now()])->save();
    }
}
