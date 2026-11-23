<x-layouts.workspace title="Edit broadcast">
    <x-ui.page-header title="Edit draft broadcast" :description="$broadcast->subject" :back="route('workspace.broadcasts.index')" back-label="Broadcasts" />
    <form method="POST" action="{{ route('workspace.broadcasts.update', $broadcast) }}">
        @csrf
        @method('PUT')
        @include('workspace.broadcasts._form')
    </form>
    <div class="flex justify-end">
        <x-ui.confirm-form :action="route('workspace.broadcasts.destroy', $broadcast)" message="Delete this draft?" label="Delete draft" />
    </div>
</x-layouts.workspace>
