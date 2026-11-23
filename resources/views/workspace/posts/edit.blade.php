<x-layouts.workspace :title="'Edit ' . $post->title">
    <x-ui.page-header title="Edit post" :description="$post->title" :back="route('workspace.posts.index')" back-label="News & posts">
        <x-slot:actions>
            <x-ui.badge :tone="$post->stateTone()" size="md" dot>{{ ucfirst($post->state()) }}</x-ui.badge>
            @if ($post->isPublished())
                <x-ui.button :href="route('site.news.show', $post)" variant="secondary" size="sm" icon="external" target="_blank" rel="noopener">View live</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <form method="POST" action="{{ route('workspace.posts.update', $post) }}">
        @csrf
        @method('PUT')
        @include('workspace.posts._form')
    </form>

    @can('delete', $post)
        <div class="flex justify-end">
            <x-ui.confirm-form :action="route('workspace.posts.destroy', $post)" :message="'Delete ' . $post->title . '?'" label="Delete post" />
        </div>
    @endcan
</x-layouts.workspace>
