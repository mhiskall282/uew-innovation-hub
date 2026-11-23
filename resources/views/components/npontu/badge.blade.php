@props([
    'variant' => 'neutral', // active, completed, suspended, pending, info, gold, purple, neutral
    'pulse' => false,
])

@php
$classes = match($variant) {
    'active', 'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800',
    'completed', 'blue' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800',
    'suspended', 'danger' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800',
    'pending', 'warning' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800',
    'purple' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-800',
    'gold' => 'bg-amber-100/70 text-amber-900 border-amber-300 dark:bg-amber-950/70 dark:text-amber-200 dark:border-amber-700',
    default => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
};

$dotColor = match($variant) {
    'active', 'success' => 'bg-emerald-500',
    'completed', 'blue' => 'bg-blue-500',
    'suspended', 'danger' => 'bg-rose-500',
    'pending', 'warning' => 'bg-amber-500',
    'purple' => 'bg-purple-500',
    'gold' => 'bg-amber-500',
    default => 'bg-slate-400',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border {$classes}"]) }}>
    <span class="relative flex h-1.5 w-1.5">
        @if($pulse)
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $dotColor }} opacity-75"></span>
        @endif
        <span class="relative inline-flex rounded-full h-1.5 w-1.5 {{ $dotColor }}"></span>
    </span>
    {{ $slot }}
</span>
