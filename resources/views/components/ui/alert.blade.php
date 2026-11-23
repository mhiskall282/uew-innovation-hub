@props(['tone' => 'info', 'title' => null])
@php
    $styles = [
        'info' => ['bg-blue-50 border-blue-200 text-blue-900', 'info', 'text-uew-royal'],
        'success' => ['bg-emerald-50 border-emerald-200 text-emerald-900', 'check-circle', 'text-emerald-600'],
        'warning' => ['bg-amber-50 border-amber-200 text-amber-900', 'warning', 'text-amber-600'],
        'danger' => ['bg-red-50 border-red-200 text-red-900', 'warning', 'text-red-600'],
    ];
    [$box, $icon, $iconColor] = $styles[$tone] ?? $styles['info'];
@endphp
<div role="{{ $tone === 'danger' ? 'alert' : 'status' }}" {{ $attributes->merge(['class' => "flex gap-3 rounded-xl border px-4 py-3 text-sm {$box}"]) }}>
    <x-ui.icon :name="$icon" class="mt-0.5 size-5 shrink-0 {{ $iconColor }}" />
    <div class="min-w-0">
        @if ($title)<p class="font-semibold">{{ $title }}</p>@endif
        <div class="{{ $title ? 'mt-0.5' : '' }} leading-relaxed">{{ $slot }}</div>
    </div>
</div>
