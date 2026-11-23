<x-layouts.workspace title="New project">
    <x-ui.page-header title="New project" description="Register a project in the Hub portfolio." :back="route('workspace.projects.index')" back-label="Projects" />

    <form method="POST" action="{{ route('workspace.projects.store') }}" enctype="multipart/form-data">
        @csrf
        @include('workspace.projects._form')
    </form>
</x-layouts.workspace>
