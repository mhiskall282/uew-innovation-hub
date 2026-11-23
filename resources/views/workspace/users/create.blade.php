<x-layouts.workspace title="Add staff member">
    <x-ui.page-header title="Add staff member" :back="route('workspace.users.index')" back-label="Staff accounts" />

    <form method="POST" action="{{ route('workspace.users.store') }}">
        @csrf
        @include('workspace.users._form')
    </form>
</x-layouts.workspace>
