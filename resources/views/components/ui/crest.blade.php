@props(['class' => 'size-10'])
<img src="{{ asset('images/innovation_hub_logo.jpg') }}" alt="UEW Innovation Hub crest" width="80" height="80"
    {{ $attributes->merge(['class' => "{$class} shrink-0 rounded-lg bg-white object-contain p-0.5 ring-1 ring-uew-gold/40"]) }}>
