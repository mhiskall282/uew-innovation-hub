{{-- Expects $project (with organizations loaded) --}}
<article class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card transition hover:-translate-y-0.5 hover:shadow-card-hover">
    <div class="h-1.5 bg-gradient-to-r from-uew-navy via-uew-royal to-uew-purple"></div>
    <div class="flex flex-1 flex-col p-6">
        <div class="flex items-center justify-between gap-3">
            <x-ui.badge tone="navy">{{ $project->category }}</x-ui.badge>
            <x-ui.badge :tone="$project->status->tone()" dot>{{ $project->status->label() }}</x-ui.badge>
        </div>
        <h3 class="mt-4 text-lg leading-snug font-bold text-slate-900 group-hover:text-uew-royal">
            <a href="{{ route('site.projects.show', $project) }}" class="after:absolute after:inset-0 focus:outline-none">{{ $project->title }}</a>
        </h3>
        <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-slate-600">{{ $project->summary }}</p>

        <div class="mt-auto pt-6">
            <x-ui.progress :value="$project->progress" :label="number_format($project->beneficiaries_reached) . ' beneficiaries reached'" />
            <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-4 text-xs text-slate-500">
                <span class="truncate">{{ $project->lead_researcher ?? 'UEW Innovation Hub' }}</span>
                @if ($project->organizations->isNotEmpty())
                    <span class="shrink-0 font-medium">{{ $project->organizations->count() }} {{ Str::plural('partner', $project->organizations->count()) }}</span>
                @endif
            </div>
        </div>
    </div>
</article>
