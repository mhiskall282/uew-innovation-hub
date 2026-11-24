<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ProgramApplication;
use App\Services\ConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Applicant side of an application conversation. */
class MessageController extends Controller
{
    public function store(Request $request, ProgramApplication $application, ConversationService $conversations): RedirectResponse
    {
        Gate::authorize('message', $application);
        abort_unless($application->user_id === $request->user()->id, 403);

        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:3000']], [], ['body' => 'message']);
        $conversations->post($application, $request->user(), $data['body']);

        return redirect()->to(route('portal.applications.show', $application).'#messages')->with('status', 'Message sent to the Hub team.');
    }
}
