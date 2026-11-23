@props([
    'title' => null,
    'subtitle' => null,
    'action' => null,
    'padding' => 'p-6',
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-slate-850 rounded-xl border border-slate-200/90 dark:border-slate-800 shadow-xs overflow-hidden']) }}>
    @if($title || $subtitle || $action)
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-900/30">
            <div>
                @if($title)
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">{{ $title }}</h3>
                @endif
                @if($subtitle)
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            @if($action)
                <div>
                    {{ $action }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
</div>
