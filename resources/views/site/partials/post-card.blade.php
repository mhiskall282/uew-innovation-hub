{{-- Expects $post --}}
<article class="group relative flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-card transition hover:shadow-card-hover">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <x-ui.badge :tone="$post->type->tone()">{{ $post->type->label() }}</x-ui.badge>
        <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('j M Y') }}</time>
        <span aria-hidden="true">·</span>
        <span>{{ $post->readingMinutes() }} min read</span>
    </div>
    <h3 class="mt-4 text-lg leading-snug font-bold text-slate-900 group-hover:text-uew-royal">
        <a href="{{ route('site.news.show', $post) }}" class="after:absolute after:inset-0">{{ $post->title }}</a>
    </h3>
    <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-slate-600">{{ $post->summary ?? Str::limit(strip_tags($post->content), 160) }}</p>
    <p class="mt-auto pt-5 text-xs font-semibold text-uew-royal">Read article <span aria-hidden="true">→</span></p>
</article>
