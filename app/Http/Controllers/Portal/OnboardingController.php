<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Enums\Affiliation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ProfileRequest;
use App\Services\MemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->user()->needsOnboarding()) {
            return redirect()->route('portal.dashboard');
        }

        return view('portal.onboarding', [
            'user' => $request->user(),
            'affiliations' => Affiliation::options(),
            'focusAreas' => config('hub.focus_areas'),
            'programmeTypes' => array_diff(ProfileRequest::interestOptions(), config('hub.focus_areas')),
        ]);
    }

    public function store(ProfileRequest $request, MemberService $members): RedirectResponse
    {
        $members->completeOnboarding($request->user(), $request->validated());

        return redirect()->intended(route('portal.dashboard'))
            ->with('status', 'Welcome aboard! Your profile is complete.');
    }
}
