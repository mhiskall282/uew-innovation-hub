@props(['value' => 0, 'tone' => 'royal', 'label' => null, 'name' => null])
{{-- $label shows a visible caption; $name only labels it for screen readers. One of them is always set. --}}
@php
    $value = max(0, min(100, (int) $value));
    $bars = ['royal' => 'from-uew-royal to-uew-purple', 'gold' => 'from-uew-gold to-uew-gold-light', 'green' => 'from-emerald-500 to-emerald-400', 'red' => 'from-red-500 to-red-400'];
    $accessibleName = $label ?? $name ?? 'Progress';
@endphp
<div {{ $attributes }}>
    @if ($label)
        <div class="mb-1.5 flex items-center justify-between text-xs">
            <span class="font-medium text-slate-600">{{ $label }}</span>
            <span class="font-mono font-semibold text-slate-900">{{ $value }}%</span>
        </div>
    @endif
    <div class="h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-label="{{ $accessibleName }}" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100" aria-valuetext="{{ $value }}%">
        <div class="h-full rounded-full bg-gradient-to-r {{ $bars[$tone] ?? $bars['royal'] }}" style="width: {{ $value }}%"></div>
    </div>
</div>
