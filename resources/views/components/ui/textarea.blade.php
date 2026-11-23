@props(['name', 'label' => null, 'value' => null, 'hint' => null, 'required' => false, 'rows' => 4, 'id' => null, 'wrap' => null])
@php
    $id = $id ?? str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $invalid = $errors->has($errorKey);
@endphp
<x-ui.field :label="$label" :for="$id" :name="$name" :hint="$hint" :required="$required" :class="$wrap">
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($required) required @endif
        @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border bg-white px-3 py-2 text-sm leading-6 text-slate-900 shadow-xs placeholder:text-slate-400 transition focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none ' . ($invalid ? 'border-red-400' : 'border-slate-300')]) }}
    >{{ old($errorKey, $value) }}</textarea>
</x-ui.field>
