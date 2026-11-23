<x-layouts.workspace :title="'Edit ' . $project->title">
    <x-ui.page-header :title="'Edit project'" :description="$project->title" :back="route('workspace.projects.show', $project)" back-label="Back to project" />

    <form method="POST" action="{{ route('workspace.projects.update', $project) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('workspace.projects._form')
    </form>
</x-layouts.workspace>
