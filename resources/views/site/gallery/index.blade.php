<x-layouts.site title="Gallery" description="Photos from UEW Innovation Hub events: pitch days, bootcamps, workshops and community projects.">
    <x-ui.page-hero eyebrow="Gallery" title="Moments from the Hub" photo="monument">
        Pitch days, bootcamps, workshops and community projects from the UEW Innovation Hub.
    </x-ui.page-hero>

    <section class="mx-auto max-w-7xl px-6 py-12">
        @if ($albums->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 p-12 text-center">
                <x-ui.icon name="photo" class="mx-auto size-10 text-slate-400" />
                <p class="mt-3 font-semibold text-slate-900">No albums yet</p>
                <p class="mt-1 text-sm text-slate-600">Photos from our events will appear here.</p>
            </div>
        @else
            <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($albums as $album)
                    @php [$w, $h] = $album->cover->dimensions('thumb'); @endphp
                    <li>
                        <a href="{{ route('site.gallery.show', $album) }}" class="group block overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card transition hover:-translate-y-0.5 hover:shadow-card-hover">
                            <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
                                <img src="{{ $album->cover->url('thumb') }}" alt="" width="{{ $w }}" height="{{ $h }}" loading="lazy" decoding="async"
                                     class="size-full object-cover transition duration-500 group-hover:scale-105">
                                <span class="absolute right-3 bottom-3 inline-flex items-center gap-1 rounded-full bg-uew-ink/80 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur">
                                    <x-ui.icon name="photo" class="size-3.5" /> {{ $album->photos_count }}
                                </span>
                            </div>
                            <div class="p-5">
                                <p class="font-mono text-[11px] font-semibold tracking-wider text-uew-gold-dark uppercase">{{ $album->event_date?->format('j M Y') ?? $album->published_at?->format('M Y') }}</p>
                                <h2 class="mt-1 text-lg font-bold text-slate-900 group-hover:text-uew-royal">{{ $album->title }}</h2>
                                @if ($album->summary)<p class="mt-1 line-clamp-2 text-sm text-slate-600">{{ $album->summary }}</p>@endif
                                @if ($album->location)<p class="mt-3 inline-flex items-center gap-1 text-xs text-slate-500"><x-ui.icon name="map-pin" class="size-3.5" /> {{ $album->location }}</p>@endif
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-10">{{ $albums->links() }}</div>
        @endif
    </section>
</x-layouts.site>
