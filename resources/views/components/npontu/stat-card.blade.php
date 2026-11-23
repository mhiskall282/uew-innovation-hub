@props([
    'title',
    'value',
    'subtext' => null,
    'icon' => null,
    'trend' => null,
    'trendType' => 'up', // up, down, neutral
    'accentColor' => 'blue', // blue, gold, purple, emerald
])

@php
$accentBorder = match($accentColor) {
    'gold' => 'border-t-4 border-t-[#f59e0b]',
    'purple' => 'border-t-4 border-t-[#6d28d9]',
    'emerald' => 'border-t-4 border-t-emerald-500',
    default => 'border-t-4 border-t-[#3157d5]',
};

$iconBg = match($accentColor) {
    'gold' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400',
    'purple' => 'bg-purple-50 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400',
    'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400',
    default => 'bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400',
};
@endphp

<div class="relative bg-white dark:bg-slate-850 rounded-xl p-5 border border-slate-200/90 dark:border-slate-800 shadow-xs hover:shadow-md transition-all duration-200 {{ $accentBorder }}">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $title }}</p>
            <h3 class="text-2xl font-bold font-mono text-slate-900 dark:text-white mt-1.5 tracking-tight">{{ $value }}</h3>
        </div>
        @if($icon)
            <div class="w-11 h-11 rounded-lg flex items-center justify-center {{ $iconBg }} shadow-2xs">
                {!! $icon !!}
            </div>
        @endif
    </div>

    @if($subtext || $trend)
        <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            @if($subtext)
                <span>{{ $subtext }}</span>
            @endif

            @if($trend)
                <span class="inline-flex items-center font-mono font-medium {{ $trendType === 'up' ? 'text-emerald-600 dark:text-emerald-400' : ($trendType === 'down' ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500') }}">
                    @if($trendType === 'up')
                        <svg class="w-3.5 h-3.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    @elseif($trendType === 'down')
                        <svg class="w-3.5 h-3.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>
                    @endif
                    {{ $trend }}
                </span>
            @endif
        </div>
    @endif
</div>
