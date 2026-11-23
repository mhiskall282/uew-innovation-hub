<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreContactMessageRequest;
use App\Services\ContactMessageService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(SettingsService $settings): View
    {
        return view('site.contact', [
            'settings' => $settings->all(),
            'socials' => $settings->socialLinks(),
        ]);
    }

    public function store(StoreContactMessageRequest $request, ContactMessageService $messages): RedirectResponse
    {
        $messages->receive($request->messageData(), $request->ip());

        return redirect()->route('site.contact')->with('contact_sent', true);
    }
}
