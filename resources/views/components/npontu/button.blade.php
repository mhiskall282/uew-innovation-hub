@props([
    'variant' => 'primary', // primary, secondary, danger, gold, ghost
    'size' => 'md', // sm, md, lg
    'type' => 'button',
    'href' => null,
    'icon' => null,
])

@php
$baseClasses = "inline-flex items-center justify-center font-medium transition-all duration-150 focus:outline-hidden focus:ring-2 focus:ring-offset-1 select-none active:scale-[0.98]";

$sizeClasses = match($size) {
    'sm' => "text-xs px-2.5 py-1.5 rounded-md gap-1.5",
    'lg' => "text-sm px-5 py-2.5 rounded-lg gap-2.5 font-semibold",
    default => "text-xs uppercase tracking-wider font-semibold px-4 py-2 rounded-lg gap-2",
};

$variantClasses = match($variant) {
    'primary' => "bg-gradient-to-r from-[#102a72] to-[#3157d5] hover:from-[#0a1b4d] hover:to-[#1e3a8a] text-white shadow-sm shadow-blue-900/25 border border-blue-900/30 focus:ring-[#3157d5]",
    'secondary' => "bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 shadow-2xs focus:ring-slate-400 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-750",
    'gold' => "bg-gradient-to-r from-[#f59e0b] to-[#fbbf24] hover:from-[#d97706] hover:to-[#f59e0b] text-slate-950 font-bold shadow-sm shadow-amber-500/20 border border-amber-500/30 focus:ring-amber-400",
    'danger' => "bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 focus:ring-rose-400 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900/50",
    'ghost' => "bg-transparent hover:bg-slate-100 text-slate-600 hover:text-slate-900 focus:ring-slate-300 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100",
    default => "bg-gradient-to-r from-[#102a72] to-[#3157d5] text-white",
};

$classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
