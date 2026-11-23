<x-layouts.workspace title="Publications">
    <x-ui.page-header title="Publications" description="Research papers, policy briefs, newsletters and reports shown in the public library.">
        <x-slot:actions>
            <x-ui.button :href="route('workspace.publications.create')" icon="plus">Add publication</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <form method="GET" class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-card sm:flex-row" role="search">
        <label class="relative flex-1">
            <span class="sr-only">Search</span>
            <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Title, author or journal"
                   class="block w-full rounded-lg border border-slate-300 py-2 pr-3 pl-9 text-sm focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
        </label>
        <x-ui.select name="type" :options="$types" :value="$filters['type']" placeholder="All types" wrap="sm:w-52" aria-label="Type" />
        <x-ui.button type="submit" icon="filter">Filter</x-ui.button>
    </form>

    <x-ui.card padding="p-0">
        @if ($publications->isEmpty())
            <x-ui.empty title="No publications" icon="book">
                <x-slot:action><x-ui.button :href="route('workspace.publications.create')" icon="plus" size="sm">Add a publication</x-ui.button></x-slot:action>
            </x-ui.empty>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3">Title</th>
                            <th scope="col" class="px-3 py-3">Type</th>
                            <th scope="col" class="hidden px-3 py-3 md:table-cell">Published</th>
                            <th scope="col" class="hidden px-3 py-3 text-right md:table-cell">Downloads</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($publications as $publication)
                            <tr class="hover:bg-slate-50/60">
                                <td class="max-w-md px-5 py-3.5">
                                    <p class="font-semibold text-slate-900">{{ $publication->title }}</p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">
                                        {{ $publication->authors ?? 'No authors listed' }}
                                        @if ($publication->project) · <a class="text-uew-royal hover:underline" href="{{ route('workspace.projects.show', $publication->project) }}">{{ $publication->project->title }}</a>@endif
                                    </p>
                                </td>
                                <td class="px-3 py-3.5"><x-ui.badge tone="purple">{{ $publication->type }}</x-ui.badge></td>
                                <td class="hidden px-3 py-3.5 text-xs text-slate-600 md:table-cell">{{ $publication->publish_date?->format('M Y') ?? '—' }}</td>
                                <td class="hidden px-3 py-3.5 text-right font-mono text-xs text-slate-700 md:table-cell">{{ number_format($publication->downloads_count) }}</td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    @if ($publication->hasDocument())
                                        <x-ui.button :href="route('site.documents.publication', $publication)" variant="ghost" size="xs" icon="document" target="_blank" rel="noopener">PDF</x-ui.button>
                                    @endif
                                    <x-ui.button :href="route('workspace.publications.edit', $publication)" variant="ghost" size="xs" icon="pencil">Edit</x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">{{ $publications->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.workspace>
