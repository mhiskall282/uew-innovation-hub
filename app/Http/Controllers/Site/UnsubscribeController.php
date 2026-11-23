<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MemberService;
use Illuminate\View\View;

/** One-click unsubscribe from announcement e-mails (signed URL, no sign-in needed). */
class UnsubscribeController extends Controller
{
    public function __invoke(User $user, MemberService $members): View
    {
        $members->unsubscribe($user);

        return view('site.unsubscribed', ['email' => $user->email]);
    }
}
