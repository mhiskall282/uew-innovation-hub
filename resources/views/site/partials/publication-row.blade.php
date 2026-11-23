{{-- Expects $publication --}}
<article class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:flex-row sm:items-start">
    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-uew-navy/10 text-uew-navy">
        <x-ui.icon name="document" class="size-5" />
    </span>
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <x-ui.badge tone="purple">{{ $publication->type }}</x-ui.badge>
            @if ($publication->publish_date)
                <time datetime="{{ $publication->publish_date->toDateString() }}">{{ $publication->publish_date->format('F Y') }}</time>
            @endif
            @if ($publication->journal_name)
                <span aria-hidden="true">·</span><span class="italic">{{ $publication->journal_name }}</span>
            @endif
        </div>
        <h3 class="mt-2 text-base leading-snug font-bold text-slate-900">{{ $publication->title }}</h3>
        @if ($publication->authors)
            <p class="mt-1 text-sm text-slate-600">{{ $publication->authors }}</p>
        @endif
        @if ($publication->abstract)
            <p class="mt-2 line-clamp-2 text-sm text-slate-500">{{ $publication->abstract }}</p>
        @endif
        @if ($publication->relationLoaded('project') && $publication->project?->is_public)
            <p class="mt-2 text-xs text-slate-500">From project <a class="font-semibold text-uew-royal hover:underline" href="{{ route('site.projects.show', $publication->project) }}">{{ $publication->project->title }}</a></p>
        @endif
    </div>
    <div class="flex shrink-0 flex-wrap gap-2 sm:flex-col sm:items-end">
        @if ($publication->hasDocument())
            <x-ui.button :href="route('site.documents.publication', $publication)" size="sm" icon="download" target="_blank" rel="noopener">Read PDF</x-ui.button>
        @endif
        @if ($publication->doi_url)
            <x-ui.button :href="$publication->doi_url" variant="secondary" size="sm" icon="external" target="_blank" rel="noopener nofollow">DOI</x-ui.button>
        @endif
        <span class="font-mono text-[11px] text-slate-400">{{ number_format($publication->downloads_count) }} downloads</span>
    </div>
</article>
