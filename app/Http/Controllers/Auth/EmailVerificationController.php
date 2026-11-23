<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\VerificationCode;
use App\Services\MemberService;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($request->user()->homeUrl());
        }

        return view('auth.verify-email', ['email' => $request->user()->email]);
    }

    public function verify(Request $request, MemberService $members): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'regex:/^\s*\d{3}\s?\d{3}\s*$/']], [
            'code.regex' => 'Enter the 6-digit code from the e-mail.',
        ]);

        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            $members->verifyEmail($user, (string) $request->input('code'));
        }

        return redirect()->intended($user->needsOnboarding() ? route('onboarding.show') : $user->homeUrl())
            ->with('status', 'Your e-mail is verified.');
    }

    public function resend(Request $request, VerificationService $codes): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            $codes->issue($user, VerificationCode::VERIFY_EMAIL);
        }

        return back()->with('status', "A new code is on its way to {$user->email}.");
    }
}
