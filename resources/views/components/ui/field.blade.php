@props(['label' => null, 'for' => null, 'name' => null, 'hint' => null, 'required' => false])
{{-- Label + control + hint + validation error. Wrap any input in this. --}}
@php
    $errorKey = $name ? str_replace(['[', ']'], ['.', ''], $name) : null;
@endphp
<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <label for="{{ $for ?? $name }}" class="block text-xs font-semibold text-slate-700">
            {{ $label }}
            @if ($required)<span class="text-red-500" aria-hidden="true">*</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if ($errorKey && $errors->has($errorKey))
        <p class="text-xs font-medium text-red-600" id="{{ $for ?? $name }}-error">{{ $errors->first($errorKey) }}</p>
    @elseif ($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
