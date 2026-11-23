<x-layouts.site>
    @php
        $areaIcons = [
            'STEM' => 'beaker', 'Educational Tech' => 'sparkles', 'Agriculture' => 'globe',
            'Inclusive Education' => 'users', 'Digital Humanities' => 'book', 'Community Outreach' => 'map-pin',
        ];
        $areaCopy = [
            'STEM' => 'Science, robotics and maths teaching that works in Ghanaian classrooms.',
            'Educational Tech' => 'Digital tools and local-language AI for teachers and learners.',
            'Agriculture' => 'Climate-smart farming and agri-tech for youth employment.',
            'Inclusive Education' => 'Assistive technology and courseware for learners with disabilities.',
            'Digital Humanities' => 'Preserving and teaching Ghanaian languages, history and culture.',
            'Community Outreach' => 'Clean water, health and enterprise projects with local communities.',
        ];
    @endphp

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-uew-ink text-white">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,rgba(49,87,213,0.45),transparent_50%),radial-gradient(circle_at_85%_70%,rgba(109,40,217,0.35),transparent_45%)]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[0.06] [background-image:radial-gradient(#fbbf24_1px,transparent_1px)] [background-size:26px_26px]"></div>

        <div class="relative mx-auto grid max-w-7xl gap-14 px-6 py-20 lg:grid-cols-12 lg:py-28">
            <div class="lg:col-span-7">
                <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-uew-gold-light ring-1 ring-white/15 ring-inset">
                    <x-ui.icon name="academic" class="size-4" /> {{ $content['hero_eyebrow'] }}
                </p>
                <h1 class="mt-6 text-4xl leading-[1.1] font-extrabold tracking-tight sm:text-5xl lg:text-6xl">{{ $content['hero_title'] }}</h1>
                <p class="mt-6 max-w-xl text-base leading-relaxed text-blue-100/80 sm:text-lg">{{ $content['hero_subtitle'] }}</p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <x-ui.button :href="route('site.programs.index')" variant="gold" size="lg" icon-right="arrow-right">See open programmes</x-ui.button>
                    <x-ui.button :href="route('site.projects.index')" variant="light" size="lg">Explore our projects</x-ui.button>
                </div>
                @if ($openCount > 0)
                    <p class="mt-5 inline-flex items-center gap-2 text-sm text-blue-100/80">
                        <span class="size-2 animate-pulse rounded-full bg-emerald-400"></span>
                        {{ $openCount }} {{ Str::plural('programme', $openCount) }} accepting applications now
                    </p>
                @endif
            </div>

            <div class="lg:col-span-5">
                <dl class="grid grid-cols-2 gap-4">
                    @foreach ([
                        ['Projects', number_format($stats['projects']), 'folder'],
                        ['Beneficiaries reached', number_format($stats['beneficiaries']), 'users'],
                        ['Funding secured', \App\Support\Format::moneyCompact($stats['funding']), 'banknotes'],
                        ['Partners', number_format($stats['partners']), 'building'],
                    ] as [$label, $value, $icon])
                        <div class="rounded-2xl bg-white/[0.06] p-5 ring-1 ring-white/10 backdrop-blur">
                            <x-ui.icon :name="$icon" class="size-5 text-uew-gold" />
                            <dd class="mt-3 font-mono text-2xl font-bold tracking-tight sm:text-3xl">{{ $value }}</dd>
                            <dt class="mt-1 text-xs font-medium text-blue-100/70">{{ $label }}</dt>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>

    {{-- Open opportunities --}}
    @if ($programs->isNotEmpty())
        <section class="border-b border-slate-200 bg-uew-canvas py-16" aria-labelledby="programmes-heading">
            <div class="mx-auto max-w-7xl px-6">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold tracking-widest text-uew-gold-dark uppercase">Apply now</p>
                        <h2 id="programmes-heading" class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">Open opportunities</h2>
                        <p class="mt-2 max-w-2xl text-slate-600">Grants, fellowships, incubation, training and events from the Hub. Create a free account to apply.</p>
                    </div>
                    <x-ui.button :href="route('site.programs.index')" variant="secondary" icon-right="arrow-right">All programmes</x-ui.button>
                </div>
                <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($programs as $program)
                        @include('site.partials.program-card', ['program' => $program])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Focus areas --}}
    <section class="mx-auto max-w-7xl px-6 py-20" aria-labelledby="focus-heading">
        <div class="max-w-2xl">
            <p class="text-xs font-bold tracking-widest text-uew-gold-dark uppercase">What we work on</p>
            <h2 id="focus-heading" class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">Six focus areas</h2>
            <p class="mt-3 text-slate-600">Every project the Hub supports sits in one of these areas, chosen for where UEW's expertise in education can do the most good.</p>
        </div>
        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($focusAreas as $area => $count)
                <a href="{{ route('site.projects.index', ['category' => $area]) }}" class="group rounded-2xl border border-slate-200 p-6 transition hover:border-uew-royal/40 hover:bg-uew-canvas">
                    <div class="flex items-center justify-between">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-uew-navy text-uew-gold-light">
                            <x-ui.icon :name="$areaIcons[$area] ?? 'sparkles'" class="size-5" />
                        </span>
                        <span class="font-mono text-xs font-semibold text-slate-400">{{ $count }} {{ Str::plural('project', $count) }}</span>
                    </div>
                    <h3 class="mt-5 font-bold text-slate-900 group-hover:text-uew-royal">{{ $area }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $areaCopy[$area] ?? '' }}</p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Featured projects --}}
    @if ($featuredProjects->isNotEmpty())
        <section class="bg-uew-canvas py-20" aria-labelledby="projects-heading">
            <div class="mx-auto max-w-7xl px-6">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold tracking-widest text-uew-gold-dark uppercase">In progress</p>
                        <h2 id="projects-heading" class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">Current projects</h2>
                    </div>
                    <x-ui.button :href="route('site.projects.index')" variant="secondary" icon-right="arrow-right">All projects</x-ui.button>
                </div>
                <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($featuredProjects as $project)
                        <div class="relative">@include('site.partials.project-card', ['project' => $project])</div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- News + publications --}}
    <section class="mx-auto grid max-w-7xl gap-14 px-6 py-20 lg:grid-cols-12">
        <div class="lg:col-span-7">
            <div class="flex items-end justify-between gap-4">
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Latest news</h2>
                <a href="{{ route('site.news.index') }}" class="text-sm font-semibold text-uew-royal hover:underline">All news</a>
            </div>
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                @forelse ($posts as $post)
                    <div @class(['sm:col-span-2' => $loop->first])>@include('site.partials.post-card', ['post' => $post])</div>
                @empty
                    <p class="text-sm text-slate-500 sm:col-span-2">News will appear here soon.</p>
                @endforelse
            </div>
        </div>
        <div class="lg:col-span-5">
            <div class="flex items-end justify-between gap-4">
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Publications</h2>
                <a href="{{ route('site.publications.index') }}" class="text-sm font-semibold text-uew-royal hover:underline">Library</a>
            </div>
            <ul class="mt-6 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
                @forelse ($publications as $publication)
                    <li class="p-5">
                        <p class="text-xs font-semibold text-uew-purple">{{ $publication->type }} · {{ $publication->publish_date?->format('M Y') }}</p>
                        <p class="mt-1 font-semibold leading-snug text-slate-900">{{ $publication->title }}</p>
                        @if ($publication->hasDocument())
                            <a href="{{ route('site.documents.publication', $publication) }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-uew-royal hover:underline">
                                <x-ui.icon name="download" class="size-3.5" /> Read PDF
                            </a>
                        @endif
                    </li>
                @empty
                    <li class="p-5 text-sm text-slate-500">Publications will appear here soon.</li>
                @endforelse
            </ul>
        </div>
    </section>

    {{-- Partners --}}
    @if ($partners->isNotEmpty())
        <section class="border-y border-slate-200 bg-white py-14" aria-labelledby="partners-heading">
            <div class="mx-auto max-w-7xl px-6">
                <h2 id="partners-heading" class="text-center text-xs font-bold tracking-widest text-slate-500 uppercase">Working with</h2>
                <ul class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    @foreach ($partners as $partner)
                        <li class="rounded-full border border-slate-200 bg-uew-canvas px-4 py-2 text-sm font-semibold text-slate-700">{{ $partner->name }}</li>
                    @endforeach
                </ul>
                <p class="mt-6 text-center"><a href="{{ route('site.partners.index') }}" class="text-sm font-semibold text-uew-royal hover:underline">See all partners</a></p>
            </div>
        </section>
    @endif

    {{-- Call to action --}}
    <section class="mx-auto max-w-7xl px-6 py-20">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-uew-navy via-uew-navy-deep to-uew-ink px-8 py-14 text-white sm:px-14">
            <div class="pointer-events-none absolute -top-24 -right-24 size-72 rounded-full bg-uew-purple/40 blur-3xl"></div>
            <div class="relative grid items-center gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <h2 class="text-3xl font-extrabold tracking-tight">{{ $content['cta_title'] }}</h2>
                    <p class="mt-3 max-w-2xl text-blue-100/80">{{ $content['cta_text'] }}</p>
                </div>
                <div class="flex flex-wrap gap-3 lg:justify-end">
                    @guest
                        <x-ui.button :href="route('register')" variant="gold" size="lg">Create a free account</x-ui.button>
                    @else
                        <x-ui.button :href="auth()->user()->homeUrl()" variant="gold" size="lg">Go to my account</x-ui.button>
                    @endguest
                    <x-ui.button :href="route('site.programs.index')" variant="light" size="lg">Browse programmes</x-ui.button>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
