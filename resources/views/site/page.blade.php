<x-layouts.site :title="$page->title" :description="$page->summary">
    <header class="border-b border-slate-200 bg-uew-canvas">
        <div class="mx-auto max-w-3xl px-6 py-14">
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ $page->title }}</h1>
            @if ($page->summary)
                <p class="mt-4 text-lg leading-relaxed text-slate-600">{{ $page->summary }}</p>
            @endif
            <p class="mt-5 text-xs text-slate-400">Last updated {{ $page->updated_at->format('j F Y') }}</p>
        </div>
    </header>
    <article class="prose-uew mx-auto max-w-3xl px-6 py-12">
        {!! Str::markdown($page->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
    </article>
</x-layouts.site>
