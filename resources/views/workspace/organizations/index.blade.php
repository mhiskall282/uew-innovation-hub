<x-layouts.workspace title="Partners">
    <x-ui.page-header title="Partner organisations" description="Funders, agencies and institutions linked to Hub projects.">
        <x-slot:actions>
            @can('create', App\Models\Organization::class)
                <x-ui.button :href="route('workspace.organizations.create')" icon="plus">Add partner</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <form method="GET" class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-card sm:flex-row" role="search">
        <label class="relative flex-1">
            <span class="sr-only">Search</span>
            <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-500" />
            <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Name or email"
                   class="block w-full rounded-lg border border-slate-300 py-2 pr-3 pl-9 text-sm focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
        </label>
        <x-ui.select name="type" :options="$types" :value="$filters['type']" placeholder="All types" wrap="sm:w-52" aria-label="Type" />
        <x-ui.button type="submit" icon="filter">Filter</x-ui.button>
    </form>

    <x-ui.card padding="p-0">
        @if ($organizations->isEmpty())
            <x-ui.empty title="No partners found" icon="building" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3">Organisation</th>
                            <th scope="col" class="px-3 py-3">Type</th>
                            <th scope="col" class="hidden px-3 py-3 md:table-cell">Contact</th>
                            <th scope="col" class="px-3 py-3 text-right">Projects</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($organizations as $organization)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-slate-900">{{ $organization->name }}</p>
                                    @if ($organization->website)
                                        <a href="{{ $organization->website }}" target="_blank" rel="noopener nofollow" class="text-xs text-uew-royal hover:underline">{{ parse_url($organization->website, PHP_URL_HOST) ?: $organization->website }}</a>
                                    @endif
                                </td>
                                <td class="px-3 py-3.5"><x-ui.badge tone="blue">{{ $organization->type }}</x-ui.badge></td>
                                <td class="hidden px-3 py-3.5 text-xs text-slate-600 md:table-cell">{{ $organization->contact_email ?? '—' }}</td>
                                <td class="px-3 py-3.5 text-right">
                                    <a href="{{ route('workspace.projects.index', ['organization' => $organization->id]) }}" class="font-mono text-sm font-semibold text-slate-900 hover:text-uew-royal">{{ $organization->projects_count }}</a>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    @can('update', $organization)
                                        <x-ui.button :href="route('workspace.organizations.edit', $organization)" variant="ghost" size="xs" icon="pencil">Edit</x-ui.button>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">{{ $organizations->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.workspace>
