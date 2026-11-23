<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Role;
use App\Mail\TemplatedMail;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Lifecycle of public member accounts: sign-up, e-mail verification,
 * onboarding and profile updates.
 */
final class MemberService
{
    public function __construct(
        private readonly VerificationService $codes,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data): User
    {
        $user = DB::transaction(function () use ($data) {
            $user = new User([
                'name' => $data['name'],
                'email' => mb_strtolower(trim($data['email'])),
                'password' => $data['password'],
            ]);
            $user->role = Role::Member;
            $user->is_active = true;
            $user->save();

            $this->audit->record('member.registered', "New member account for {$user->email}", $user, actor: $user);

            return $user;
        });

        $this->codes->issue($user, VerificationCode::VERIFY_EMAIL);

        return $user;
    }

    public function verifyEmail(User $user, string $code): void
    {
        $this->codes->verify($user, VerificationCode::VERIFY_EMAIL, $code);

        $user->forceFill(['email_verified_at' => now()])->save();
        $this->audit->record('member.verified', "{$user->email} verified their e-mail", $user, actor: $user);

        if ($user->isMember()) {
            try {
                Mail::to($user)->queue(new TemplatedMail('welcome', ['name' => $user->name, 'first_name' => $user->firstName()], route('portal.dashboard')));
            } catch (Throwable $e) {
                Log::warning('Welcome e-mail could not be queued.', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data  validated onboarding/profile fields
     */
    public function completeOnboarding(User $user, array $data): User
    {
        $this->fillProfile($user, $data);
        $user->onboarded_at ??= now();
        $user->save();

        $this->audit->record('member.onboarded', "{$user->name} completed their profile", $user, actor: $user);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        $this->fillProfile($user, $data);
        $changes = $this->audit->diff($user);
        $user->save();

        if ($changes) {
            $this->audit->record('member.profile_updated', "{$user->name} updated their profile", $user, $changes, actor: $user);
        }

        return $user;
    }

    public function unsubscribe(User $user): void
    {
        if ($user->marketing_opt_in) {
            $user->forceFill(['marketing_opt_in' => false])->save();
            $this->audit->record('member.unsubscribed', "{$user->email} unsubscribed from announcements", $user, actor: $user);
        }
    }

    /** @param array<string, mixed> $data */
    private function fillProfile(User $user, array $data): void
    {
        $user->fill([
            'name' => $data['name'] ?? $user->name,
            'phone' => $data['phone'] ?? null,
            'affiliation' => $data['affiliation'] ?? null,
            'institution' => $data['institution'] ?? null,
            'department' => $data['department'] ?? null,
            'bio' => $data['bio'] ?? null,
            'interests' => array_values($data['interests'] ?? []),
            'marketing_opt_in' => (bool) ($data['marketing_opt_in'] ?? false),
        ]);
    }
}
