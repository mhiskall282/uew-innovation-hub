@props(['title' => 'My account', 'heading' => null, 'description' => null])
{{-- Member area: the public site chrome plus an account sub-navigation. --}}
@php
    $tabs = [
        ['Overview', 'portal.dashboard', 'home'],
        ['My applications', 'portal.applications.*', 'inbox'],
        ['Progress reports', 'portal.reports.*', 'clipboard'],
        ['Notifications', 'notifications.*', 'bell'],
        ['Profile & settings', 'portal.profile.*', 'cog'],
    ];
    $routes = [
        'portal.dashboard' => 'portal.dashboard', 'portal.applications.*' => 'portal.applications.index',
        'portal.reports.*' => 'portal.reports.index', 'notifications.*' => 'notifications.index', 'portal.profile.*' => 'portal.profile.edit',
    ];
@endphp
<x-layouts.site :title="$title">
    <section class="border-b border-slate-200 bg-uew-canvas">
        <div class="mx-auto max-w-7xl px-6 pt-10">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-bold tracking-widest text-uew-gold-dark uppercase">My account</p>
                    <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ $heading ?? $title }}</h1>
                    @if ($description)<p class="mt-1.5 max-w-2xl text-sm text-slate-600">{{ $description }}</p>@endif
                </div>
                @isset($actions)<div class="flex flex-wrap gap-2">{{ $actions }}</div>@endisset
            </div>
            <nav class="-mb-px mt-8 flex gap-6 overflow-x-auto" aria-label="Account">
                @foreach ($tabs as [$label, $match, $icon])
                    <a href="{{ route($routes[$match]) }}"
                       @class(['inline-flex shrink-0 items-center gap-2 border-b-2 px-1 pb-3 text-sm font-semibold', 'border-uew-navy text-uew-navy' => request()->routeIs($match), 'border-transparent text-slate-500 hover:text-slate-800' => ! request()->routeIs($match)])>
                        <x-ui.icon :name="$icon" class="size-4" /> {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>
    </section>
    <div class="mx-auto max-w-7xl px-6 py-10">
        {{ $slot }}
    </div>
</x-layouts.site>
