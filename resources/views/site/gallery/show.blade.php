<x-layouts.site :title="$album->title" :description="$album->summary ?? ('Photos from ' . $album->title . ' at the UEW Innovation Hub.')">
    @php
        $photos = $album->photos->map(fn ($p) => [
            'large' => $p->url('large'),
            'caption' => $p->caption,
            'alt' => $p->altText(),
        ])->values();
    @endphp

    <section class="border-b border-slate-200 bg-uew-canvas">
        <div class="mx-auto max-w-7xl px-6 py-10">
            <a href="{{ route('site.gallery.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-uew-navy"><x-ui.icon name="arrow-left" class="size-3.5" /> Gallery</a>
            <p class="mt-4 font-mono text-[11px] font-bold tracking-widest text-uew-gold-dark uppercase">
                {{ $album->event_date?->format('j F Y') }}@if ($album->location) · {{ $album->location }}@endif
            </p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ $album->title }}</h1>
            @if ($album->summary)<p class="mt-3 max-w-3xl text-slate-600">{{ $album->summary }}</p>@endif
            @if ($album->program?->isVisible())
                <a href="{{ route('site.programs.show', $album->program) }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-uew-royal hover:underline"><x-ui.icon name="sparkles" class="size-4" /> {{ $album->program->title }}</a>
            @endif
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-6 py-10"
             x-data="{
                photos: @js($photos),
                open: false, index: 0, opener: null,
                show(i, el) { this.index = i; this.opener = el; this.open = true; this.$nextTick(() => this.$refs.close.focus()); document.body.style.overflow = 'hidden'; },
                hide() { this.open = false; document.body.style.overflow = ''; this.opener?.focus(); },
                next() { this.index = (this.index + 1) % this.photos.length },
                prev() { this.index = (this.index - 1 + this.photos.length) % this.photos.length },
             }"
             @keydown.escape.window="open && hide()" @keydown.arrow-right.window="open && next()" @keydown.arrow-left.window="open && prev()">

        @if ($album->description)
            <div class="prose-uew mb-10 max-w-3xl whitespace-pre-line">{{ $album->description }}</div>
        @endif

        <h2 class="sr-only">Photos</h2>
        <ul class="columns-2 gap-4 sm:columns-3 lg:columns-4">
            @foreach ($album->photos as $i => $photo)
                @php [$w, $h] = $photo->dimensions('thumb'); @endphp
                <li class="mb-4 break-inside-avoid">
                    <button type="button" @click="show({{ $i }}, $el)" class="group block w-full overflow-hidden rounded-xl bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-uew-royal"
                            aria-label="Open photo {{ $i + 1 }} of {{ $album->photos->count() }}{{ $photo->caption ? ': ' . $photo->caption : '' }}">
                        <img src="{{ $photo->url('thumb') }}" alt="{{ $photo->altText() }}" width="{{ $w }}" height="{{ $h }}" loading="lazy" decoding="async"
                             class="h-auto w-full transition duration-300 group-hover:scale-[1.03] group-hover:opacity-95">
                    </button>
                    @if ($photo->caption)<p class="mt-1.5 text-xs text-slate-600">{{ $photo->caption }}</p>@endif
                </li>
            @endforeach
        </ul>

        {{-- Lightbox --}}
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-uew-ink/95 p-4"
             role="dialog" aria-modal="true" aria-label="Photo viewer" @click.self="hide()">
            <button type="button" x-ref="close" @click="hide()" class="absolute top-4 right-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-uew-gold">
                <x-ui.icon name="x" class="size-6" /><span class="sr-only">Close</span>
            </button>
            <button type="button" @click="prev()" class="absolute left-3 rounded-full bg-white/10 p-3 text-white hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-uew-gold" x-show="photos.length > 1">
                <x-ui.icon name="chevron-left" class="size-6" /><span class="sr-only">Previous photo</span>
            </button>
            <figure class="max-h-full max-w-5xl">
                <img :src="photos[index]?.large" :alt="photos[index]?.alt" class="mx-auto max-h-[80vh] w-auto rounded-lg shadow-2xl">
                <figcaption class="mt-3 text-center text-sm text-blue-50/90">
                    <span x-text="photos[index]?.caption"></span>
                    <span class="ml-2 font-mono text-xs text-blue-100/60" x-text="(index + 1) + ' / ' + photos.length" aria-live="polite"></span>
                </figcaption>
            </figure>
            <button type="button" @click="next()" class="absolute right-3 rounded-full bg-white/10 p-3 text-white hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-uew-gold" x-show="photos.length > 1">
                <x-ui.icon name="chevron-right" class="size-6" /><span class="sr-only">Next photo</span>
            </button>
        </div>
    </section>

    @if ($more->isNotEmpty())
        <section class="border-t border-slate-200 bg-uew-canvas py-12">
            <div class="mx-auto max-w-7xl px-6">
                <h2 class="text-xs font-bold tracking-widest text-slate-500 uppercase">More albums</h2>
                <ul class="mt-4 grid gap-5 sm:grid-cols-3">
                    @foreach ($more as $other)
                        <li>
                            <a href="{{ route('site.gallery.show', $other) }}" class="group block overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card">
                                <img src="{{ $other->cover->url('thumb') }}" alt="" loading="lazy" class="aspect-[4/3] w-full object-cover">
                                <span class="block p-4 font-semibold text-slate-900 group-hover:text-uew-royal">{{ $other->title }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</x-layouts.site>
