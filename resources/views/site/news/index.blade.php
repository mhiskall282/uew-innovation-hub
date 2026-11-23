<x-layouts.site title="News" description="News, stories and announcements from the UEW Innovation Hub.">
    <section class="border-b border-slate-200 bg-uew-canvas">
        <div class="mx-auto max-w-7xl px-6 py-14">
            <p class="text-xs font-bold tracking-widest text-uew-gold-dark uppercase">Newsroom</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">News and stories</h1>
            <p class="mt-3 max-w-2xl text-slate-600">Announcements, funding calls and stories from our researchers and partners.</p>
            <nav class="mt-8 flex flex-wrap gap-2" aria-label="Filter by type">
                <a href="{{ route('site.news.index') }}" @class(['rounded-full px-3.5 py-1.5 text-xs font-semibold ring-1 ring-inset', 'bg-uew-navy text-white ring-uew-navy' => ! $activeType, 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50' => $activeType])>Everything</a>
                @foreach ($types as $type)
                    <a href="{{ route('site.news.index', ['type' => $type->value]) }}" @class(['rounded-full px-3.5 py-1.5 text-xs font-semibold ring-1 ring-inset', 'bg-uew-navy text-white ring-uew-navy' => $activeType === $type, 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50' => $activeType !== $type])>{{ Str::plural($type->label()) }}</a>
                @endforeach
            </nav>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-6 py-12">
        @if ($posts->isEmpty())
            <x-ui.empty title="Nothing published yet" icon="newspaper">Check back soon for news from the Hub.</x-ui.empty>
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    @include('site.partials.post-card', ['post' => $post])
                @endforeach
            </div>
            <div class="mt-10">{{ $posts->links() }}</div>
        @endif
    </section>
</x-layouts.site>
