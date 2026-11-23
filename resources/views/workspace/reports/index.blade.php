@use('App\Support\Format')
<x-layouts.workspace title="Reports">
    @php
        $maxMonth = max(1, $months->max());
        $maxInterest = max(1, max($interests ?: [1]));
        $affTotal = max(1, $affiliations->sum());
    @endphp

    <x-ui.page-header title="Reports" description="Programmes, applications, membership and the project portfolio at a glance.">
        <x-slot:actions>
            @can('members.manage')<x-ui.button :href="route('workspace.members.export')" variant="secondary" icon="download">Members CSV</x-ui.button>@endcan
            @can('reports.export')<x-ui.button :href="route('workspace.projects.export')" variant="secondary" icon="download">Portfolio CSV</x-ui.button>@endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-ui.stat label="Members" :value="number_format($totals['members'])" icon="users" tone="navy" :hint="number_format($totals['verified']) . ' verified'" />
        <x-ui.stat label="Open programmes" :value="$totals['open_programs']" icon="sparkles" tone="purple" />
        <x-ui.stat label="Applications" :value="number_format($totals['applications'])" icon="inbox" tone="royal" />
        <x-ui.stat label="Accepted" :value="number_format($totals['approved'])" icon="check-circle" tone="green"
            :hint="$totals['applications'] ? round($totals['approved'] / $totals['applications'] * 100) . '% acceptance' : null" />
        <x-ui.stat label="Portfolio reach" :value="Format::compact($portfolio['beneficiaries']['reached'])" icon="globe" tone="gold" :hint="$portfolio['projects']['total'] . ' projects'" />
    </div>

    <x-ui.card title="Programmes" description="Submitted applications and outcomes per programme" padding="p-0">
        @if ($programs->isEmpty())
            <x-ui.empty title="No programmes yet" icon="sparkles" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th class="px-5 py-3">Programme</th>
                            <th class="px-3 py-3 text-right">Applications</th>
                            <th class="px-3 py-3 text-right">Pending</th>
                            <th class="px-3 py-3 text-right">Accepted</th>
                            <th class="px-3 py-3 text-right">Not selected</th>
                            <th class="px-5 py-3">Acceptance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($programs as $row)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3">
                                    <a href="{{ route('workspace.programs.show', $row->program) }}" class="font-semibold text-slate-900 hover:text-uew-royal">{{ $row->program->title }}</a>
                                    <p class="text-xs text-slate-500">{{ $row->program->type->label() }} · {{ $row->program->status->label() }}</p>
                                </td>
                                <td class="px-3 py-3 text-right font-mono font-semibold">{{ $row->total }}@if ($row->program->capacity)<span class="text-slate-400">/{{ $row->program->capacity }}</span>@endif</td>
                                <td class="px-3 py-3 text-right font-mono">{{ $row->pending }}</td>
                                <td class="px-3 py-3 text-right font-mono text-emerald-700">{{ $row->approved }}</td>
                                <td class="px-3 py-3 text-right font-mono text-slate-500">{{ $row->rejected }}</td>
                                <td class="w-44 px-5 py-3">@if ($row->acceptance !== null)<x-ui.progress :value="$row->acceptance" tone="green" />@else<span class="text-xs text-slate-400">—</span>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    <div class="grid gap-6 xl:grid-cols-3">
        <x-ui.card title="New members per month" description="Last 12 months" class="xl:col-span-2">
            <div class="flex h-52 items-end gap-2">
                @foreach ($months as $month => $count)
                    <div class="flex flex-1 flex-col items-center gap-1.5">
                        <span class="font-mono text-[11px] font-semibold text-slate-700">{{ $count }}</span>
                        <div class="w-full rounded-t-md bg-gradient-to-t from-uew-navy to-uew-royal" style="height: {{ max(4, round($count / $maxMonth * 160)) }}px"></div>
                        <span class="font-mono text-[10px] text-slate-500">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('M') }}</span>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card title="Who our members are">
            @if ($affiliations->isEmpty())
                <p class="text-sm text-slate-500">No profiles completed yet.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($affiliations as $affiliation => $count)
                        <li>
                            <div class="mb-1 flex justify-between text-xs"><span class="font-medium text-slate-700">{{ \App\Enums\Affiliation::tryFrom($affiliation)?->label() ?? $affiliation }}</span><span class="font-mono text-slate-500">{{ $count }}</span></div>
                            <x-ui.progress :value="round($count / $affTotal * 100)" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card title="Member interests" description="How many members follow each focus area and type of opportunity">
        <div class="grid gap-x-8 gap-y-3 md:grid-cols-2">
            @foreach ($interests as $interest => $count)
                <div>
                    <div class="mb-1 flex justify-between text-xs"><span class="font-medium text-slate-700">{{ $interest }}</span><span class="font-mono text-slate-500">{{ $count }}</span></div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-gradient-to-r from-uew-purple to-uew-royal-light" style="width: {{ round($count / $maxInterest * 100) }}%"></div></div>
                </div>
            @endforeach
        </div>
    </x-ui.card>
</x-layouts.workspace>
