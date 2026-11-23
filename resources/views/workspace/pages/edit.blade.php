<x-layouts.workspace :title="'Edit ' . $page->title">
    <x-ui.page-header title="Edit page" :description="$page->title" :back="route('workspace.pages.index')" back-label="Pages">
        <x-slot:actions>
            @if ($page->is_published)
                <x-ui.button :href="route('site.page', $page)" variant="secondary" size="sm" icon="external" target="_blank" rel="noopener">View live</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>
    <form method="POST" action="{{ route('workspace.pages.update', $page) }}">
        @csrf
        @method('PUT')
        @include('workspace.pages._form')
    </form>
    <div class="flex justify-end">
        <x-ui.confirm-form :action="route('workspace.pages.destroy', $page)" :message="'Delete the page ' . $page->title . '?'" label="Delete page" />
    </div>
</x-layouts.workspace>
