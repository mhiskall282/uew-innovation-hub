<x-layouts.workspace :title="'Edit ' . $user->name">
    <x-ui.page-header :title="$user->name" :description="$user->email" :back="route('workspace.users.index')" back-label="Staff accounts">
        <x-slot:actions>
            <x-ui.badge :tone="$user->is_active ? 'green' : 'slate'" size="md" dot>{{ $user->is_active ? 'Active' : 'Deactivated' }}</x-ui.badge>
        </x-slot:actions>
    </x-ui.page-header>

    <form method="POST" action="{{ route('workspace.users.update', $user) }}">
        @csrf
        @method('PUT')
        @include('workspace.users._form')
    </form>

    @can('delete', $user)
        <x-ui.card title="Delete account">
            <p class="text-sm text-slate-600">Deleting removes this person's sign-in permanently. Their past actions stay in the audit log. To pause access instead, deactivate the account from the staff list.</p>
            <x-ui.confirm-form class="mt-4 block" :action="route('workspace.users.destroy', $user)" :message="'Permanently delete ' . $user->name . '\'s account?'" label="Delete account" variant="danger" />
        </x-ui.card>
    @endcan
</x-layouts.workspace>
