<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\PublicationRequest;
use App\Models\Project;
use App\Models\Publication;
use App\Services\PublicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PublicationController extends Controller
{
    public function __construct(private readonly PublicationService $publications) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Publication::class);

        $type = in_array($request->query('type'), config('hub.publication_types'), true) ? $request->query('type') : null;

        return view('workspace.publications.index', [
            'publications' => Publication::query()
                ->with('project:id,title,slug')
                ->when($type, fn ($q) => $q->where('type', $type))
                ->search($request->query('search'), ['title', 'authors', 'journal_name'])
                ->latest('publish_date')
                ->paginate(15)
                ->withQueryString(),
            'types' => config('hub.publication_types'),
            'filters' => ['type' => $type, 'search' => $request->query('search')],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Publication::class);

        return view('workspace.publications.create', $this->formData(new Publication(['type' => 'Research Paper'])));
    }

    public function store(PublicationRequest $request): RedirectResponse
    {
        Gate::authorize('create', Publication::class);

        $this->publications->create($request->validated());

        return redirect()->route('workspace.publications.index')->with('status', 'Publication added.');
    }

    public function edit(Publication $publication): View
    {
        Gate::authorize('update', $publication);

        return view('workspace.publications.edit', $this->formData($publication));
    }

    public function update(PublicationRequest $request, Publication $publication): RedirectResponse
    {
        Gate::authorize('update', $publication);

        $this->publications->update($publication, $request->validated());

        return redirect()->route('workspace.publications.index')->with('status', 'Publication updated.');
    }

    public function destroy(Publication $publication): RedirectResponse
    {
        Gate::authorize('delete', $publication);

        $this->publications->delete($publication);

        return redirect()->route('workspace.publications.index')->with('status', "\"{$publication->title}\" was removed.");
    }

    /** @return array<string, mixed> */
    private function formData(Publication $publication): array
    {
        return [
            'publication' => $publication,
            'types' => config('hub.publication_types'),
            'projects' => Project::orderBy('title')->get(['id', 'title']),
        ];
    }
}
