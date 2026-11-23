<x-layouts.workspace title="Add publication">
    <x-ui.page-header title="Add publication" :back="route('workspace.publications.index')" back-label="Publications" />

    <form method="POST" action="{{ route('workspace.publications.store') }}" enctype="multipart/form-data">
        @csrf
        @include('workspace.publications._form')
    </form>
</x-layouts.workspace>
