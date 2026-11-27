<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\MemberService;
use App\Services\UserService;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The buttons in verification and password-reset e-mails.
 *
 * Each link is a temporary signed URL (route middleware `signed`) to one
 * one-time code record, so it can't be forged, expires with the code and works
 * once. Opening a link (GET) only shows a confirmation page; the change happens
 * on POST to the same signed URL. Link scanners that pre-fetch URLs therefore
 * can't verify an account or use up a reset link. No sign-in is needed: holding
 * the link proves control of the inbox, as with the code itself.
 */
class EmailLinkController extends Controller
{
    public function showVerify(Request $request, User $user, int $record): View|RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return $this->afterVerify($request, $user, 'Your e-mail is already verified.');
        }

        return view('auth.link-verify', ['email' => self::mask($user->email), 'action' => $request->fullUrl()]);
    }

    public function verify(Request $request, User $user, int $record, MemberService $members): RedirectResponse
    {
        if (! $user->hasVerifiedEmail()) {
            $members->verifyEmailByLink($user, $record);
        }

        return $this->afterVerify($request, $user, 'Your e-mail is verified.');
    }

    public function showReset(Request $request, User $user, int $record): View
    {
        abort_unless($user->is_active, 404);

        return view('auth.link-reset', ['email' => self::mask($user->email), 'action' => $request->fullUrl()]);
    }

    public function reset(Request $request, User $user, int $record, VerificationService $codes, UserService $users): RedirectResponse
    {
        abort_unless($user->is_active, 404);

        // Validate first, so a weak password doesn't use up the link.
        $data = $request->validate(['password' => ['required', 'confirmed', Password::defaults()]]);

        $codes->consumeLink($user, VerificationCode::RESET_PASSWORD, $record);
        $users->resetPassword($user, $data['password']);

        return redirect()->route('login')->with('status', 'Your password was changed. Sign in with your new password.');
    }

    private function afterVerify(Request $request, User $user, string $status): RedirectResponse
    {
        // Continue in the same browser if this is the person's own session.
        if ($request->user()?->is($user)) {
            return redirect($user->needsOnboarding() ? route('onboarding.show') : $user->homeUrl())->with('status', $status);
        }

        return redirect()->route('login')->with('status', $status.' Sign in to continue.');
    }

    /** a••••@gmail.com: enough to recognise the account, not enough to harvest it. */
    public static function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 1).str_repeat('•', max(3, min(8, mb_strlen($local) - 1))).'@'.$domain;
    }
}
