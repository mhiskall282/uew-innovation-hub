<x-layouts.site :title="$project->title" :description="Str::limit($project->summary, 155)">
    <section class="relative overflow-hidden bg-uew-ink text-white">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_80%_10%,rgba(49,87,213,0.4),transparent_50%)]"></div>
        <div class="relative mx-auto max-w-7xl px-6 py-14">
            <a href="{{ route('site.projects.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-100/70 hover:text-white">
                <x-ui.icon name="arrow-left" class="size-3.5" /> All projects
            </a>
            <div class="mt-5 flex flex-wrap items-center gap-2">
                <x-ui.badge tone="gold">{{ $project->category }}</x-ui.badge>
                <x-ui.badge :tone="$project->status->tone()" dot>{{ $project->status->label() }}</x-ui.badge>
            </div>
            <h1 class="mt-4 max-w-4xl text-3xl leading-tight font-extrabold tracking-tight sm:text-4xl">{{ $project->title }}</h1>
            <p class="mt-4 max-w-3xl text-blue-100/80">
                Led by <span class="font-semibold text-white">{{ $project->lead_researcher ?? 'the UEW Innovation Hub' }}</span>@if ($project->department), {{ $project->department }}@endif
            </p>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-10 px-6 py-12 lg:grid-cols-12">
        <div class="space-y-10 lg:col-span-8">
            <section>
                <h2 class="text-lg font-bold text-slate-900">About the project</h2>
                <p class="mt-3 leading-relaxed whitespace-pre-line text-slate-700">{{ $project->summary ?: 'A summary for this project is coming soon.' }}</p>
            </section>

            @if ($project->organizations->isNotEmpty())
                <section>
                    <h2 class="text-lg font-bold text-slate-900">Partners</h2>
                    <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($project->organizations as $partner)
                            <li class="flex items-center gap-3 rounded-xl border border-slate-200 p-4">
                                <span class="flex size-10 items-center justify-center rounded-lg bg-uew-navy/10 text-uew-navy"><x-ui.icon name="building" class="size-5" /></span>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-900">{{ $partner->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $partner->pivot->role }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($project->publications->isNotEmpty())
                <section>
                    <h2 class="text-lg font-bold text-slate-900">Publications from this project</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($project->publications as $publication)
                            @include('site.partials.publication-row', ['publication' => $publication])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-5 lg:col-span-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
                <h2 class="text-sm font-bold text-uew-navy">Impact</h2>
                <p class="mt-4 font-mono text-3xl font-bold text-slate-900">{{ number_format($project->beneficiaries_reached) }}</p>
                <p class="text-xs text-slate-500">beneficiaries reached of {{ number_format($project->beneficiaries_target) }} targeted</p>
                <x-ui.progress class="mt-4" :value="$project->progress" />

                <dl class="mt-6 space-y-3 border-t border-slate-100 pt-5 text-sm">
                    @if ($project->grant_amount)
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Funding</dt><dd class="font-mono font-semibold text-slate-900">{{ \App\Support\Format::money($project->grant_amount, 0) }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Started</dt><dd class="font-medium text-slate-900">{{ $project->start_date?->format('j M Y') ?? 'To be confirmed' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Ends</dt><dd class="font-medium text-slate-900">{{ $project->end_date?->format('j M Y') ?? 'Ongoing' }}</dd></div>
                </dl>

                @if ($project->report_file_path)
                    <x-ui.button :href="route('site.documents.project-report', $project)" class="mt-6 w-full" icon="download" target="_blank" rel="noopener">Download project report</x-ui.button>
                @endif
            </div>

            <div class="rounded-2xl bg-uew-canvas p-6">
                <h2 class="text-sm font-bold text-slate-900">Want to work on something similar?</h2>
                <p class="mt-1.5 text-sm text-slate-600">The Hub funds new projects in {{ $project->category }} and five other focus areas.</p>
                <x-ui.button :href="route('site.programs.index')" variant="gold" size="sm" class="mt-4">See open programmes</x-ui.button>
            </div>
        </aside>
    </div>

    @if ($related->isNotEmpty())
        <section class="border-t border-slate-200 bg-uew-canvas py-14">
            <div class="mx-auto max-w-7xl px-6">
                <h2 class="text-xl font-bold text-slate-900">More in {{ $project->category }}</h2>
                <div class="mt-6 grid gap-6 md:grid-cols-3">
                    @foreach ($related->load('organizations:id,name') as $item)
                        @include('site.partials.project-card', ['project' => $item])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.site>
