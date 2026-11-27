<x-layouts.workspace title="New album">
    <x-ui.page-header title="New album" description="Start with the event details. You'll add photos on the next screen." :back="route('workspace.albums.index')" back-label="Gallery" />
    <form method="POST" action="{{ route('workspace.albums.store') }}">
        @csrf
        @include('workspace.albums._form')
    </form>
</x-layouts.workspace>
