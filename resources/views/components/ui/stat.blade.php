@props(['label', 'value', 'hint' => null, 'icon' => 'chart', 'tone' => 'navy', 'href' => null])
@php
    $tones = [
        'navy' => 'bg-uew-navy/10 text-uew-navy',
        'royal' => 'bg-blue-50 text-uew-royal',
        'gold' => 'bg-amber-50 text-uew-gold-dark',
        'green' => 'bg-emerald-50 text-emerald-600',
        'purple' => 'bg-violet-50 text-uew-purple',
    ];
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'group relative block overflow-hidden rounded-xl border border-slate-200/80 bg-white p-5 shadow-card transition-shadow' . ($href ? ' hover:shadow-card-hover' : '')]) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">{{ $label }}</p>
        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $tones[$tone] ?? $tones['navy'] }}">
            <x-ui.icon :name="$icon" class="size-[18px]" />
        </span>
    </div>
    <p class="mt-2 font-mono text-2xl font-bold tracking-tight text-slate-900 sm:text-[28px]">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    {{ $slot }}
</{{ $tag }}>
