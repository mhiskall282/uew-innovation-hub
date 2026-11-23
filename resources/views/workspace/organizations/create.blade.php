<x-layouts.workspace title="Add partner">
    <x-ui.page-header title="Add partner" :back="route('workspace.organizations.index')" back-label="Partners" />

    <form method="POST" action="{{ route('workspace.organizations.store') }}">
        @csrf
        @include('workspace.organizations._form')
    </form>
</x-layouts.workspace>
