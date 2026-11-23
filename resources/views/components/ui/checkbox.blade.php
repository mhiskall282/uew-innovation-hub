@props(['name', 'label', 'checked' => false, 'hint' => null, 'value' => '1', 'id' => null])
@php
    $id = $id ?? str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $isChecked = old($errorKey) !== null ? (bool) old($errorKey) : (bool) $checked;
@endphp
<div {{ $attributes->only('class')->merge(['class' => 'flex items-start gap-3']) }}>
    <input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="{{ $value }}" @checked($isChecked)
        {{ $attributes->except('class') }}
        class="mt-0.5 size-4 shrink-0 rounded border-slate-300 text-uew-navy focus:ring-uew-royal">
    <div class="text-sm">
        <label for="{{ $id }}" class="font-medium text-slate-800">{{ $label }}</label>
        @if ($errors->has($errorKey))
            <p class="mt-0.5 text-xs font-medium text-red-600">{{ $errors->first($errorKey) }}</p>
        @elseif ($hint)
            <p class="mt-0.5 text-xs text-slate-500">{{ $hint }}</p>
        @endif
    </div>
</div>
