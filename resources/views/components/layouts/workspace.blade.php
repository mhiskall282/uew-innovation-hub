@props(['title' => 'Workspace'])
@inject('metrics', 'App\Services\MetricsService')
@php
    $user = auth()->user();
    $openGrants = $user->can('grants.review') ? $metrics->openGrantCount() : 0;
    $newMessages = $user->can('messages.manage') ? \App\Models\ContactMessage::newCount() : 0;
    $openApplications = $user->can('applications.review') ? $metrics->openApplicationCount() : 0;
    $awaitingReports = $user->can('applications.review') ? \App\Models\ProgressReport::where('status', \App\Enums\ReportStatus::Submitted)->count() : 0;

    // Navigation is declared once; each item is hidden unless the user holds its permission.
    $groups = [
        'Overview' => [
            ['label' => 'Dashboard', 'icon' => 'chart', 'route' => 'workspace.dashboard', 'match' => 'workspace.dashboard', 'can' => 'workspace.access'],
            ['label' => 'Reports', 'icon' => 'chart', 'route' => 'workspace.reports.index', 'match' => 'workspace.reports.*', 'can' => 'reports.view'],
        ],
        'Programmes' => [
            ['label' => 'Programmes', 'icon' => 'sparkles', 'route' => 'workspace.programs.index', 'match' => 'workspace.programs.*', 'can' => 'programs.manage'],
            ['label' => 'Applications', 'icon' => 'inbox', 'route' => 'workspace.applications.index', 'match' => 'workspace.applications.*', 'can' => 'applications.review', 'badge' => $openApplications],
            ['label' => 'Incubation board', 'icon' => 'clipboard', 'route' => 'workspace.progress-reports.index', 'match' => 'workspace.progress-reports.*', 'can' => 'applications.review', 'badge' => $awaitingReports],
            ['label' => 'Legacy grants', 'icon' => 'archive', 'route' => 'workspace.grants.index', 'match' => 'workspace.grants.*', 'can' => 'grants.review', 'badge' => $openGrants],
        ],
        'People' => [
            ['label' => 'Members', 'icon' => 'users', 'route' => 'workspace.members.index', 'match' => 'workspace.members.*', 'can' => 'members.manage'],
            ['label' => 'Broadcasts', 'icon' => 'bell', 'route' => 'workspace.broadcasts.index', 'match' => 'workspace.broadcasts.*', 'can' => 'broadcasts.send'],
            ['label' => 'Enquiries', 'icon' => 'chat', 'route' => 'workspace.messages.index', 'match' => 'workspace.messages.*', 'can' => 'messages.manage', 'badge' => $newMessages],
        ],
        'Portfolio' => [
            ['label' => 'Projects', 'icon' => 'folder', 'route' => 'workspace.projects.index', 'match' => 'workspace.projects.*', 'can' => 'projects.view'],
            ['label' => 'Partners', 'icon' => 'building', 'route' => 'workspace.organizations.index', 'match' => 'workspace.organizations.*', 'can' => 'projects.view'],
            ['label' => 'Publications', 'icon' => 'book', 'route' => 'workspace.publications.index', 'match' => 'workspace.publications.*', 'can' => 'publications.manage'],
        ],
        'Website' => [
            ['label' => 'News & posts', 'icon' => 'newspaper', 'route' => 'workspace.posts.index', 'match' => 'workspace.posts.*', 'can' => 'content.draft'],
            ['label' => 'Pages', 'icon' => 'document', 'route' => 'workspace.pages.index', 'match' => 'workspace.pages.*', 'can' => 'pages.manage'],
        ],
        'Administration' => [
            ['label' => 'Staff accounts', 'icon' => 'shield', 'route' => 'workspace.users.index', 'match' => 'workspace.users.*', 'can' => 'users.manage'],
            ['label' => 'Site settings', 'icon' => 'cog', 'route' => 'workspace.settings.edit', 'match' => 'workspace.settings.*', 'can' => 'settings.manage'],
            ['label' => 'E-mail templates', 'icon' => 'mail', 'route' => 'workspace.templates.index', 'match' => 'workspace.templates.*', 'can' => 'settings.manage'],
            ['label' => 'Audit log', 'icon' => 'clock', 'route' => 'workspace.audit.index', 'match' => 'workspace.audit.*', 'can' => 'audit.view'],
        ],
        'You' => [
            ['label' => 'Account settings', 'icon' => 'user', 'route' => 'workspace.profile.edit', 'match' => 'workspace.profile.*', 'can' => 'workspace.access'],
        ],
    ];

    $groups = array_filter(array_map(fn ($items) => array_values(array_filter($items, fn ($i) => $user->can($i['can']))), $groups));
    $generalErrors = collect(['organization', 'user'])->map(fn ($k) => $errors->first($k))->filter();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · Workspace · UEW Innovation Hub</title>
    <link rel="icon" href="{{ asset('images/innovation_hub_logo.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-uew-canvas font-sans text-slate-800 antialiased"
      x-data="{ mobileNav: false, collapsed: localStorage.getItem('workspace.sidebar') === 'collapsed' }"
      x-effect="localStorage.setItem('workspace.sidebar', collapsed ? 'collapsed' : 'open')"
      @keydown.escape.window="mobileNav = false">

