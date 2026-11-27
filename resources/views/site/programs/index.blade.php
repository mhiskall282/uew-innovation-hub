<x-layouts.site title="Programmes" description="Grants, fellowships, incubation, competitions, training and events at the UEW Innovation Hub.">
    <x-ui.page-hero eyebrow="Opportunities" title="Programmes" photo="study-kiosk">
        Grants, fellowships, incubation cohorts, competitions, training and events. Create a free account once, then apply to any open programme and follow your applications online.
        @guest
            <x-slot:below>
                <div class="flex flex-wrap gap-3">
                    <x-ui.button :href="route('register')" variant="gold" icon-right="arrow-right">Create a free account</x-ui.button>
                    <x-ui.button :href="route('login')" variant="light">I already have an account</x-ui.button>
                </div>
            </x-slot:below>
        @endguest
    </x-ui.page-hero>

    <section class="mx-auto max-w-7xl px-6 py-10">
        {{-- Tabs --}}
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200">
            <nav class="-mb-px flex gap-4 overflow-x-auto sm:gap-6" aria-label="Programme status">
                @foreach (['open' => 'Open now', 'upcoming' => 'Opening soon', 'closed' => 'Closed'] as $key => $label)
                    <a href="{{ route('site.programs.index', array_filter(['tab' => $key, 'type' => $type?->value])) }}"
                       @class(['inline-flex items-center gap-2 border-b-2 px-1 pb-3 text-sm font-semibold', 'border-uew-navy text-uew-navy' => $tab === $key, 'border-transparent text-slate-500 hover:text-slate-800' => $tab !== $key])
                       @if ($tab === $key) aria-current="page" @endif>
                        {{ $label }} <span class="rounded-full bg-slate-100 px-2 py-0.5 font-mono text-[11px] text-slate-600">{{ $counts[$key] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- Type filter --}}
        <div class="mt-5 flex flex-wrap gap-2" aria-label="Filter by type">
            <a href="{{ route('site.programs.index', ['tab' => $tab]) }}" @class(['rounded-full px-3.5 py-1.5 text-xs font-semibold ring-1 ring-inset', 'bg-uew-navy text-white ring-uew-navy' => ! $type, 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50' => $type])>All types</a>
            @foreach ($types as $option)
                <a href="{{ route('site.programs.index', ['tab' => $tab, 'type' => $option->value]) }}" @class(['inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold ring-1 ring-inset', 'bg-uew-navy text-white ring-uew-navy' => $type === $option, 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50' => $type !== $option])>
                    <x-ui.icon :name="$option->icon()" class="size-3.5" /> {{ $option->plural() }}
                </a>
            @endforeach
        </div>

        {{-- Upcoming events strip (only on the default view) --}}
        @if ($events->isNotEmpty())
            <div class="mt-8 rounded-2xl border border-amber-200 bg-amber-50/60 p-5">
                <h2 class="flex items-center gap-2 text-sm font-bold text-amber-900"><x-ui.icon name="calendar" class="size-4" /> Upcoming events</h2>
                <ul class="mt-3 grid gap-3 md:grid-cols-3">
                    @foreach ($events as $event)
                        <li>
                            <a href="{{ route('site.programs.show', $event) }}" class="block rounded-xl bg-white p-4 ring-1 ring-amber-200 hover:ring-uew-gold">
                                <p class="font-mono text-xs font-semibold text-uew-gold-dark">{{ $event->starts_at?->format('D j M, H:i') ?? 'Date to be announced' }}</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ $event->title }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $event->location ?? 'UEW, Winneba' }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-8">
            @if ($programs->isEmpty())
                <x-ui.empty :title="$tab === 'open' ? 'No programmes are open right now' : 'Nothing to show here yet'" icon="calendar">
                    New calls are announced by e-mail. @guest<a href="{{ route('register') }}" class="font-semibold text-uew-royal hover:underline">Create an account</a> and choose your interests to hear first.@endguest
                </x-ui.empty>
            @else
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($programs as $program)
                        @include('site.partials.program-card', ['program' => $program])
                    @endforeach
                </div>
                <div class="mt-10">{{ $programs->links() }}</div>
            @endif
        </div>
    </section>
</x-layouts.site>
