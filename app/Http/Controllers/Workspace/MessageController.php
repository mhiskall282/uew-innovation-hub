<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\ProgramApplication;
use App\Services\ConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Staff side of an application conversation. Route is gated by can:applications.review. */
class MessageController extends Controller
{
    public function store(Request $request, ProgramApplication $application, ConversationService $conversations): RedirectResponse
    {
        Gate::authorize('message', $application);
        abort_if($application->user_id === $request->user()->id, 403);

        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:3000']], [], ['body' => 'message']);
        $conversations->post($application, $request->user(), $data['body']);

        return redirect()->to(route('workspace.applications.show', $application).'#messages')->with('status', 'Message sent to the applicant.');
    }
}
