<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Finds accounts that still use a well-known default password (for example
 * demo accounts seeded before SEED_PASSWORD was set) and moves them to
 * SEED_PASSWORD. Runs on every container boot; does nothing when no account
 * is affected. It never prints or logs a password.
 *
 *   php artisan hub:secure-demo-accounts
 */
class SecureDemoAccounts extends Command
{
    protected $signature = 'hub:secure-demo-accounts {--dry-run : Only report affected accounts}';

    protected $description = 'Replace well-known default passwords with SEED_PASSWORD';

    /** Passwords that must never protect a real or hosted account. */
    public const WEAK = ['password', 'secret', '12345678', 'password123', 'admin', 'changeme'];

    public function handle(AuditLogger $audit): int
    {
        $affected = User::query()->select(['id', 'email', 'password'])->lazyById(200)
            ->filter(fn (User $u) => $this->usesWeakPassword((string) $u->password))
            ->values();

        if ($affected->isEmpty()) {
            $this->info('No accounts use a default password.');

            return self::SUCCESS;
        }

        $this->warn($affected->count().' account(s) use a default password: '.$affected->pluck('email')->join(', '));

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $replacement = (string) config('hub.seed_password');
        if (! self::isStrong($replacement)) {
            $this->error('SEED_PASSWORD is missing or weak (12+ characters with upper and lower case and a number). Set it, then redeploy to secure these accounts.');

            return self::FAILURE;
        }

        foreach ($affected as $user) {
            $user->forceFill(['password' => $replacement, 'remember_token' => null])->save();
            $audit->record('user.password_rotated', "Replaced the default password on {$user->email}", $user, ['password' => 'changed']);
        }

        $this->info('Moved '.$affected->count().' account(s) to SEED_PASSWORD.');

        return self::SUCCESS;
    }

    private function usesWeakPassword(string $hash): bool
    {
        foreach (self::WEAK as $weak) {
            if ($hash !== '' && Hash::check($weak, $hash)) {
                return true;
            }
        }

        return false;
    }

    public static function isStrong(?string $password): bool
    {
        if ($password === null || in_array(strtolower($password), self::WEAK, true)) {
            return false;
        }

        return Validator::make(['p' => $password], ['p' => [Password::min(12)->letters()->mixedCase()->numbers()]])->passes();
    }
}
