<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * One sign-in page for everyone. Staff land in the workspace, members in
 * their account portal.
 */
class SessionController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();

        // New session id prevents session fixation.
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->saveQuietly();
        $this->audit->record('auth.login', "{$user->name} signed in", $user, actor: $user);

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // Members never land in the staff workspace, even via an intended URL.
        if ($user->isMember()) {
            $intended = (string) $request->session()->pull('url.intended', '');

            return redirect(str_contains($intended, '/workspace') || $intended === '' ? $user->homeUrl() : $intended);
        }

        return redirect()->intended($user->homeUrl());
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $this->audit->record('auth.logout', "{$user->name} signed out", $user, actor: $user);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('site.home')->with('status', 'You have been signed out.');
    }
}
