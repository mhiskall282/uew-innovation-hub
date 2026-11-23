{{-- Expects $program --}}
@php [$state, $tone] = $program->availability(); @endphp
<article class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card transition hover:-translate-y-0.5 hover:shadow-card-hover">
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-gradient-to-r from-uew-navy to-uew-royal px-5 py-3 text-white">
        <span class="inline-flex items-center gap-2 text-xs font-bold tracking-wider uppercase">
            <x-ui.icon :name="$program->type->icon()" class="size-4 text-uew-gold-light" /> {{ $program->type->label() }}
        </span>
        <x-ui.badge :tone="$tone" dot>{{ $state }}</x-ui.badge>
    </div>
    <div class="flex flex-1 flex-col p-6">
        @if ($program->focus_area)
            <p class="text-xs font-semibold text-uew-purple">{{ $program->focus_area }}</p>
        @endif
        <h3 class="mt-1 text-lg leading-snug font-bold text-slate-900 group-hover:text-uew-royal">
            <a href="{{ route('site.programs.show', $program) }}" class="after:absolute after:inset-0">{{ $program->title }}</a>
        </h3>
        <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-slate-600">{{ $program->summary }}</p>

        <dl class="mt-auto grid grid-cols-2 gap-3 border-t border-slate-100 pt-5 text-xs">
            <div>
                <dt class="text-slate-500">{{ $program->type->isEvent() ? 'Takes place' : 'Deadline' }}</dt>
                <dd class="mt-0.5 font-semibold text-slate-900">
                    @if ($program->type->isEvent() && $program->starts_at)
                        {{ $program->starts_at->format('j M Y, H:i') }}
                    @else
                        {{ $program->closes_at?->format('j M Y') ?? 'Rolling' }}
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-slate-500">{{ $program->funding_amount ? 'Funding up to' : 'Where' }}</dt>
                <dd class="mt-0.5 truncate font-semibold text-slate-900">{{ $program->funding_amount ? \App\Support\Format::moneyCompact($program->funding_amount) : ($program->location ?? 'UEW, Winneba') }}</dd>
            </div>
        </dl>
        @if ($program->closes_at && ! $program->hasClosed() && $program->closes_at->diffInDays(now(), true) <= 7)
            <p class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-red-600"><x-ui.icon name="clock" class="size-3.5" /> Closes {{ $program->closes_at->diffForHumans() }}</p>
        @endif
    </div>
</article>
