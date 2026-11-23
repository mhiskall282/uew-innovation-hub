@use('App\Support\Format')
<x-layouts.workspace title="Projects">
    <x-ui.page-header title="Projects" description="Every Hub project, including internal ones that are not shown on the public site.">
        <x-slot:actions>
            @can('reports.export')
                <x-ui.button :href="route('workspace.projects.export', request()->query())" variant="secondary" icon="download">Export CSV</x-ui.button>
            @endcan
            @can('projects.manage')
                <x-ui.button :href="route('workspace.projects.create')" icon="plus">New project</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <form method="GET" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-card md:grid-cols-12" role="search">
        <label class="relative md:col-span-4">
            <span class="sr-only">Search</span>
            <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Title, researcher, department"
                   class="block w-full rounded-lg border border-slate-300 py-2 pr-3 pl-9 text-sm focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
        </label>
        <x-ui.select name="status" :options="$statuses" :value="$filters['status'] ?? ''" placeholder="All statuses" wrap="md:col-span-2" aria-label="Status" />
        <x-ui.select name="category" :options="$focusAreas" :value="$filters['category'] ?? ''" placeholder="All focus areas" wrap="md:col-span-2" aria-label="Focus area" />
        <x-ui.select name="year" :options="array_combine($years, $years) ?: []" :value="$filters['year'] ?? ''" placeholder="Any year" wrap="md:col-span-1" aria-label="Start year" />
        <x-ui.select name="organization" :options="$organizations->pluck('name', 'id')->all()" :value="$filters['organization'] ?? ''" placeholder="Any partner" wrap="md:col-span-2" aria-label="Partner" />
        <div class="flex gap-2 md:col-span-1">
            <x-ui.button type="submit" class="flex-1" icon="filter"><span class="sr-only md:not-sr-only">Go</span></x-ui.button>
        </div>
    </form>

    <x-ui.card padding="p-0">
        @if ($projects->isEmpty())
            <x-ui.empty title="No projects match" icon="folder">
                @if (array_filter($filters))
                    <a href="{{ route('workspace.projects.index') }}" class="font-semibold text-uew-royal hover:underline">Clear filters</a>
                @endif
            </x-ui.empty>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3">Project</th>
                            <th scope="col" class="px-3 py-3">Status</th>
                            <th scope="col" class="hidden px-3 py-3 lg:table-cell">Partners</th>
                            <th scope="col" class="px-3 py-3">Reach</th>
                            <th scope="col" class="hidden px-3 py-3 text-right md:table-cell">Funding</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($projects as $project)
                            <tr class="hover:bg-slate-50/60">
                                <td class="max-w-md px-5 py-3.5">
                                    <a href="{{ route('workspace.projects.show', $project) }}" class="font-semibold text-slate-900 hover:text-uew-royal">{{ $project->title }}</a>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-slate-500">
                                        <span>{{ $project->category }}</span>
                                        <span aria-hidden="true">·</span>
                                        <span>{{ $project->start_date?->format('M Y') ?? 'No start date' }}</span>
                                        @unless ($project->is_public)
                                            <x-ui.badge size="xs" tone="slate"><x-ui.icon name="eye-off" class="size-3" /> Internal</x-ui.badge>
                                        @endunless
                                    </div>
                                </td>
                                <td class="px-3 py-3.5"><x-ui.badge :tone="$project->status->tone()" dot>{{ $project->status->label() }}</x-ui.badge></td>
                                <td class="hidden max-w-[14rem] px-3 py-3.5 text-xs text-slate-600 lg:table-cell">
                                    <span class="line-clamp-2">{{ $project->organizations->pluck('name')->join(', ') ?: '—' }}</span>
                                </td>
                                <td class="w-40 px-3 py-3.5">
                                    <div class="flex items-center justify-between font-mono text-[11px] text-slate-500">
                                        <span>{{ Format::compact($project->beneficiaries_reached) }}</span>
                                        <span>{{ $project->progress }}%</span>
                                    </div>
                                    <x-ui.progress class="mt-1" :value="$project->progress" />
                                </td>
                                <td class="hidden px-3 py-3.5 text-right font-mono text-xs whitespace-nowrap text-slate-700 md:table-cell">{{ $project->grant_amount ? Format::moneyCompact($project->grant_amount) : '—' }}</td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    @can('update', $project)
                                        <x-ui.button :href="route('workspace.projects.edit', $project)" variant="ghost" size="xs" icon="pencil">Edit</x-ui.button>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">{{ $projects->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.workspace>
