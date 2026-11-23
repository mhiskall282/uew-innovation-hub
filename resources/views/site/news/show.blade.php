<x-layouts.site :title="$post->title" :description="$post->summary">
    <article>
        <header class="border-b border-slate-200 bg-uew-canvas">
            <div class="mx-auto max-w-3xl px-6 py-14">
                <a href="{{ route('site.news.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-uew-navy">
                    <x-ui.icon name="arrow-left" class="size-3.5" /> All news
                </a>
                <div class="mt-5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    <x-ui.badge :tone="$post->type->tone()">{{ $post->type->label() }}</x-ui.badge>
                    <x-ui.badge>{{ $post->category }}</x-ui.badge>
                </div>
                <h1 class="mt-4 text-3xl leading-tight font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ $post->title }}</h1>
                @if ($post->summary)
                    <p class="mt-4 text-lg leading-relaxed text-slate-600">{{ $post->summary }}</p>
                @endif
                <p class="mt-6 text-sm text-slate-500">
                    By <span class="font-semibold text-slate-700">{{ $post->author?->name ?? 'UEW Innovation Hub' }}</span>
                    · <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->format('j F Y') }}</time>
                    · {{ $post->readingMinutes() }} min read
                </p>
            </div>
        </header>

        {{-- Markdown with raw HTML escaped and unsafe links disabled. --}}
        <div class="prose-uew mx-auto max-w-3xl px-6 py-12">
            {!! Str::markdown($post->content, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="border-t border-slate-200 bg-uew-canvas py-14">
            <div class="mx-auto max-w-7xl px-6">
                <h2 class="text-xl font-bold text-slate-900">Related</h2>
                <div class="mt-6 grid gap-6 md:grid-cols-3">
                    @foreach ($related as $item)
                        @include('site.partials.post-card', ['post' => $item])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.site>
