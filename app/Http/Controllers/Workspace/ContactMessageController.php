<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Enums\ContactStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\ContactMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Inbox for the public contact form. Routes are gated by can:messages.manage. */
class ContactMessageController extends Controller
{
    public function __construct(private readonly ContactMessageService $messages) {}

    public function index(Request $request): View
    {
        $status = ContactStatus::tryFrom((string) $request->query('status'));

        $counts = ContactMessage::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('workspace.messages.index', [
            'enquiries' => ContactMessage::query()
                ->with('handler:id,name')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when(! $status, fn ($q) => $q->where('status', '!=', ContactStatus::Archived))
                ->search($request->query('search'), ['name', 'email', 'subject', 'message'])
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'statuses' => ContactStatus::cases(),
            'activeStatus' => $status,
            'counts' => $counts,
            'search' => $request->query('search'),
        ]);
    }

    public function show(Request $request, ContactMessage $message): View
    {
        $this->messages->markRead($message, $request->user());
        $message->load('handler:id,name');

        return view('workspace.messages.show', [
            'enquiry' => $message,
            'statuses' => ContactStatus::cases(),
        ]);
    }

    public function update(Request $request, ContactMessage $message): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(ContactStatus::class)]]);

        $this->messages->setStatus($message, ContactStatus::from($data['status']), $request->user());

        return back()->with('status', "Message marked as {$message->status->label()}.");
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $this->messages->delete($message);

        return redirect()->route('workspace.messages.index')->with('status', 'Message deleted.');
    }
}
