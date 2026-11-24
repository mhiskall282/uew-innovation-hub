<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The signed-in user's in-app notifications. Users only ever reach their own. */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';

        return view('notifications.index', [
            'layout' => $user->isStaff() ? 'layouts.workspace' : 'layouts.portal',
            'filter' => $filter,
            'unread' => $user->unreadNotifications()->count(),
            'notifications' => ($filter === 'unread' ? $user->unreadNotifications() : $user->notifications())
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    /** Mark one as read and follow its link. */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $url = (string) ($notification->data['url'] ?? '');

        // Only same-site paths are followed (no open redirects).
        // Browsers treat "/\host" like "//host", so backslashes and control characters are refused too.
        return str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! preg_match('/[\\\\\x00-\x1F]/', $url)
            ? redirect($url)
            : redirect()->route('notifications.index');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }
}
