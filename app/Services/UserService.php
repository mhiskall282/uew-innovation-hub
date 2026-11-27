<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Role;
use App\Mail\PasswordChangedMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Staff account lifecycle. Role and active status are privileged fields and
 * are only ever set here, never through mass assignment.
 */
final class UserService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{name: string, email: string, department?: ?string, password: string, role: string}  $data
     */
    public function create(array $data): User
    {
        $user = new User([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'department' => $data['department'] ?? null,
            'password' => $data['password'],
        ]);
        $user->role = Role::from($data['role']);
        $user->is_active = true;
        $user->email_verified_at = now(); // issued by the Hub Director
        $user->save();

        $this->audit->record('user.created', "Created staff account for {$user->name} ({$user->role->label()})", $user);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, ?User $actor = null): User
    {
        // Nobody changes their own role: no self-escalation, and no accidental
        // self-demotion that locks the Hub Director out of administration.
        if ($actor?->is($user) && $data['role'] !== $user->role->value) {
            throw ValidationException::withMessages(['role' => 'You cannot change your own role. Ask another Hub Director.']);
        }

        if ($data['role'] !== Role::SuperAdmin->value) {
            $this->guardLastAdministrator($user, 'role', 'This is the only active Hub Director. Assign another Hub Director first.');
        }

        return DB::transaction(function () use ($user, $data) {
            $user->fill([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'department' => $data['department'] ?? null,
            ]);
            $user->role = Role::from($data['role']);

            $passwordChanged = ! empty($data['password']);
            if ($passwordChanged) {
                $user->password = $data['password'];
            }

            $changes = $this->audit->diff($user);
            $user->save();

            $this->audit->record('user.updated', "Updated staff account for {$user->name}", $user, $changes);

            if ($passwordChanged) {
                $this->revokeOtherSessions($user);
                $this->notifyPasswordChange($user);
            }

            return $user;
        });
    }

    public function setActive(User $user, bool $active): User
    {
        if (! $active) {
            $this->guardLastAdministrator($user, 'user', 'The only active Hub Director cannot be deactivated.');
        }

        $user->is_active = $active;
        $user->save();

        if (! $active) {
            $this->revokeOtherSessions($user);
        }

        $this->audit->record(
            $active ? 'user.activated' : 'user.deactivated',
            ($active ? 'Re-activated' : 'Deactivated')." staff account for {$user->name}",
            $user,
        );

        return $user;
    }

    public function changeOwnPassword(User $user, string $password): void
    {
        $user->password = $password;
        $user->save();
        $this->revokeOtherSessions($user);

        $this->audit->record('auth.password_changed', "{$user->name} changed their password", $user);
        $this->notifyPasswordChange($user);
    }

    /**
     * @param  array{name: string, department?: ?string, notify_new_grants?: bool, notify_contact_messages?: bool}  $data
     */
    public function updateOwnProfile(User $user, array $data): User
    {
        $user->name = $data['name'];
        $user->department = $data['department'] ?? null;
        $user->notify_new_grants = (bool) ($data['notify_new_grants'] ?? false);
        $user->notify_contact_messages = (bool) ($data['notify_contact_messages'] ?? false);

        $changes = $this->audit->diff($user);
        $user->save();

        if ($changes) {
            $this->audit->record('user.profile_updated', "{$user->name} updated their profile", $user, $changes);
        }

        return $user;
    }

    /** Password reset by e-mail code: proves e-mail ownership, signs out everywhere. */
    public function resetPassword(User $user, string $password): void
    {
        $user->password = $password;
        $user->email_verified_at ??= now();
        $user->save();
        $this->revokeOtherSessions($user);

        $this->audit->record('auth.password_reset', "{$user->email} reset their password with an e-mail code", $user, actor: $user);
        $this->notifyPasswordChange($user);
    }

    public function signOutOtherSessions(User $user): void
    {
        $this->revokeOtherSessions($user);
        $this->audit->record('auth.sessions_revoked', "{$user->name} signed out of other devices", $user);
    }

    public function delete(User $user): void
    {
        $this->guardLastAdministrator($user, 'user', 'The only active Hub Director cannot be deleted.');
        $this->revokeOtherSessions($user);
        $user->delete();
        $this->audit->record('user.deleted', "Deleted staff account for {$user->name}", $user);
    }

    /**
     * Prevents locking everyone out of user management.
     *
     * @throws ValidationException
     */
    private function guardLastAdministrator(User $user, string $field, string $message): void
    {
        if (! $user->isSuperAdmin() || ! $user->is_active) {
            return;
        }

        $others = User::query()
            ->where('role', Role::SuperAdmin->value)
            ->where('is_active', true)
            ->whereKeyNot($user->getKey())
            ->exists();

        if (! $others) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    /**
     * Signs the user out everywhere except the current request: rotates the
     * remember-me token and drops stored sessions (database session driver).
     * Inactive accounts are also rejected on every request by
     * EnsureAccountIsActive, so cookie sessions are covered too.
     */
    private function revokeOtherSessions(User $user): void
    {
        $user->setRememberToken(Str::random(60));
        $user->saveQuietly();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->when(session()->isStarted(), fn ($q) => $q->where('id', '!=', session()->getId()))
                ->delete();
        }
    }

    private function notifyPasswordChange(User $user): void
    {
        try {
            Mail::to($user->email)->queue(new PasswordChangedMail($user));
        } catch (Throwable $e) {
            Log::warning('Password change notice could not be queued.', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }
}