<div class="flex min-h-full">
    {{-- Mobile overlay --}}
    <div x-show="mobileNav" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-uew-ink/60 lg:hidden" @click="mobileNav = false"></div>

    {{-- Sidebar --}}
    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-uew-ink text-slate-300 transition-[width,transform] duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
        :class="[mobileNav ? 'translate-x-0' : '-translate-x-full', collapsed ? 'lg:w-[76px]' : 'lg:w-64']"
        aria-label="Workspace navigation">

        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/5 px-4">
            <a href="{{ route('workspace.dashboard') }}" class="flex min-w-0 items-center gap-3">
                <img src="{{ asset('images/uew/uew-logo.png') }}" alt="University of Education, Winneba" width="36" height="35" class="size-9 shrink-0 rounded-full bg-white p-0.5 ring-1 ring-uew-gold/40">
                <span class="min-w-0 leading-tight" x-show="! collapsed" x-transition.opacity>
                    <span class="block truncate text-sm font-bold text-white">UEW Innovation Hub</span>
                    <span class="block truncate font-mono text-[10px] font-semibold tracking-widest text-uew-gold uppercase">Staff workspace</span>
                </span>
            </a>
            <button type="button" class="ml-auto rounded-md p-1 text-slate-400 hover:text-white lg:hidden" @click="mobileNav = false">
                <span class="sr-only">Close navigation</span>
                <x-ui.icon name="x" class="size-5" />
            </button>
        </div>

        <nav class="flex-1 space-y-5 overflow-y-auto px-3 py-5">
            @foreach ($groups as $group => $items)
                <div>
                    <p class="mb-1.5 px-3 font-mono text-[10px] font-bold tracking-widest text-slate-400 uppercase" x-show="! collapsed">{{ $group }}</p>
                    <ul class="space-y-0.5">
                        @foreach ($items as $item)
                            @php $active = request()->routeIs($item['match']); @endphp
                            <li>
                                <a href="{{ route($item['route']) }}"
                                   title="{{ $item['label'] }}"
                                   @if ($active) aria-current="page" @endif
                                   @class([
                                       'group relative flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-semibold transition-colors',
                                       'bg-uew-navy text-white shadow-sm' => $active,
                                       'text-slate-300 hover:bg-uew-ink-soft hover:text-white' => ! $active,
                                   ])>
                                    @if ($active)<span class="absolute inset-y-1.5 left-0 w-1 rounded-r bg-uew-gold"></span>@endif
                                    <x-ui.icon :name="$item['icon']" @class(['size-[18px] shrink-0', 'text-uew-gold-light' => $active, 'text-slate-400 group-hover:text-slate-200' => ! $active]) />
                                    <span class="truncate" x-show="! collapsed">{{ $item['label'] }}</span>
                                    @if (! empty($item['badge']))
                                        <span class="ml-auto rounded-full bg-uew-gold px-1.5 py-px font-mono text-[10px] font-bold text-uew-ink" x-show="! collapsed">{{ $item['badge'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="shrink-0 border-t border-white/5 p-3">
            <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-uew-royal to-uew-purple text-xs font-bold text-white">{{ $user->initials() }}</span>
                <div class="min-w-0 flex-1" x-show="! collapsed">
                    <p class="truncate text-sm font-semibold text-white">{{ $user->name }}</p>
                    <p class="truncate text-[11px] text-uew-gold-light">{{ $user->role->label() }}</p>
                </div>
            </div>
            <div class="mt-1 grid gap-0.5" :class="collapsed ? 'grid-cols-1' : 'grid-cols-2'">
                <a href="{{ route('workspace.profile.edit') }}" title="My account" class="flex items-center justify-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-400 hover:bg-uew-ink-soft hover:text-white">
                    <x-ui.icon name="user" class="size-4" /><span x-show="! collapsed">Account</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Sign out" class="flex w-full items-center justify-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-400 hover:bg-red-500/10 hover:text-red-300">
                        <x-ui.icon name="logout" class="size-4" /><span x-show="! collapsed">Sign out</span>
                    </button>
                </form>
            </div>
            <button type="button" @click="collapsed = ! collapsed" class="mt-2 hidden w-full items-center justify-center gap-2 rounded-lg py-1.5 text-[11px] font-semibold text-slate-400 hover:text-slate-300 lg:flex">
                <x-ui.icon name="chevron-double-left" class="size-4 transition-transform" ::class="collapsed && 'rotate-180'" />
                <span x-show="! collapsed">Collapse</span>
            </button>
        </div>
    </aside>

    {{-- Main column --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
            <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" @click="mobileNav = true">
                <span class="sr-only">Open navigation</span>
                <x-ui.icon name="menu" class="size-5" />
            </button>
            <p class="min-w-0 truncate text-sm font-semibold text-slate-900">{{ $title }}</p>
            <div class="ml-auto flex items-center gap-2">
                <x-ui.button :href="route('site.home')" variant="ghost" size="sm" icon="globe" target="_blank" rel="noopener">
                    <span class="hidden sm:inline">Public site</span>
                </x-ui.button>
                @can('projects.manage')
                    <x-ui.button :href="route('workspace.projects.create')" size="sm" icon="plus">
                        <span class="hidden sm:inline">New project</span>
                    </x-ui.button>
                @endcan
                <x-ui.notification-bell :user="$user" />

                {{-- Account menu --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                    <button type="button" @click="open = ! open" class="flex items-center gap-2 rounded-full p-0.5 pr-2 hover:bg-slate-100" :aria-expanded="open" aria-haspopup="menu">
                        <span class="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-uew-royal to-uew-purple text-[11px] font-bold text-white">{{ $user->initials() }}</span>
                        <x-ui.icon name="chevron-down" class="size-3.5 text-slate-500" />
                        <span class="sr-only">Account menu</span>
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top.right role="menu"
                         class="absolute right-0 mt-2 w-60 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card-hover">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                            <x-ui.badge class="mt-1.5" :tone="$user->role->tone()" size="xs">{{ $user->role->label() }}</x-ui.badge>
                        </div>
                        <div class="py-1 text-sm">
                            <a href="{{ route('workspace.profile.edit') }}" role="menuitem" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-slate-50"><x-ui.icon name="user" class="size-4 text-slate-500" /> Account settings</a>
                            @can('settings.manage')
                                <a href="{{ route('workspace.settings.edit') }}" role="menuitem" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-slate-50"><x-ui.icon name="cog" class="size-4 text-slate-500" /> Site settings</a>
                            @endcan
                            <a href="{{ route('site.home') }}" target="_blank" rel="noopener" role="menuitem" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-slate-50"><x-ui.icon name="globe" class="size-4 text-slate-500" /> View public site</a>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 py-1">
                            @csrf
                            <button type="submit" role="menuitem" class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50"><x-ui.icon name="logout" class="size-4" /> Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 6000)">
                    <x-ui.alert tone="success">
                        <div class="flex items-start justify-between gap-4">
                            <span>{{ session('status') }}</span>
                            <button type="button" @click="show = false" class="text-emerald-700 hover:text-emerald-900"><span class="sr-only">Dismiss</span><x-ui.icon name="x" class="size-4" /></button>
                        </div>
                    </x-ui.alert>
                </div>
            @endif

            @foreach ($generalErrors as $message)
                <x-ui.alert tone="danger">{{ $message }}</x-ui.alert>
            @endforeach

            {{ $slot }}
        </main>

        <footer class="border-t border-slate-200 px-6 py-4 text-center text-[11px] text-slate-500">
            UEW Innovation Hub workspace · Authorised staff only · Activity is recorded in the audit log
        </footer>
    </div>
</div>
</body>
</html>
