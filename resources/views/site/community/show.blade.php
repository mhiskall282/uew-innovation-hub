<x-layouts.site :title="$member->name" :description="$member->name . ' on the UEW Innovation Hub community page.'">
    @php $level = $member->level(); @endphp
    <section class="relative overflow-hidden bg-uew-ink text-white">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,rgba(49,87,213,0.45),transparent_50%),radial-gradient(circle_at_85%_70%,rgba(109,40,217,0.35),transparent_45%)]"></div>
        <div class="relative mx-auto flex max-w-5xl flex-wrap items-center gap-6 px-6 py-14">
            <span class="flex size-20 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-uew-gold to-uew-gold-light text-2xl font-extrabold text-uew-ink ring-4 ring-white/10">{{ $member->initials() }}</span>
            <div class="min-w-0 flex-1">
                <a href="{{ route('site.community.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-100/70 hover:text-white"><x-ui.icon name="arrow-left" class="size-3.5" /> Community</a>
                <h1 class="mt-1 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $member->name }}</h1>
                <p class="mt-1 text-blue-100/80">{{ $member->affiliation?->label() }}{{ $member->institution ? ' · ' . $member->institution : '' }}</p>
            </div>
            <dl class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-xl bg-white/[0.06] px-4 py-3 ring-1 ring-white/10"><dd class="font-mono text-2xl font-bold">{{ number_format($member->xp) }}</dd><dt class="text-[11px] text-blue-100/70">XP</dt></div>
                <div class="rounded-xl bg-white/[0.06] px-4 py-3 ring-1 ring-white/10"><dd class="font-mono text-2xl font-bold">{{ $rank ? '#' . $rank : '—' }}</dd><dt class="text-[11px] text-blue-100/70">Rank</dt></div>
                <div class="rounded-xl bg-white/[0.06] px-4 py-3 ring-1 ring-white/10"><dd class="font-mono text-2xl font-bold">{{ $achievements->count() }}</dd><dt class="text-[11px] text-blue-100/70">Badges</dt></div>
            </dl>
        </div>
    </section>

    <section class="mx-auto grid max-w-5xl gap-8 px-6 py-12 md:grid-cols-3">
        <div class="space-y-6 md:col-span-2">
            @if ($member->bio)
                <div>
                    <h2 class="text-xs font-bold tracking-widest text-slate-500 uppercase">About</h2>
                    <p class="mt-3 leading-relaxed whitespace-pre-line text-slate-700">{{ $member->bio }}</p>
                </div>
            @endif
            <div>
                <h2 class="text-xs font-bold tracking-widest text-slate-500 uppercase">Badges</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    @forelse ($achievements as $achievement)
                        <li class="flex gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-card">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-amber-50 text-uew-gold-dark ring-1 ring-amber-200"><x-ui.icon :name="$achievement->type->icon()" class="size-5" /></span>
                            <span class="min-w-0">
                                <x-ui.badge :tone="$achievement->type->tone()" size="xs">{{ $achievement->type->label() }}</x-ui.badge>
                                <span class="mt-1 block text-sm font-semibold text-slate-900">{{ $achievement->title }}</span>
                                @if ($achievement->description)<span class="block text-xs text-slate-600">{{ $achievement->description }}</span>@endif
                                <span class="mt-0.5 block text-[11px] text-slate-400">{{ $achievement->awarded_at->format('F Y') }} · +{{ $achievement->xp }} XP</span>
                            </span>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No badges yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <aside class="rounded-2xl bg-uew-canvas p-5 ring-1 ring-slate-200">
            <p class="text-xs font-bold tracking-widest text-uew-gold-dark uppercase">Level</p>
            <p class="mt-1 text-xl font-extrabold text-uew-navy">{{ $level['name'] }}</p>
            <x-ui.progress class="mt-3" :value="$level['progress']" tone="gold" />
            <p class="mt-2 text-xs text-slate-500">{{ $level['next'] ? number_format($level['next'] - $member->xp) . ' XP to ' . $level['next_name'] : 'Highest level reached' }}</p>
        </aside>
    </section>
</x-layouts.site>
