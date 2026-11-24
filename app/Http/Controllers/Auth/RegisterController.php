<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\MemberService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(SettingsService $settings): View
    {
        return view('auth.register', ['open' => $settings->bool('registration_open')]);
    }

    public function store(RegisterRequest $request, MemberService $members, SettingsService $settings): RedirectResponse
    {
        abort_unless($settings->bool('registration_open'), 403, 'New registrations are paused.');

        $user = $members->register($request->accountData());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')
            ->with('status', "We sent a 6-digit code to {$user->email}.");
    }
}
