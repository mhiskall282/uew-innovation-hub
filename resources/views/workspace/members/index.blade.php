<x-layouts.workspace title="Members">
    <x-ui.page-header title="Members" description="People with public accounts who apply to programmes.">
        <x-slot:actions>
            <x-ui.button :href="route('workspace.members.export', request()->query())" variant="secondary" icon="download">Export CSV</x-ui.button>
            @can('broadcasts.send')
                <x-ui.button :href="route('workspace.broadcasts.create')" icon="bell">New broadcast</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="Members" :value="number_format($totals['all'])" icon="users" tone="navy" />
        <x-ui.stat label="Verified" :value="number_format($totals['verified'])" icon="check-circle" tone="green" />
        <x-ui.stat label="Receive announcements" :value="number_format($totals['subscribed'])" icon="bell" tone="gold" />
        <x-ui.stat label="Joined this month" :value="number_format($totals['month'])" icon="sparkles" tone="purple" />
    </div>

    <form method="GET" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-card md:grid-cols-12" role="search">
        <label class="relative md:col-span-4">
            <span class="sr-only">Search</span>
            <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, e-mail, phone, institution"
                   class="block w-full rounded-lg border border-slate-300 py-2 pr-3 pl-9 text-sm focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
        </label>
        <x-ui.select name="affiliation" :options="$affiliations" :value="$filters['affiliation'] ?? ''" placeholder="Any affiliation" wrap="md:col-span-2" aria-label="Affiliation" />
        <x-ui.select name="interest" :options="$interests" :value="$filters['interest'] ?? ''" placeholder="Any interest" wrap="md:col-span-2" aria-label="Interest" />
        <x-ui.select name="state" :options="['subscribed' => 'Receive announcements', 'unverified' => 'Not verified', 'suspended' => 'Suspended']" :value="$filters['state'] ?? ''" placeholder="Everyone" wrap="md:col-span-2" aria-label="State" />
        <x-ui.button type="submit" icon="filter" class="md:col-span-2">Filter</x-ui.button>
    </form>

    <x-ui.card padding="p-0">
        @if ($members->isEmpty())
            <x-ui.empty title="No members found" icon="users">People appear here after they create an account on the public site.</x-ui.empty>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3">Member</th>
                            <th scope="col" class="hidden px-3 py-3 lg:table-cell">Affiliation</th>
                            <th scope="col" class="hidden px-3 py-3 xl:table-cell">Interests</th>
                            <th scope="col" class="px-3 py-3 text-right">Applications</th>
                            <th scope="col" class="px-3 py-3">Account</th>
                            <th scope="col" class="hidden px-5 py-3 md:table-cell">Joined</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($members as $member)
                            <tr @class(['hover:bg-slate-50/60', 'opacity-60' => ! $member->is_active])>
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('workspace.members.show', $member) }}" class="font-semibold text-slate-900 hover:text-uew-royal">{{ $member->name }}</a>
                                    <p class="text-xs text-slate-500">{{ $member->email }}</p>
                                </td>
                                <td class="hidden px-3 py-3.5 text-xs text-slate-600 lg:table-cell">{{ $member->affiliation?->label() ?? '—' }}<br><span class="text-slate-400">{{ $member->institution }}</span></td>
                                <td class="hidden max-w-xs px-3 py-3.5 xl:table-cell">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach (array_slice($member->interests ?? [], 0, 3) as $interest)<x-ui.badge size="xs" tone="navy">{{ $interest }}</x-ui.badge>@endforeach
                                        @if (count($member->interests ?? []) > 3)<x-ui.badge size="xs">+{{ count($member->interests) - 3 }}</x-ui.badge>@endif
                                    </div>
                                </td>
                                <td class="px-3 py-3.5 text-right font-mono font-semibold text-slate-900">{{ $member->submitted_count }}</td>
                                <td class="px-3 py-3.5">
                                    @if (! $member->is_active)
                                        <x-ui.badge tone="red" dot>Suspended</x-ui.badge>
                                    @elseif (! $member->email_verified_at)
                                        <x-ui.badge tone="gold" dot>Unverified</x-ui.badge>
                                    @elseif (! $member->onboarded_at)
                                        <x-ui.badge tone="blue" dot>Profile incomplete</x-ui.badge>
                                    @else
                                        <x-ui.badge tone="green" dot>Active</x-ui.badge>
                                    @endif
                                </td>
                                <td class="hidden px-5 py-3.5 text-xs text-slate-500 md:table-cell">{{ $member->created_at->format('j M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">{{ $members->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.workspace>
