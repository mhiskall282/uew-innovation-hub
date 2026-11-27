<x-layouts.site title="Publications" description="Research papers, policy briefs, newsletters and reports from the UEW Innovation Hub.">
    <x-ui.page-hero eyebrow="Library" title="Publications" photo="creative-arts">
        Research papers, policy briefs, newsletters and reports from Hub projects. PDFs are free to read and share.
        <x-slot:below>
            <form method="GET" class="flex max-w-3xl flex-col gap-3 text-slate-800 sm:flex-row" role="search">
                @if ($activeType)<input type="hidden" name="type" value="{{ $activeType }}">@endif
                <label class="relative flex-1">
                    <span class="sr-only">Search publications</span>
                    <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-500" />
                    <input type="search" name="search" value="{{ $search }}" placeholder="Search by title, author or journal"
                           class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pr-3 pl-9 text-sm focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
                </label>
                <x-ui.button type="submit">Search</x-ui.button>
            </form>

            <nav class="mt-5 flex flex-wrap gap-2" aria-label="Filter by type">
                <a href="{{ route('site.publications.index', array_filter(['search' => $search])) }}" @class(['rounded-full px-3.5 py-1.5 text-xs font-semibold ring-1 ring-inset', 'bg-uew-navy text-white ring-uew-navy' => ! $activeType, 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50' => $activeType])>All</a>
                @foreach ($types as $type)
                    <a href="{{ route('site.publications.index', array_filter(['type' => $type, 'search' => $search])) }}" @class(['rounded-full px-3.5 py-1.5 text-xs font-semibold ring-1 ring-inset', 'bg-uew-navy text-white ring-uew-navy' => $activeType === $type, 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50' => $activeType !== $type])>{{ $type }}</a>
                @endforeach
            </nav>
        </x-slot:below>
    </x-ui.page-hero>

    <section class="mx-auto max-w-5xl px-6 py-12">
        @if ($publications->isEmpty())
            <x-ui.empty title="No publications found" icon="book">Try another type or search term.</x-ui.empty>
        @else
            <div class="space-y-4">
                @foreach ($publications as $publication)
                    @include('site.partials.publication-row', ['publication' => $publication])
                @endforeach
            </div>
            <div class="mt-10">{{ $publications->links() }}</div>
        @endif
    </section>
</x-layouts.site>
