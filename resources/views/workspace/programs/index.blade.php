<x-layouts.workspace title="Programmes">
    <x-ui.page-header title="Programmes" description="Grants, fellowships, incubation, competitions, training and events that members can apply to.">
        <x-slot:actions>
            <x-ui.button :href="route('workspace.programs.create')" icon="plus">New programme</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <form method="GET" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-card sm:grid-cols-12" role="search">
        <label class="relative sm:col-span-6">
            <span class="sr-only">Search</span>
            <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search programmes"
                   class="block w-full rounded-lg border border-slate-300 py-2 pr-3 pl-9 text-sm focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
        </label>
        <x-ui.select name="status" :options="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" :value="$filters['status']" placeholder="Any status" wrap="sm:col-span-2" aria-label="Status" />
        <x-ui.select name="type" :options="collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" :value="$filters['type']" placeholder="Any type" wrap="sm:col-span-2" aria-label="Type" />
        <x-ui.button type="submit" icon="filter" class="sm:col-span-2">Filter</x-ui.button>
    </form>

    <x-ui.card padding="p-0">
        @if ($programs->isEmpty())
            <x-ui.empty title="No programmes yet" icon="sparkles">
                Launch your first call for applications.
                <x-slot:action><x-ui.button :href="route('workspace.programs.create')" icon="plus" size="sm">New programme</x-ui.button></x-slot:action>
            </x-ui.empty>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3">Programme</th>
                            <th scope="col" class="px-3 py-3">Status</th>
                            <th scope="col" class="hidden px-3 py-3 md:table-cell">Window</th>
                            <th scope="col" class="px-3 py-3 text-right">Applications</th>
                            <th scope="col" class="px-3 py-3 text-right">To review</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($programs as $program)
                            @php [$state, $tone] = $program->availability(); @endphp
                            <tr class="hover:bg-slate-50/60">
                                <td class="max-w-md px-5 py-3.5">
                                    <a href="{{ route('workspace.programs.show', $program) }}" class="font-semibold text-slate-900 hover:text-uew-royal">{{ $program->title }}</a>
                                    <p class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-slate-500">
                                        <span class="inline-flex items-center gap-1"><x-ui.icon :name="$program->type->icon()" class="size-3.5" /> {{ $program->type->label() }}</span>
                                        @if ($program->focus_area)<span>· {{ $program->focus_area }}</span>@endif
                                        @if ($program->is_featured)<x-ui.badge size="xs" tone="gold">Featured</x-ui.badge>@endif
                                    </p>
                                </td>
                                <td class="px-3 py-3.5"><x-ui.badge :tone="$tone" dot>{{ $state }}</x-ui.badge></td>
                                <td class="hidden px-3 py-3.5 text-xs text-slate-600 md:table-cell">
                                    {{ $program->opens_at?->format('j M') ?? 'Now' }} – {{ $program->closes_at?->format('j M Y') ?? 'Rolling' }}
                                </td>
                                <td class="px-3 py-3.5 text-right font-mono font-semibold text-slate-900">
                                    {{ $program->submitted_count }}@if ($program->capacity)<span class="text-slate-400">/{{ $program->capacity }}</span>@endif
                                </td>
                                <td class="px-3 py-3.5 text-right">
                                    @if ($program->pending_count > 0)
                                        <span class="rounded-full bg-uew-gold px-2 py-0.5 font-mono text-xs font-bold text-uew-ink">{{ $program->pending_count }}</span>
                                    @else
                                        <span class="font-mono text-xs text-slate-400">0</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <x-ui.button :href="route('workspace.programs.edit', $program)" variant="ghost" size="xs" icon="pencil">Edit</x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">{{ $programs->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.workspace>
