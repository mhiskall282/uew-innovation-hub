@props(['title' => null, 'description' => null, 'padding' => 'p-5'])
<section {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200/80 bg-white shadow-card']) }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div class="min-w-0">
                @if ($title)<h2 class="text-sm font-bold text-uew-navy">{{ $title }}</h2>@endif
                @if ($description)<p class="mt-0.5 text-xs text-slate-500">{{ $description }}</p>@endif
            </div>
            @isset($actions)
                {{-- Wraps under the title on narrow screens instead of overflowing. --}}
                <div class="flex max-w-full flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif
    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
    @isset($footer)
        <footer class="rounded-b-xl border-t border-slate-100 bg-slate-50/60 px-5 py-3">{{ $footer }}</footer>
    @endisset
</section>
