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

    @if ($user->is_active && ! auth()->user()->is($user))
        <x-ui.card title="Help them sign in" description="You never need to know or set someone's password.">
            <p class="text-sm text-slate-600">Send {{ $user->firstName() }} an e-mail with a secure link and a 6-digit code to choose a new password. It expires after 10 minutes and works once. Their other devices are signed out once the password changes.</p>
            <x-ui.confirm-form class="mt-4 block" :action="route('workspace.users.password-reset', $user)" method="POST"
                :message="'Send a password-reset e-mail to ' . $user->email . '?'" label="Send password-reset e-mail" variant="secondary" icon="mail" />
        </x-ui.card>
    @endif

    @can('delete', $user)
        <x-ui.card title="Delete account">
            <p class="text-sm text-slate-600">Deleting removes this person's sign-in permanently. Their past actions stay in the audit log. To pause access instead, deactivate the account from the staff list.</p>
            <x-ui.confirm-form class="mt-4 block" :action="route('workspace.users.destroy', $user)" :message="'Permanently delete ' . $user->name . '\'s account?'" label="Delete account" variant="danger" />
        </x-ui.card>
    @endcan
</x-layouts.workspace>
