@props(['eyebrow' => null, 'title', 'photo' => 'monument', 'size' => 'md'])
{{-- Public page header: UEW campus photograph under a navy overlay. The slot holds the intro text, filters or actions. --}}
@php
    $p = \App\Support\Photos::get($photo);
    $pad = $size === 'lg' ? 'py-20 lg:py-28' : 'py-14 lg:py-16';
@endphp
<section {{ $attributes->merge(['class' => 'relative isolate overflow-hidden bg-uew-ink text-white']) }}>
    <img src="{{ asset($p['src']) }}" alt="" aria-hidden="true" loading="eager" decoding="async"
         class="absolute inset-0 -z-20 size-full object-cover object-center opacity-60">
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-uew-ink via-uew-ink/90 to-uew-navy/60"></div>
    <div class="absolute inset-x-0 bottom-0 -z-10 h-1 bg-gradient-to-r from-uew-gold via-uew-gold-light to-uew-red"></div>
    <div class="relative mx-auto max-w-7xl px-6 {{ $pad }}">
        @if ($eyebrow)
            <p class="inline-flex items-center gap-2 font-mono text-[11px] font-bold tracking-[0.2em] text-uew-gold-light uppercase">
                <span class="h-px w-6 bg-uew-gold-light"></span>{{ $eyebrow }}
            </p>
        @endif
        <h1 class="mt-3 max-w-3xl text-3xl font-extrabold tracking-tight text-balance sm:text-5xl">{{ $title }}</h1>
        <div class="mt-4 max-w-2xl text-base leading-relaxed text-blue-50/85 [&_a]:text-uew-gold-light">
            {{ $slot }}
        </div>
        @isset($below)
            <div class="mt-8">{{ $below }}</div>
        @endisset
    </div>
    <p class="absolute right-3 bottom-2.5 text-[10px] text-white/45">
        <a href="{{ $p['source'] }}" target="_blank" rel="noopener" class="hover:text-white/80">{{ \App\Support\Photos::credit($photo) }}</a>
    </p>
</section>
