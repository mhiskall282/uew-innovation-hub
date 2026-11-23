<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Mail\LoginAlertMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Dispatch Login Alert Security Notification
            try {
                Mail::to($request->user()->email)->send(new LoginAlertMail($request->user(), $request->ip()));
            } catch (\Throwable $e) {}

            return redirect()->intended(route('dashboard'))
                ->with('status', 'Welcome back to UEW Innovation Hub.');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our institutional records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'You have been successfully logged out.');
    }
}
