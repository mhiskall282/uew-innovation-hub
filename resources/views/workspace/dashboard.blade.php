@use('App\Support\Format')
<x-layouts.workspace title="Dashboard">
    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $maxCategory = max(1, collect($stats['by_category'])->max('projects') ?? 1);
        $maxYear = max(1, collect($stats['by_year'])->max('projects') ?? 1);
    @endphp

    <x-ui.page-header :title="$greeting . ', ' . Str::before(auth()->user()->name, ' ')" description="Here is where the Hub's portfolio, funding pipeline and content stand today.">
        <x-slot:actions>
            @can('reports.export')
                <x-ui.button :href="route('workspace.projects.export')" variant="secondary" icon="download">Export portfolio</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Key figures --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="Projects" :value="number_format($stats['projects']['total'])" icon="folder" tone="navy" :href="route('workspace.projects.index')"
            :hint="$stats['projects']['active'] . ' active · ' . $stats['projects']['completed'] . ' completed · ' . $stats['projects']['pending'] . ' pending'" />
        <x-ui.stat label="Beneficiaries" :value="Format::compact($stats['beneficiaries']['reached'])" icon="users" tone="green"
            :hint="$stats['beneficiaries']['progress'] . '% of ' . number_format($stats['beneficiaries']['target']) . ' target'">
            <x-ui.progress class="mt-3" :value="$stats['beneficiaries']['progress']" tone="green" />
        </x-ui.stat>
        <x-ui.stat label="Funding managed" :value="Format::moneyCompact($stats['funding'])" icon="banknotes" tone="gold" hint="Across all projects" />
        @can('applications.review')
            <x-ui.stat label="Applications to review" :value="number_format($openApplicationCount)" icon="inbox" tone="purple" :href="route('workspace.applications.index')"
                :hint="$memberCount !== null ? number_format($memberCount) . ' registered members' : 'Across all programmes'" />
        @else
            <x-ui.stat label="Partners" :value="number_format($stats['partners'])" icon="building" tone="purple" :href="route('workspace.organizations.index')"
                :hint="number_format($stats['publications']) . ' publications'" />
        @endcan
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Focus areas --}}
        <x-ui.card title="Projects by focus area" description="Number of projects and people reached" class="xl:col-span-2">
            @if (empty($stats['by_category']))
                <x-ui.empty title="No projects yet" icon="folder" />
            @else
                <ul class="space-y-4">
                    @foreach ($stats['by_category'] as $row)
                        <li>
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                <a href="{{ route('workspace.projects.index', ['category' => $row['category']]) }}" class="font-medium text-slate-700 hover:text-uew-royal">{{ $row['category'] }}</a>
                                <span class="font-mono text-xs text-slate-500"><span class="font-semibold text-slate-900">{{ $row['projects'] }}</span> projects · {{ Format::compact($row['reached']) }} reached</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-gradient-to-r from-uew-navy to-uew-royal" style="width: {{ round($row['projects'] / $maxCategory * 100) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        {{-- Projects started per year --}}
        <x-ui.card title="Projects started per year">
            @if (empty($stats['by_year']))
                <x-ui.empty title="No dated projects yet" icon="calendar" />
            @else
                <div class="flex h-48 items-end gap-3">
                    @foreach ($stats['by_year'] as $row)
                        <div class="flex flex-1 flex-col items-center gap-2">
                            <span class="font-mono text-xs font-semibold text-slate-900">{{ $row['projects'] }}</span>
                            <div class="w-full rounded-t-md bg-gradient-to-t from-uew-purple to-uew-royal-light" style="height: {{ max(6, round($row['projects'] / $maxYear * 150)) }}px"></div>
                            <a href="{{ route('workspace.projects.index', ['year' => $row['year']]) }}" class="font-mono text-[11px] text-slate-500 hover:text-uew-royal">{{ $row['year'] }}</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Recently updated projects --}}
        <x-ui.card title="Recently updated projects" padding="p-0" class="xl:col-span-2">
            <x-slot:actions>
                <x-ui.button :href="route('workspace.projects.index')" variant="ghost" size="xs" icon-right="arrow-right">All projects</x-ui.button>
            </x-slot:actions>
            @if ($recentProjects->isEmpty())
                <x-ui.empty title="No projects yet" icon="folder">
                    @can('projects.manage')
                        <x-slot:action>
                            <x-ui.button :href="route('workspace.projects.create')" icon="plus" size="sm">Add the first project</x-ui.button>
                        </x-slot:action>
                    @endcan
                </x-ui.empty>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($recentProjects as $project)
                        <li class="flex items-center gap-4 px-5 py-3.5">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('workspace.projects.show', $project) }}" class="block truncate text-sm font-semibold text-slate-900 hover:text-uew-royal">{{ $project->title }}</a>
                                <p class="truncate text-xs text-slate-500">{{ $project->category }} · {{ $project->lead_researcher ?? 'No lead set' }}</p>
                            </div>
                            <div class="hidden w-32 sm:block"><x-ui.progress :value="$project->progress" /></div>
                            <x-ui.badge :tone="$project->status->tone()" dot>{{ $project->status->label() }}</x-ui.badge>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <div class="space-y-6">
            @can('programs.manage')
                <x-ui.card title="Open programmes" padding="p-0">
                    <x-slot:actions>
                        <x-ui.button :href="route('workspace.programs.create')" variant="ghost" size="xs" icon="plus">New</x-ui.button>
                    </x-slot:actions>
                    @if ($openPrograms->isEmpty())
                        <x-ui.empty title="Nothing open right now" icon="sparkles" class="py-8" />
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($openPrograms as $program)
                                <li class="flex items-center justify-between gap-3 px-5 py-3">
                                    <div class="min-w-0">
                                        <a href="{{ route('workspace.programs.show', $program) }}" class="block truncate text-sm font-semibold text-slate-900 hover:text-uew-royal">{{ $program->title }}</a>
                                        <p class="text-xs text-slate-500">{{ $program->closes_at ? 'Closes '.$program->closes_at->diffForHumans() : 'Rolling deadline' }}</p>
                                    </div>
                                    <span class="shrink-0 font-mono text-sm font-bold text-uew-navy">{{ $program->submitted_count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card>
            @endcan

            @can('applications.review')
                <x-ui.card title="Waiting for review" padding="p-0">
                    <x-slot:actions>
                        <x-ui.button :href="route('workspace.applications.index')" variant="ghost" size="xs" icon-right="arrow-right">Queue</x-ui.button>
                    </x-slot:actions>
                    @if ($waiting->isEmpty())
                        <x-ui.empty title="No applications waiting" icon="check-circle" class="py-8" />
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($waiting as $application)
                                <li class="px-5 py-3">
                                    <a href="{{ route('workspace.applications.show', $application) }}" class="block truncate text-sm font-semibold text-slate-900 hover:text-uew-royal">{{ $application->applicant?->name ?? 'Applicant' }}</a>
                                    <div class="mt-1 flex items-center justify-between gap-2 text-xs text-slate-500">
                                        <span class="truncate">{{ $application->program?->title }} · {{ $application->submitted_at?->diffForHumans() }}</span>
                                        <x-ui.badge :tone="$application->status->tone()" size="xs">{{ $application->status->label() }}</x-ui.badge>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card>
            @endcan

            @can('content.draft')
                <x-ui.card title="My drafts" padding="p-0">
                    <x-slot:actions>
                        <x-ui.button :href="route('workspace.posts.create')" variant="ghost" size="xs" icon="plus">New post</x-ui.button>
                    </x-slot:actions>
                    @if ($myDrafts->isEmpty())
                        <x-ui.empty title="No drafts in progress" icon="newspaper" class="py-8" />
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($myDrafts as $draft)
                                <li class="px-5 py-3">
                                    <a href="{{ route('workspace.posts.edit', $draft) }}" class="block truncate text-sm font-semibold text-slate-900 hover:text-uew-royal">{{ $draft->title }}</a>
                                    <p class="text-xs text-slate-500">Edited {{ $draft->updated_at->diffForHumans() }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card>
            @endcan

            @can('audit.view')
                <x-ui.card title="Recent activity" padding="p-0">
                    <x-slot:actions>
                        <x-ui.button :href="route('workspace.audit.index')" variant="ghost" size="xs" icon-right="arrow-right">Audit log</x-ui.button>
                    </x-slot:actions>
                    <ul class="divide-y divide-slate-100">
                        @forelse ($recentActivity as $log)
                            <li class="flex gap-3 px-5 py-3">
                                <x-ui.badge :tone="$log->tone()" size="xs" class="mt-0.5 shrink-0">{{ $log->area() }}</x-ui.badge>
                                <div class="min-w-0">
                                    <p class="truncate text-xs text-slate-700">{{ $log->description }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $log->user?->name ?? 'Public' }} · {{ $log->created_at->diffForHumans() }}</p>
                                </div>
                            </li>
                        @empty
                            <li><x-ui.empty title="No activity yet" icon="clock" class="py-8" /></li>
                        @endforelse
                    </ul>
                </x-ui.card>
            @endcan
        </div>
    </div>
</x-layouts.workspace>
