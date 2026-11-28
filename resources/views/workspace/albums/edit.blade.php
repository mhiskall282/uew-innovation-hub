<x-layouts.workspace :title="$album->title">
    <x-ui.page-header :title="$album->title" :description="($album->event_date?->format('j F Y') ?? 'No date') . ' · ' . $album->photos->count() . ' ' . Str::plural('photo', $album->photos->count())"
        :back="route('workspace.albums.index')" back-label="Gallery">
        <x-slot:actions>
            <x-ui.badge :tone="$album->is_published ? 'green' : 'slate'" size="md" dot>{{ $album->is_published ? 'Published' : 'Draft' }}</x-ui.badge>
            @if ($album->is_published)
                <x-ui.button :href="route('site.gallery.show', $album)" variant="ghost" size="sm" icon="external" target="_blank" rel="noopener">View on site</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Upload --}}
    <x-ui.card title="Add photos" :description="'JPEG, PNG or WebP · up to ' . $maxFiles . ' at a time · ' . $maxMb . ' MB each'">
        <form method="POST" action="{{ route('workspace.albums.photos.store', $album) }}" enctype="multipart/form-data"
              x-data="{ files: [], busy: false, pick(e) { this.files = Array.from(e.target.files).map(f => f.name) } }" @submit="busy = true">
            @csrf
            <label for="photos" class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50/60 px-6 py-10 text-center transition hover:border-uew-royal hover:bg-blue-50/40">
                <x-ui.icon name="upload" class="size-8 text-uew-royal" />
                <span class="mt-3 text-sm font-semibold text-slate-900">Choose photos</span>
                <span class="mt-1 text-xs text-slate-500">They're resized for the web and location data from phones is removed.</span>
                <input id="photos" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple class="sr-only" @change="pick($event)">
            </label>
            <p class="mt-3 text-xs text-slate-600" x-show="files.length" x-cloak x-text="files.length + ' selected: ' + files.join(', ')"></p>
            @foreach (['photos', 'photos.*'] as $key)
                @foreach ($errors->get($key) as $messages)
                    <p class="mt-2 text-xs font-medium text-red-600">{{ is_array($messages) ? $messages[0] : $messages }}</p>
                @endforeach
            @endforeach
            <div class="mt-4 flex justify-end">
                <x-ui.button type="submit" icon="upload" ::disabled="busy || ! files.length">
                    <span x-show="! busy">Upload</span><span x-show="busy" x-cloak>Processing photos…</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Photos: captions, order, cover --}}
    @if ($album->photos->isNotEmpty())
        <form method="POST" action="{{ route('workspace.albums.photos.arrange', $album) }}" id="arrange">
            @csrf
            @method('PUT')
        </form>
        <x-ui.card title="Photos" description="Add captions, set the order (lower numbers first) and pick the cover.">
            <x-slot:actions>
                <x-ui.button type="submit" form="arrange" size="sm" icon="check">Save photos</x-ui.button>
            </x-slot:actions>
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($album->photos as $photo)
                    @php [$w, $h] = $photo->dimensions('thumb'); @endphp
                    <li class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                        <div class="relative aspect-[4/3] bg-slate-100">
                            @if ($photo->isAvailable())
                                <img src="{{ $photo->url('thumb') }}" alt="{{ $photo->altText() }}" width="{{ $w }}" height="{{ $h }}" loading="lazy" class="size-full object-cover">
                            @else
                                {{-- File lost (e.g. a redeploy on a host without persistent storage). Hidden on the public site. --}}
                                <div class="flex size-full flex-col items-center justify-center gap-1 p-3 text-center text-xs text-red-700">
                                    <x-ui.icon name="warning" class="size-6" />
                                    File missing. Delete and upload again.
                                </div>
                            @endif
                            @if ($album->cover_photo_id === $photo->id)
                                <span class="absolute top-2 left-2 rounded-full bg-uew-gold px-2 py-0.5 text-[10px] font-bold text-uew-ink">Cover</span>
                            @endif
                        </div>
                        <div class="space-y-2 p-3">
                            <label class="block">
                                <span class="sr-only">Caption</span>
                                <input type="text" form="arrange" name="photos[{{ $photo->id }}][caption]" value="{{ $photo->caption }}" maxlength="300" placeholder="Caption (optional)"
                                       class="block w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
                            </label>
                            <div class="flex items-center justify-between gap-2">
                                <label class="flex items-center gap-1.5 text-xs text-slate-600">
                                    Order
                                    <input type="number" form="arrange" name="photos[{{ $photo->id }}][sort_order]" value="{{ $photo->sort_order }}" min="0" max="10000"
                                           class="w-16 rounded-md border border-slate-300 px-2 py-1 font-mono text-xs focus:border-uew-royal focus:outline-none">
                                </label>
                                <label class="flex items-center gap-1.5 text-xs font-medium text-slate-700">
                                    <input type="radio" form="arrange" name="cover" value="{{ $photo->id }}" @checked($album->cover_photo_id === $photo->id) class="size-4 border-slate-300 text-uew-navy focus:ring-uew-royal"> Cover
                                </label>
                            </div>
                            <x-ui.confirm-form :action="route('workspace.albums.photos.destroy', [$album, $photo])" message="Delete this photo? This can't be undone." label="Delete" size="xs" />
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    {{-- Details --}}
    <form method="POST" action="{{ route('workspace.albums.update', $album) }}">
        @csrf
        @method('PUT')
        @include('workspace.albums._form')
    </form>

    @can('delete', $album)
        <x-ui.card title="Delete album">
            <p class="text-sm text-slate-600">Deletes the album and all {{ $album->photos->count() }} photos. This can't be undone.</p>
            <x-ui.confirm-form class="mt-4 block" :action="route('workspace.albums.destroy', $album)" :message="'Delete “' . $album->title . '” and all its photos?'" label="Delete album" variant="danger" />
        </x-ui.card>
    @endcan
</x-layouts.workspace>
