<x-layouts.workspace :title="'Edit ' . $program->title">
    <x-ui.page-header title="Edit programme" :description="$program->title" :back="route('workspace.programs.show', $program)" back-label="Back to programme">
        <x-slot:actions>
            @if ($program->isVisible())
                <x-ui.button :href="route('site.programs.show', $program)" variant="secondary" size="sm" icon="external" target="_blank" rel="noopener">View public page</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <form method="POST" action="{{ route('workspace.programs.update', $program) }}">
        @csrf
        @method('PUT')
        @include('workspace.programs._form')
    </form>

    @unless ($hasApplications)
        <div class="flex justify-end">
            <x-ui.confirm-form :action="route('workspace.programs.destroy', $program)" :message="'Delete ' . $program->title . '? This cannot be undone.'" label="Delete programme" />
        </div>
    @endunless
</x-layouts.workspace>
