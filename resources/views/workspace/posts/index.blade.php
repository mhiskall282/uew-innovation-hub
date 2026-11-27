<x-layouts.workspace title="News & posts">
    <x-ui.page-header title="News & posts" description="News, blog posts and announcements for the public site.">
        <x-slot:actions>
            @can('create', App\Models\Post::class)
                <x-ui.button :href="route('workspace.posts.create')" icon="plus">New post</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @cannot('content.publish')
        <x-ui.alert tone="info">You can write and edit your own drafts. An officer reviews and publishes them.</x-ui.alert>
    @endcannot

    <form method="GET" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-card sm:grid-cols-12" role="search">
        <label class="relative sm:col-span-6">
            <span class="sr-only">Search</span>
            <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-500" />
            <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search titles"
                   class="block w-full rounded-lg border border-slate-300 py-2 pr-3 pl-9 text-sm focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
        </label>
        <x-ui.select name="state" :options="['draft' => 'Drafts', 'scheduled' => 'Scheduled', 'published' => 'Published']" :value="$filters['state']" placeholder="Any state" wrap="sm:col-span-2" aria-label="State" />
        <x-ui.select name="type" :options="collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" :value="$filters['type']" placeholder="Any type" wrap="sm:col-span-2" aria-label="Type" />
        <x-ui.button type="submit" icon="filter" class="sm:col-span-2">Filter</x-ui.button>
    </form>

    <x-ui.card padding="p-0">
        @if ($posts->isEmpty())
            <x-ui.empty title="No posts yet" icon="newspaper">
                <x-slot:action><x-ui.button :href="route('workspace.posts.create')" icon="plus" size="sm">Write the first post</x-ui.button></x-slot:action>
            </x-ui.empty>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($posts as $post)
                    <li class="flex flex-wrap items-center gap-4 px-5 py-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge :tone="$post->stateTone()" dot>{{ ucfirst($post->state()) }}</x-ui.badge>
                                <x-ui.badge :tone="$post->type->tone()">{{ $post->type->label() }}</x-ui.badge>
                                @if ($post->is_featured)<x-ui.badge tone="gold">Featured</x-ui.badge>@endif
                            </div>
                            <p class="mt-1.5 truncate font-semibold text-slate-900">{{ $post->title }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $post->author?->name ?? 'Unknown author' }} · {{ $post->category }} ·
                                @if ($post->published_at) {{ $post->state() === 'scheduled' ? 'Goes live' : 'Published' }} {{ $post->published_at->format('j M Y, H:i') }} @else Edited {{ $post->updated_at->diffForHumans() }} @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-1">
                            @if ($post->isPublished())
                                <x-ui.button :href="route('site.news.show', $post)" variant="ghost" size="xs" icon="external" target="_blank" rel="noopener">View</x-ui.button>
                            @endif
                            @can('update', $post)
                                <x-ui.button :href="route('workspace.posts.edit', $post)" variant="ghost" size="xs" icon="pencil">Edit</x-ui.button>
                            @endcan
                            @can('delete', $post)
                                <x-ui.confirm-form :action="route('workspace.posts.destroy', $post)" :message="'Delete ' . $post->title . '?'" label="Delete" size="xs" />
                            @endcan
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="border-t border-slate-100 px-5 py-3">{{ $posts->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.workspace>
