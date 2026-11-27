<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Auth\EmailLinkController;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MemberService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Unsubscribe from announcement e-mails with a signed URL (no sign-in).
 *
 * GET shows a confirmation, so mail scanners that pre-open links don't
 * unsubscribe people. POST unsubscribes: it serves the button on that page and
 * RFC 8058 one-click requests from Gmail/Yahoo (the List-Unsubscribe-Post
 * header), which arrive without a CSRF token. That route is exempt from CSRF;
 * the URL signature is what authorises it.
 */
class UnsubscribeController extends Controller
{
    public function show(Request $request, User $user): View
    {
        return view('site.unsubscribe-confirm', [
            'email' => EmailLinkController::mask($user->email),
            'subscribed' => $user->marketing_opt_in,
            'action' => $request->fullUrl(),
        ]);
    }

    public function store(Request $request, User $user, MemberService $members): View|Response
    {
        $members->unsubscribe($user);

        // Mail providers' one-click requests don't need a page.
        if ($request->input('List-Unsubscribe') === 'One-Click') {
            return response('', 204);
        }

        return view('site.unsubscribed', ['email' => $user->email]);
    }
}
