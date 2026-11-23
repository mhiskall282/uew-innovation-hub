<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\OrganizationRequest;
use App\Models\Organization;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Organization::class);

        $type = in_array($request->query('type'), config('hub.organization_types'), true) ? $request->query('type') : null;

        return view('workspace.organizations.index', [
            'organizations' => Organization::query()
                ->withCount('projects')
                ->when($type, fn ($q) => $q->where('type', $type))
                ->search($request->query('search'), ['name', 'type', 'contact_email'])
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'types' => config('hub.organization_types'),
            'filters' => ['type' => $type, 'search' => $request->query('search')],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Organization::class);

        return view('workspace.organizations.create', [
            'organization' => new Organization(['type' => 'Partner']),
            'types' => config('hub.organization_types'),
        ]);
    }

    public function store(OrganizationRequest $request): RedirectResponse
    {
        Gate::authorize('create', Organization::class);

        $organization = Organization::create($request->validated());
        $this->audit->record('organization.created', "Added partner \"{$organization->name}\"", $organization);

        return redirect()->route('workspace.organizations.index')->with('status', "{$organization->name} was added.");
    }

    public function edit(Organization $organization): View
    {
        Gate::authorize('update', $organization);

        $organization->loadCount('projects');

        return view('workspace.organizations.edit', [
            'organization' => $organization,
            'types' => config('hub.organization_types'),
        ]);
    }

    public function update(OrganizationRequest $request, Organization $organization): RedirectResponse
    {
        Gate::authorize('update', $organization);

        $organization->fill($request->validated());
        $changes = $this->audit->diff($organization);
        $organization->save();
        $this->audit->record('organization.updated', "Updated partner \"{$organization->name}\"", $organization, $changes);

        return redirect()->route('workspace.organizations.index')->with('status', "{$organization->name} was updated.");
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        Gate::authorize('delete', $organization);

        if ($organization->projects()->exists()) {
            return back()->withErrors([
                'organization' => "{$organization->name} is linked to projects. Remove it from those projects first.",
            ]);
        }

        $organization->delete();
        $this->audit->record('organization.deleted', "Removed partner \"{$organization->name}\"", $organization);

        return redirect()->route('workspace.organizations.index')->with('status', "{$organization->name} was removed.");
    }
}
