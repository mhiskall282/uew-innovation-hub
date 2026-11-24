<x-layouts.site title="Community" description="The most active innovators in the UEW Innovation Hub community: winners, alumni, mentors and incubatees.">
    <x-ui.page-hero eyebrow="Community" title="Innovators of the Hub" photo="monument">
        Members earn XP by applying, reporting progress and winning. Badges mark winners, finalists, incubatees, alumni and mentors. Members choose whether to appear here.
        @guest
            <x-slot:below>
                <div class="flex flex-wrap gap-3">
                    <x-ui.button :href="route('register')" variant="gold" size="lg" icon-right="arrow-right">Join the community</x-ui.button>
                    <x-ui.button :href="route('site.programs.index')" variant="light" size="lg">See programmes</x-ui.button>
                </div>
            </x-slot:below>
        @endguest
    </x-ui.page-hero>

    <section class="mx-auto grid max-w-7xl gap-8 px-6 py-12 lg:grid-cols-3">
        <div class="min-w-0 lg:col-span-2">
            <h2 class="text-xs font-bold tracking-widest text-slate-500 uppercase">Leaderboard</h2>
            @if ($leaders->isEmpty())
                <div class="mt-4 rounded-2xl border border-dashed border-slate-300 p-10 text-center">
                    <x-ui.icon name="trophy" class="mx-auto size-8 text-slate-300" />
                    <p class="mt-3 font-semibold text-slate-900">The leaderboard is waiting for its first names</p>
                    <p class="mt-1 text-sm text-slate-500">Members can switch on “Show me on the community page” in their profile settings.</p>
                </div>
            @else
                <ol class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
                    @foreach ($leaders as $i => $member)
                        @php $level = $member->level(); @endphp
                        <li class="border-b border-slate-100 last:border-0">
                            <a href="{{ route('site.community.show', $member) }}" class="flex items-center gap-3 px-4 py-4 transition hover:bg-uew-canvas/60 sm:gap-4 sm:px-5">
                                <span @class([
                                    'flex size-9 shrink-0 items-center justify-center rounded-full font-mono text-sm font-bold',
                                    'bg-gradient-to-br from-uew-gold to-uew-gold-light text-uew-ink shadow-sm shadow-uew-gold/40' => $i === 0,
                                    'bg-slate-200 text-slate-700' => $i === 1,
                                    'bg-amber-100 text-amber-800' => $i === 2,
                                    'bg-slate-100 text-slate-500' => $i > 2,
                                ])>{{ $i + 1 }}</span>
                                <span class="hidden size-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-uew-navy to-uew-royal text-sm font-bold text-white min-[420px]:flex">{{ $member->initials() }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-semibold text-slate-900">{{ $member->name }}</span>
                                    <span class="block truncate text-xs text-slate-500">{{ $member->affiliation?->label() }}{{ $member->institution ? ' · ' . $member->institution : '' }}</span>
                                </span>
                                <span class="hidden text-right sm:block">
                                    <span class="block text-xs font-semibold text-uew-purple">{{ $level['name'] }}</span>
                                    <span class="block text-[11px] text-slate-500">{{ $member->achievements_count }} {{ Str::plural('badge', $member->achievements_count) }}</span>
                                </span>
                                <span class="shrink-0 text-right font-mono text-lg font-bold text-uew-navy">{{ number_format($member->xp) }}<span class="ml-0.5 text-[11px] font-semibold text-slate-400">XP</span></span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>

        <aside class="min-w-0 space-y-6">
            <div>
                <h2 class="text-xs font-bold tracking-widest text-slate-500 uppercase">Recent badges</h2>
                <ul class="mt-4 space-y-3">
                    @forelse ($recent as $achievement)
                        <li class="flex gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-card">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-50 text-uew-gold-dark ring-1 ring-amber-200"><x-ui.icon :name="$achievement->type->icon()" class="size-5" /></span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-slate-900">{{ $achievement->user?->name }}</span>
                                <span class="block text-xs text-slate-600">{{ $achievement->title }}</span>
                                <span class="block text-[11px] text-slate-400">{{ $achievement->awarded_at->diffForHumans() }}</span>
                            </span>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No badges awarded yet.</li>
                    @endforelse
                </ul>
            </div>
            <div class="rounded-2xl bg-uew-canvas p-5 ring-1 ring-slate-200">
                <h2 class="text-sm font-bold text-uew-navy">How XP works</h2>
                <ul class="mt-3 space-y-1.5 text-sm text-slate-600">
                    <li class="flex justify-between"><span>Submit an application</span><span class="font-mono font-semibold">+{{ \App\Services\EngagementService::XP_APPLICATION }}</span></li>
                    <li class="flex justify-between"><span>Send a progress report</span><span class="font-mono font-semibold">+{{ \App\Services\EngagementService::XP_REPORT }}</span></li>
                    @foreach (\App\Enums\AchievementType::cases() as $type)
                        @continue($type === \App\Enums\AchievementType::Custom)
                        <li class="flex justify-between"><span>{{ $type->label() }} badge</span><span class="font-mono font-semibold">+{{ $type->xp() }}</span></li>
                    @endforeach
                </ul>
            </div>
        </aside>
    </section>
</x-layouts.site>
