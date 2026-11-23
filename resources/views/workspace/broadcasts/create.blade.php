<x-layouts.workspace title="New broadcast">
    <x-ui.page-header title="New broadcast" description="E-mail an announcement to members who chose to receive them." :back="route('workspace.broadcasts.index')" back-label="Broadcasts" />
    <form method="POST" action="{{ route('workspace.broadcasts.store') }}">
        @csrf
        @include('workspace.broadcasts._form')
    </form>
</x-layouts.workspace>
