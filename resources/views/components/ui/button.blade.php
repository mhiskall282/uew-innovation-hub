@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'iconRight' => null,
])
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold whitespace-nowrap transition-all duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50';

    $sizes = [
        'xs' => 'px-2.5 py-1.5 text-xs',
        'sm' => 'px-3 py-2 text-xs',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-5 py-3 text-sm',
    ];

    $variants = [
        'primary' => 'bg-gradient-to-r from-uew-navy to-uew-royal text-white shadow-sm shadow-uew-navy/20 hover:from-uew-navy-deep hover:to-uew-navy focus-visible:outline-uew-royal',
        'gold' => 'bg-uew-gold text-uew-ink shadow-sm hover:bg-uew-gold-light focus-visible:outline-uew-gold',
        'secondary' => 'bg-white text-slate-700 ring-1 ring-slate-300 ring-inset hover:bg-slate-50 hover:text-slate-900 focus-visible:outline-uew-royal',
        'ghost' => 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-uew-royal',
        'danger' => 'bg-red-600 text-white shadow-sm hover:bg-red-700 focus-visible:outline-red-600',
        'danger-ghost' => 'text-red-600 hover:bg-red-50 hover:text-red-700 focus-visible:outline-red-600',
        'light' => 'bg-white/10 text-white ring-1 ring-white/25 ring-inset backdrop-blur hover:bg-white/20 focus-visible:outline-white',
    ];

    $classes = $base . ' ' . ($sizes[$size] ?? $sizes['md']) . ' ' . ($variants[$variant] ?? $variants['primary']);
    $iconSize = in_array($size, ['xs', 'sm'], true) ? 'size-3.5' : 'size-4';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-ui.icon :name="$icon" :class="$iconSize" />@endif
        {{ $slot }}
        @if ($iconRight)<x-ui.icon :name="$iconRight" :class="$iconSize" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-ui.icon :name="$icon" :class="$iconSize" />@endif
        {{ $slot }}
        @if ($iconRight)<x-ui.icon :name="$iconRight" :class="$iconSize" />@endif
    </button>
@endif
