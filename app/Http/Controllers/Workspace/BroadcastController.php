<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Enums\Affiliation;
use App\Enums\BroadcastStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ProfileRequest;
use App\Mail\TemplatedMail;
use App\Models\Broadcast;
use App\Services\BroadcastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Announcement e-mails to members. Routes are gated by can:broadcasts.send. */
class BroadcastController extends Controller
{
    public function __construct(private readonly BroadcastService $broadcasts) {}

    public function index(): View
    {
        return view('workspace.broadcasts.index', [
            'broadcasts' => Broadcast::with('author:id,name', 'program:id,title')->latest()->paginate(20),
            'subscribers' => $this->broadcasts->count([]),
        ]);
    }

    public function create(): View
    {
        return view('workspace.broadcasts.create', $this->formData(new Broadcast([
            'subject' => '',
            'body' => 'Hello '.\App\Support\Format::placeholder('first_name').",\n\n",
            'audience' => ['interests' => [], 'affiliations' => []],
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $broadcast = new Broadcast($this->validated($request));
        $broadcast->created_by = $request->user()->id;
        $broadcast->save();

        if ($request->input('intent') === 'send') {
            $this->broadcasts->send($broadcast);

            return redirect()->route('workspace.broadcasts.index')->with('status', "Sending to {$broadcast->recipients_count} member(s).");
        }

        return redirect()->route('workspace.broadcasts.edit', $broadcast)->with('status', 'Draft saved.');
    }

    public function edit(Broadcast $broadcast): View|RedirectResponse
    {
        if ($broadcast->status !== BroadcastStatus::Draft) {
            return redirect()->route('workspace.broadcasts.show', $broadcast);
        }

        return view('workspace.broadcasts.edit', $this->formData($broadcast));
    }

    public function update(Request $request, Broadcast $broadcast): RedirectResponse
    {
        abort_unless($broadcast->status === BroadcastStatus::Draft, 422, 'Sent broadcasts cannot be changed.');

        $broadcast->fill($this->validated($request))->save();

        if ($request->input('intent') === 'send') {
            $this->broadcasts->send($broadcast);

            return redirect()->route('workspace.broadcasts.index')->with('status', "Sending to {$broadcast->recipients_count} member(s).");
        }

        return back()->with('status', 'Draft saved.');
    }

    public function show(Broadcast $broadcast): View
    {
        return view('workspace.broadcasts.show', [
            'broadcast' => $broadcast->load('author:id,name', 'program:id,title,slug'),
            'preview' => (new TemplatedMail('program_launched', ['name' => 'Ama Owusu', 'first_name' => 'Ama'], null, null, $broadcast->subject, $broadcast->body))->rendered(),
        ]);
    }

    public function destroy(Broadcast $broadcast): RedirectResponse
    {
        abort_unless($broadcast->status === BroadcastStatus::Draft, 422, 'Sent broadcasts are kept for the record.');
        $broadcast->delete();

        return redirect()->route('workspace.broadcasts.index')->with('status', 'Draft deleted.');
    }

    /** Live recipient count for the audience picker. */
    public function audience(Request $request): JsonResponse
    {
        return response()->json(['count' => $this->broadcasts->count([
            'interests' => array_values(array_intersect((array) $request->query('interests', []), ProfileRequest::interestOptions())),
            'affiliations' => array_values(array_intersect((array) $request->query('affiliations', []), array_keys(Affiliation::options()))),
        ])]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
            'interests' => ['nullable', 'array'],
            'interests.*' => ['string', Rule::in(ProfileRequest::interestOptions())],
            'affiliations' => ['nullable', 'array'],
            'affiliations.*' => ['string', Rule::in(array_keys(Affiliation::options()))],
            'intent' => ['required', 'in:draft,send'],
        ]);

        return [
            'subject' => $data['subject'],
            'body' => $data['body'],
            'audience' => ['interests' => array_values($data['interests'] ?? []), 'affiliations' => array_values($data['affiliations'] ?? [])],
        ];
    }

    /** @return array<string, mixed> */
    private function formData(Broadcast $broadcast): array
    {
        return [
            'broadcast' => $broadcast,
            'interests' => ProfileRequest::interestOptions(),
            'affiliations' => Affiliation::options(),
            'count' => $this->broadcasts->count($broadcast->audience ?? []),
        ];
    }
}
