<x-layouts.workspace title="Gallery">
    <x-ui.page-header title="Gallery" description="Photo albums from Hub events. Published albums appear on the website gallery and the homepage.">
        <x-slot:actions>
            @can('create', \App\Models\Album::class)
                <x-ui.button :href="route('workspace.albums.create')" icon="plus">New album</x-ui.button>
            @endcan
            <x-ui.button :href="route('site.gallery.index')" variant="ghost" icon="external" target="_blank" rel="noopener">View gallery</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($albums->isEmpty())
        <x-ui.card>
            <x-ui.empty title="No albums yet" icon="photo">
                Create an album for an event, then upload its photos.
                <x-slot:action><x-ui.button :href="route('workspace.albums.create')" size="sm" icon="plus">New album</x-ui.button></x-slot:action>
            </x-ui.empty>
        </x-ui.card>
    @else
        <ul class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($albums as $album)
                <li class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-card">
                    <a href="{{ route('workspace.albums.edit', $album) }}" class="group block">
                        <div class="aspect-[4/3] bg-slate-100">
                            @if ($album->cover)
                                <img src="{{ $album->cover->url('thumb') }}" alt="" loading="lazy" class="size-full object-cover transition group-hover:opacity-90">
                            @else
                                <div class="flex size-full items-center justify-center text-slate-400"><x-ui.icon name="photo" class="size-10" /></div>
                            @endif
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate font-semibold text-slate-900 group-hover:text-uew-royal">{{ $album->title }}</p>
                                <x-ui.badge :tone="$album->is_published ? 'green' : 'slate'" size="xs">{{ $album->is_published ? 'Published' : 'Draft' }}</x-ui.badge>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $album->event_date?->format('j M Y') ?? 'No date' }} · {{ $album->photos_count }} {{ Str::plural('photo', $album->photos_count) }}@if ($album->is_featured) · <span class="font-semibold text-uew-gold-dark">Featured</span>@endif</p>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
        {{ $albums->links() }}
    @endif
</x-layouts.workspace>
