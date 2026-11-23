@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null, 'required' => false, 'id' => null, 'wrap' => null])
{{-- $options: either a list ['A', 'B'] or a map ['value' => 'Label']. --}}
@php
    $id = $id ?? str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $invalid = $errors->has($errorKey);
    $selected = (string) old($errorKey, $value instanceof \BackedEnum ? $value->value : $value);
    $isList = array_is_list($options);
@endphp
<x-ui.field :label="$label" :for="$id" :name="$name" :hint="$hint" :required="$required" :class="$wrap">
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($required) required @endif
        @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border bg-white px-3 py-2 pr-8 text-sm text-slate-900 shadow-xs transition focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none ' . ($invalid ? 'border-red-400' : 'border-slate-300')]) }}
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $key => $optionLabel)
            @php $optionValue = (string) ($isList ? $optionLabel : $key); @endphp
            <option value="{{ $optionValue }}" @selected($selected === $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</x-ui.field>
