<x-layouts.workspace title="Incubation board">
    <x-ui.page-header title="Incubation board" description="Progress and budget reports from accepted incubatees, grantees and fellows, with spending against funding per programme.">
        @can('reports.view')
            <x-slot:actions>
                <x-ui.button :href="route('workspace.reports.index', ['range' => '12m'])" variant="secondary" size="sm" icon="chart">Reports by period</x-ui.button>
                <x-ui.button :href="route('workspace.reports.pdf', ['range' => 'all', 'sections' => ['incubation']])" variant="gold" size="sm" icon="download">Budget PDF</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    @php
        $totalReceived = $budgets->sum('received');
        $totalSpent = $budgets->sum('spent');
    @endphp
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="Awaiting review" :value="$awaiting" icon="clipboard" tone="royal" :href="route('workspace.progress-reports.index', ['status' => 'submitted'])" />
        <x-ui.stat label="Teams in programmes" :value="$budgets->sum('accepted')" icon="users" tone="purple" :hint="$budgets->sum('reporting') . ' reporting'" />
        <x-ui.stat label="Funds disbursed" :value="'GH₵ ' . number_format($totalReceived)" icon="banknotes" tone="navy" />
        <x-ui.stat label="Funds spent" :value="'GH₵ ' . number_format($totalSpent)" icon="chart" tone="gold" :hint="$totalReceived > 0 ? round($totalSpent / $totalReceived * 100) . '% of disbursed' : null" />
    </div>

    <x-ui.card title="Spending by programme" description="Latest report from each team. Funding is the amount set on the programme." padding="p-0">
        @if ($budgets->isEmpty())
            <x-ui.empty title="No accepted applicants yet" icon="sparkles">Teams appear here once their application is accepted.</x-ui.empty>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3">Programme</th>
                            <th scope="col" class="px-3 py-3 text-right">Teams</th>
                            <th scope="col" class="px-3 py-3 text-right">Reports</th>
                            <th scope="col" class="px-3 py-3 text-right">Funding</th>
                            <th scope="col" class="px-3 py-3 text-right">Received</th>
                            <th scope="col" class="px-3 py-3 text-right">Spent</th>
                            <th scope="col" class="w-44 px-5 py-3">Burn</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($budgets as $row)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3">
                                    <a href="{{ route('workspace.progress-reports.index', ['programme' => $row->program->id]) }}" class="font-semibold text-slate-900 hover:text-uew-royal">{{ $row->program->title }}</a>
                                    <p class="text-xs text-slate-500">{{ $row->program->type->label() }}</p>
                                </td>
                                <td class="px-3 py-3 text-right font-mono">{{ $row->reporting }}<span class="text-slate-400">/{{ $row->accepted }}</span></td>
                                <td class="px-3 py-3 text-right font-mono">{{ $row->reports }}</td>
                                <td class="px-3 py-3 text-right font-mono text-slate-500">{{ $row->funding ? number_format($row->funding) : '—' }}</td>
                                <td class="px-3 py-3 text-right font-mono">{{ number_format($row->received) }}</td>
                                <td class="px-3 py-3 text-right font-mono font-semibold">{{ number_format($row->spent) }}</td>
                                <td class="px-5 py-3">
                                    @if ($row->burn !== null)
                                        <x-ui.progress :value="min(100, $row->burn)" :tone="$row->burn > 100 ? 'red' : 'royal'" />
                                        <p @class(['mt-1 font-mono text-[11px]', 'text-red-600 font-semibold' => $row->burn > 100, 'text-slate-500' => $row->burn <= 100])>{{ $row->burn }}%</p>
                                    @else
                                        <span class="text-xs text-slate-400">No reports</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="Reports" padding="p-0">
        <x-slot:actions>
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <label class="sr-only" for="filter-status">Status</label>
                <select id="filter-status" name="status" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs focus:border-uew-royal focus:outline-none">
                    <option value="">Any status</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}" @selected($activeStatus === $s)>{{ $s->label() }}</option>
                    @endforeach
                </select>
                <label class="sr-only" for="filter-programme">Programme</label>
                <select id="filter-programme" name="programme" class="max-w-48 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs focus:border-uew-royal focus:outline-none">
                    <option value="">All programmes</option>
                    @foreach ($programmes as $id => $title)
                        <option value="{{ $id }}" @selected($activeProgramme === $id)>{{ $title }}</option>
                    @endforeach
                </select>
                <x-ui.button type="submit" size="xs" variant="secondary" icon="filter">Filter</x-ui.button>
            </form>
        </x-slot:actions>

        @if ($reports->isEmpty())
            <x-ui.empty title="No reports match" icon="clipboard" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3">Team member</th>
                            <th scope="col" class="px-3 py-3">Period</th>
                            <th scope="col" class="hidden px-3 py-3 lg:table-cell">Programme</th>
                            <th scope="col" class="px-3 py-3 text-right">Spent / received</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($reports as $report)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3">
                                    <a href="{{ route('workspace.progress-reports.show', $report) }}" class="font-semibold text-slate-900 hover:text-uew-royal">{{ $report->author?->name ?? 'Deleted account' }}</a>
                                    <p class="font-mono text-[11px] text-slate-500">{{ $report->reference }}</p>
                                </td>
                                <td class="px-3 py-3 text-slate-700">{{ $report->period }}</td>
                                <td class="hidden px-3 py-3 text-slate-600 lg:table-cell">{{ $report->application?->program?->title }}</td>
                                <td class="px-3 py-3 text-right font-mono">{{ number_format((float) $report->budget_spent) }} <span class="text-slate-400">/ {{ number_format((float) $report->budget_received) }}</span></td>
                                <td class="px-5 py-3"><x-ui.badge :tone="$report->status->tone()" dot>{{ $report->status->label() }}</x-ui.badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
    {{ $reports->links() }}
</x-layouts.workspace>
