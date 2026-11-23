<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\AuditLogger;
use App\Services\UserService;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * "Forgot password" with a one-time code sent by e-mail.
 *
 * The request step always shows the same message, whether or not the e-mail
 * belongs to an account, so the form cannot be used to discover accounts.
 */
class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request, VerificationService $codes): RedirectResponse
    {
        $email = Str::lower(trim((string) $request->validate(['email' => ['required', 'email', 'max:255']])['email']));

        $user = User::query()->where('email', $email)->where('is_active', true)->first();

        if ($user) {
            try {
                $codes->issue($user, VerificationCode::RESET_PASSWORD);
            } catch (ValidationException) {
                // Resend throttled: keep the response identical.
            }
        }

        $request->session()->put('password_reset_email', $email);

        return redirect()->route('password.reset')
            ->with('status', 'If an account uses that address, we have sent it a 6-digit code.');
    }

    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('password_reset_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password', ['email' => $request->session()->get('password_reset_email')]);
    }

    public function update(Request $request, VerificationService $codes, UserService $users, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'regex:/^\s*\d{3}\s?\d{3}\s*$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], ['code.regex' => 'Enter the 6-digit code from the e-mail.']);

        $email = (string) $request->session()->get('password_reset_email');
        $user = User::query()->where('email', $email)->where('is_active', true)->first();

        if (! $user) {
            throw ValidationException::withMessages(['code' => 'This code has expired. Ask for a new one.']);
        }

        $codes->verify($user, VerificationCode::RESET_PASSWORD, $data['code']);

        $users->resetPassword($user, $data['password']);
        $request->session()->forget('password_reset_email');

        return redirect()->route('login')->with('status', 'Your password was reset. Sign in with your new password.');
    }
}
