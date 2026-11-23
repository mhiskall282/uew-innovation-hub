<x-layouts.workspace title="New programme">
    <x-ui.page-header title="Launch a programme" description="Create a grant call, fellowship, incubation cohort, competition, training or event, with its own application form." :back="route('workspace.programs.index')" back-label="Programmes" />

    <form method="POST" action="{{ route('workspace.programs.store') }}">
        @csrf
        @include('workspace.programs._form')
    </form>
</x-layouts.workspace>
