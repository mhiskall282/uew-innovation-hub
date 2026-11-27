{{-- Public site header. Desktop layout from lg (1024px); drawer below that. Expects $nav, $user --}}
<header x-data="{ open: false, scrolled: false }"
        x-init="scrolled = window.scrollY > 8"
        @scroll.window="scrolled = window.scrollY > 8"
        @keydown.escape.window="open = false"
        :class="scrolled ? 'shadow-sm shadow-uew-navy/5 bg-white/95' : 'bg-white/85'"
        class="sticky top-0 z-40 border-b border-slate-200/80 backdrop-blur-md transition-shadow">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:h-[72px]">
        {{-- Brand --}}
        <a href="{{ route('site.home') }}" class="flex shrink-0 items-center gap-3" aria-label="UEW Innovation Hub home">
            {{-- UEW crest: hidden at lg (1024–1279px), where the full menu needs the room. --}}
            <img src="{{ asset('images/uew/uew-logo.png') }}" alt="" width="44" height="43" class="hidden size-10 sm:block lg:hidden xl:block xl:size-11">
            <span class="hidden h-9 w-px bg-slate-200 sm:block lg:hidden xl:block" aria-hidden="true"></span>
            <x-ui.crest class="size-10 lg:size-11" />
            <span class="leading-tight">
                <span class="block text-[15px] font-extrabold tracking-tight text-uew-navy">UEW Innovation Hub</span>
                <span class="hidden text-[11px] font-medium text-slate-500 sm:block lg:hidden xl:block">University of Education, Winneba</span>
            </span>
        </a>

        {{-- Desktop navigation --}}
        <nav class="mx-auto hidden h-full items-center lg:flex" aria-label="Main">
            @foreach ($nav as $item)
                @php $active = request()->routeIs($item['match']); @endphp
                <a href="{{ route($item['route']) }}"
                   @if ($active) aria-current="page" @endif
                   @class([
                       'relative flex h-full items-center px-2.5 text-sm font-semibold transition-colors xl:px-3.5',
                       'text-uew-navy' => $active,
                       'text-slate-600 hover:text-uew-navy' => ! $active,
                   ])>
                    {{ $item['label'] }}
                    <span @class([
                        'absolute inset-x-2.5 bottom-0 h-0.5 rounded-full transition-all xl:inset-x-3.5',
                        'bg-uew-gold' => $active,
                        'bg-transparent' => ! $active,
                    ])></span>
                </a>
            @endforeach
        </nav>

        {{-- Desktop actions --}}
        <div class="ml-auto hidden shrink-0 items-center gap-2 lg:ml-0 lg:flex">
            @guest
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-uew-navy ring-1 ring-slate-200 ring-inset transition hover:bg-slate-50 hover:ring-slate-300">
                    <x-ui.icon name="user" class="size-4" /> Sign in
                </a>
                <a href="{{ route('register') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-uew-gold to-uew-gold-light px-3.5 py-2 text-sm font-bold text-uew-ink shadow-sm shadow-uew-gold/30 transition hover:shadow-md hover:shadow-uew-gold/40">
                    <span class="xl:hidden">Join</span><span class="hidden xl:inline">Create account</span>
                    <x-ui.icon name="arrow-right" class="size-4" />
                </a>
            @else
                @if ($user->hasVerifiedEmail())
                    <x-ui.notification-bell :user="$user" />
                @endif
                <div class="relative" x-data="{ menu: false }" @click.outside="menu = false" @keydown.escape="menu = false">
                    <button type="button" @click="menu = ! menu" class="flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1 pr-3 pl-1 transition hover:border-slate-300 hover:bg-slate-50" :aria-expanded="menu" aria-haspopup="menu">
                        <span class="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-uew-navy to-uew-royal text-[11px] font-bold text-white">{{ $user->initials() }}</span>
                        <span class="max-w-28 truncate text-sm font-semibold text-slate-700">{{ $user->firstName() }}</span>
                        <x-ui.icon name="chevron-down" class="size-3.5 text-slate-500 transition-transform" ::class="menu && 'rotate-180'" />
                    </button>
                    <div x-show="menu" x-cloak x-transition.origin.top.right role="menu" class="absolute right-0 mt-2 w-64 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card-hover">
                        <div class="border-b border-slate-100 bg-uew-canvas/60 px-4 py-3">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                            <x-ui.badge class="mt-1.5" :tone="$user->role->tone()" size="xs">{{ $user->role->label() }}</x-ui.badge>
                        </div>
                        <div class="py-1 text-sm">
                            @if ($user->isStaff())
                                <a href="{{ route('workspace.dashboard') }}" role="menuitem" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-slate-50"><x-ui.icon name="chart" class="size-4 text-slate-500" /> Staff workspace</a>
                                <a href="{{ route('workspace.profile.edit') }}" role="menuitem" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-slate-50"><x-ui.icon name="cog" class="size-4 text-slate-500" /> Account settings</a>
                            @else
                                <a href="{{ route('portal.dashboard') }}" role="menuitem" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-slate-50"><x-ui.icon name="home" class="size-4 text-slate-500" /> My account</a>
                                <a href="{{ route('portal.applications.index') }}" role="menuitem" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-slate-50"><x-ui.icon name="inbox" class="size-4 text-slate-500" /> My applications</a>
                                <a href="{{ route('portal.reports.index') }}" role="menuitem" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-slate-50"><x-ui.icon name="clipboard" class="size-4 text-slate-500" /> Progress reports</a>
                                <a href="{{ route('portal.profile.edit') }}" role="menuitem" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-slate-50"><x-ui.icon name="cog" class="size-4 text-slate-500" /> Profile & settings</a>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 py-1">
                            @csrf
                            <button type="submit" role="menuitem" class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50"><x-ui.icon name="logout" class="size-4" /> Sign out</button>
                        </form>
                    </div>
                </div>
            @endguest
        </div>

        {{-- Mobile bell and toggle --}}
        <div class="ml-auto flex items-center gap-2 lg:hidden">
        @auth
            @if ($user->hasVerifiedEmail())
                <x-ui.notification-bell :user="$user" />
            @endif
        @endauth
        <button type="button" @click="open = ! open" class="inline-flex size-10 items-center justify-center rounded-lg text-slate-700 ring-1 ring-slate-200 ring-inset hover:bg-slate-50 lg:hidden" :aria-expanded="open" aria-controls="mobile-nav">
            <span class="sr-only">Open menu</span>
            <x-ui.icon name="menu" class="size-5" x-show="! open" />
            <x-ui.icon name="x" class="size-5" x-show="open" x-cloak />
        </button>
        </div>
    </div>

    {{-- Mobile drawer --}}
    <div id="mobile-nav" x-show="open" x-cloak x-transition.origin.top class="max-h-[calc(100vh-4rem)] overflow-y-auto border-t border-slate-200 bg-white lg:hidden">
        <nav class="mx-auto max-w-7xl px-4 py-4 sm:px-6" aria-label="Mobile">
            @auth
                <div class="mb-3 flex items-center gap-3 rounded-xl bg-uew-canvas p-3">
                    <span class="flex size-10 items-center justify-center rounded-full bg-gradient-to-br from-uew-navy to-uew-royal text-xs font-bold text-white">{{ $user->initials() }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                    </div>
                </div>
            @endauth
            <ul class="space-y-0.5">
                <li><a href="{{ route('site.home') }}" @class(['flex items-center justify-between rounded-lg px-3 py-2.5 text-[15px] font-semibold', 'bg-uew-navy/5 text-uew-navy' => request()->routeIs('site.home'), 'text-slate-700 hover:bg-slate-50' => ! request()->routeIs('site.home')])>Home</a></li>
                @foreach ($nav as $item)
                    @php $active = request()->routeIs($item['match']); @endphp
                    <li>
                        <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                           @class(['flex items-center justify-between rounded-lg px-3 py-2.5 text-[15px] font-semibold', 'bg-uew-navy/5 text-uew-navy' => $active, 'text-slate-700 hover:bg-slate-50' => ! $active])>
                            {{ $item['label'] }} <x-ui.icon name="chevron-right" class="size-4 text-slate-300" />
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4 grid grid-cols-2 gap-2 border-t border-slate-100 pt-4">
                @guest
                    <x-ui.button :href="route('login')" variant="secondary" icon="user">Sign in</x-ui.button>
                    <x-ui.button :href="route('register')" variant="gold">Create account</x-ui.button>
                @else
                    <x-ui.button :href="$user->homeUrl()" variant="secondary" :icon="$user->isStaff() ? 'chart' : 'home'">{{ $user->isStaff() ? 'Workspace' : 'My account' }}</x-ui.button>
                    <form method="POST" action="{{ route('logout') }}">@csrf<x-ui.button type="submit" variant="danger-ghost" icon="logout" class="w-full">Sign out</x-ui.button></form>
                @endguest
            </div>
        </nav>
    </div>
</header>
