<x-layouts.site title="Projects" description="Research and innovation projects supported by the UEW Innovation Hub.">
    <section class="border-b border-slate-200 bg-uew-canvas">
        <div class="mx-auto max-w-7xl px-6 py-14">
            <p class="text-xs font-bold tracking-widest text-uew-gold-dark uppercase">Portfolio</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Projects</h1>
            <p class="mt-3 max-w-2xl text-slate-600">Projects led by UEW researchers with funding and support from the Innovation Hub and our partners.</p>

            <form method="GET" action="{{ route('site.projects.index') }}" class="mt-8 grid gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-card sm:grid-cols-12" role="search">
                <label class="relative sm:col-span-6">
                    <span class="sr-only">Search projects</span>
                    <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by title, researcher or department"
                           class="block w-full rounded-lg border border-slate-300 py-2.5 pr-3 pl-9 text-sm focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
                </label>
                <x-ui.select name="category" :options="$focusAreas" :value="$filters['category'] ?? ''" placeholder="All focus areas" wrap="sm:col-span-3" aria-label="Focus area" />
                <x-ui.select name="status" :options="$statuses" :value="$filters['status'] ?? ''" placeholder="Any status" wrap="sm:col-span-2" aria-label="Status" />
                <x-ui.button type="submit" class="sm:col-span-1">Filter</x-ui.button>
            </form>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-6 py-12">
        @if (array_filter($filters))
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600">
                <p><span class="font-mono font-semibold text-slate-900">{{ $projects->total() }}</span> {{ Str::plural('project', $projects->total()) }} match your filters</p>
                <a href="{{ route('site.projects.index') }}" class="font-semibold text-uew-royal hover:underline">Clear filters</a>
            </div>
        @endif

        @if ($projects->isEmpty())
            <x-ui.empty title="No projects found" icon="folder">Try a different search or remove a filter.</x-ui.empty>
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($projects as $project)
                    @include('site.partials.project-card', ['project' => $project])
                @endforeach
            </div>
            <div class="mt-10">{{ $projects->links() }}</div>
        @endif
    </section>
</x-layouts.site>
