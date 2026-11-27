<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\AuditLogger;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Lets administrators help people get back in without ever knowing or setting
 * their password: send a password-reset e-mail, or resend the verification
 * e-mail. Staff accounts need users.manage (UserPolicy::update); member
 * accounts need members.manage.
 */
class AccountEmailController extends Controller
{
    public function __construct(private readonly VerificationService $codes, private readonly AuditLogger $audit) {}

    public function passwordReset(Request $request, User $user): RedirectResponse
    {
        $this->authorizeFor($user);
        abort_unless($user->is_active, 422, 'Reactivate the account first.');

        return $this->send($request, $user, VerificationCode::RESET_PASSWORD, 'password-reset', "A password-reset e-mail was sent to {$user->email}.");
    }

    public function verification(Request $request, User $user): RedirectResponse
    {
        $this->authorizeFor($user);

        if ($user->hasVerifiedEmail()) {
            return back()->with('status', "{$user->email} is already verified.");
        }

        return $this->send($request, $user, VerificationCode::VERIFY_EMAIL, 'verification', "A new verification e-mail was sent to {$user->email}.");
    }

    private function authorizeFor(User $user): void
    {
        $user->isMember()
            ? Gate::authorize('members.manage')
            : Gate::authorize('update', $user);
    }

    private function send(Request $request, User $user, string $purpose, string $kind, string $status): RedirectResponse
    {
        try {
            $this->codes->issue($user, $purpose);
        } catch (ValidationException $e) {
            return back()->withErrors(['user' => $e->errors()['code'][0] ?? 'Please wait a minute before sending another e-mail.']);
        }

        $this->audit->record("user.{$kind}_sent", "Sent a {$kind} e-mail to {$user->email}", $user, actor: $request->user());

        return back()->with('status', $status);
    }
}
