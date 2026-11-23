@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false, 'id' => null, 'wrap' => null])
@php
    $id = $id ?? str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $invalid = $errors->has($errorKey);
    $current = $type === 'password' || $type === 'file' ? null : old($errorKey, $value);
@endphp
<x-ui.field :label="$label" :for="$id" :name="$name" :hint="$hint" :required="$required" :class="$wrap">
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($current !== null) value="{{ $current }}" @endif
        @if ($required) required @endif
        @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-xs placeholder:text-slate-400 transition focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none disabled:bg-slate-50 ' . ($type === 'file' ? 'file:mr-3 file:rounded-md file:border-0 file:bg-uew-navy/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-uew-navy hover:file:bg-uew-navy/15 ' : '') . ($invalid ? 'border-red-400' : 'border-slate-300')]) }}
    >
</x-ui.field>
