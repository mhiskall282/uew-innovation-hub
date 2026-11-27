@props(['title', 'icon' => 'inbox'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-14 text-center']) }}>
    <span class="flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
        <x-ui.icon :name="$icon" class="size-6" />
    </span>
    <p class="mt-4 text-sm font-semibold text-slate-900">{{ $title }}</p>
    @if (trim($slot))
        <div class="mt-1 max-w-sm text-sm text-slate-500">{{ $slot }}</div>
    @endif
    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>
