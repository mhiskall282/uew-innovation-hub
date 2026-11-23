@props(['action', 'method' => 'DELETE', 'message' => 'Are you sure?', 'label' => 'Delete', 'variant' => 'danger-ghost', 'size' => 'sm', 'icon' => 'trash'])
{{-- Destructive action with a browser confirmation (CSP-safe: handled by Alpine, no inline JS). --}}
<form method="POST" action="{{ $action }}" x-data @submit="if (! window.confirm(@js($message))) $event.preventDefault()" {{ $attributes->merge(['class' => 'inline']) }}>
    @csrf
    @method($method)
    <x-ui.button type="submit" :variant="$variant" :size="$size" :icon="$icon">{{ $label }}</x-ui.button>
</form>
