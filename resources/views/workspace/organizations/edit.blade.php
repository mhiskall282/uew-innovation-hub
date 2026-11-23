<x-layouts.workspace :title="'Edit ' . $organization->name">
    <x-ui.page-header title="Edit partner" :description="$organization->name" :back="route('workspace.organizations.index')" back-label="Partners" />

    <form method="POST" action="{{ route('workspace.organizations.update', $organization) }}">
        @csrf
        @method('PUT')
        @include('workspace.organizations._form')
    </form>

    @can('delete', $organization)
        <x-ui.card title="Remove partner" class="max-w-3xl">
            @if ($organization->projects_count > 0)
                <p class="text-sm text-slate-600">{{ $organization->name }} is linked to {{ $organization->projects_count }} {{ Str::plural('project', $organization->projects_count) }}. Remove it from those projects before deleting it.</p>
            @else
                <p class="text-sm text-slate-600">This partner is not linked to any project and can be removed.</p>
                <x-ui.confirm-form class="mt-4 block" :action="route('workspace.organizations.destroy', $organization)" :message="'Remove ' . $organization->name . '?'" label="Remove partner" variant="danger" />
            @endif
        </x-ui.card>
    @endcan
</x-layouts.workspace>
