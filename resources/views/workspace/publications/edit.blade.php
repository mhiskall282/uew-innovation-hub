<x-layouts.workspace :title="'Edit ' . $publication->title">
    <x-ui.page-header title="Edit publication" :description="$publication->title" :back="route('workspace.publications.index')" back-label="Publications" />

    <form method="POST" action="{{ route('workspace.publications.update', $publication) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('workspace.publications._form')
    </form>

    <div class="flex justify-end">
        <x-ui.confirm-form :action="route('workspace.publications.destroy', $publication)" :message="'Remove ' . $publication->title . ' from the library?'" label="Remove publication" />
    </div>
</x-layouts.workspace>
