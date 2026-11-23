<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Enums\Affiliation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ProfileRequest;
use App\Http\Requests\Workspace\UpdatePasswordRequest;
use App\Services\MemberService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('portal.profile', [
            'user' => $request->user(),
            'affiliations' => Affiliation::options(),
            'focusAreas' => config('hub.focus_areas'),
            'programmeTypes' => array_diff(ProfileRequest::interestOptions(), config('hub.focus_areas')),
        ]);
    }

    public function update(ProfileRequest $request, MemberService $members): RedirectResponse
    {
        $members->updateProfile($request->user(), $request->validated());

        return back()->with('status', 'Your profile was updated.');
    }

    public function password(UpdatePasswordRequest $request, UserService $users): RedirectResponse
    {
        $users->changeOwnPassword($request->user(), $request->validated('password'));
        $request->session()->regenerate();

        return back()->with('status', 'Your password was changed. Other devices have been signed out.');
    }
}
