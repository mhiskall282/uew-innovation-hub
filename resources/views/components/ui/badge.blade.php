@props(['tone' => 'slate', 'dot' => false, 'size' => 'sm'])
@php
    $tones = [
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'blue' => 'bg-blue-50 text-uew-royal ring-blue-200',
        'navy' => 'bg-uew-navy/10 text-uew-navy ring-uew-navy/20',
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'gold' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'red' => 'bg-red-50 text-red-700 ring-red-200',
        'purple' => 'bg-violet-50 text-uew-purple ring-violet-200',
    ];
    $dots = [
        'slate' => 'bg-slate-400', 'blue' => 'bg-uew-royal', 'navy' => 'bg-uew-navy', 'green' => 'bg-emerald-500',
        'gold' => 'bg-uew-gold', 'red' => 'bg-red-500', 'purple' => 'bg-uew-purple',
    ];
    $sizes = ['xs' => 'px-1.5 py-0.5 text-[10px]', 'sm' => 'px-2 py-0.5 text-[11px]', 'md' => 'px-2.5 py-1 text-xs'];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full font-semibold ring-1 ring-inset whitespace-nowrap ' . ($tones[$tone] ?? $tones['slate']) . ' ' . ($sizes[$size] ?? $sizes['sm'])]) }}>
    @if ($dot)<span class="size-1.5 rounded-full {{ $dots[$tone] ?? $dots['slate'] }}"></span>@endif
    {{ $slot }}
</span>
