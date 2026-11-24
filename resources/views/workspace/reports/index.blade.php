@use('App\Support\Format')
@use('App\Support\Demographics')
<x-layouts.workspace title="Reports">
    @php
        $maxMonth = max(1, $months->max() ?? 1);
        $maxInterest = max(1, max($interests ?: [1]));
        $affTotal = max(1, $affiliations->sum());
        $gTotal = max(1, $genders->sum());
        $aTotal = max(1, $ageBands->sum());
        $money = fn ($n) => 'GH₵ ' . number_format((float) $n);
    @endphp

    <x-ui.page-header title="Reports" :description="'Programmes, membership, inclusion and incubation · ' . $range->describe()">
        <x-slot:actions>
            @can('members.manage')<x-ui.button :href="route('workspace.members.export')" variant="ghost" size="sm" icon="download">Members CSV</x-ui.button>@endcan
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Period and export toolbar --}}
    <div class="rounded-xl border border-slate-200/80 bg-white p-3 shadow-card"
         x-data="{ custom: @js($range->preset === 'custom'), exporting: false }">
        <form method="GET" action="{{ route('workspace.reports.index') }}" class="flex flex-wrap items-center gap-2" aria-label="Reporting period">
            <span class="mr-1 inline-flex items-center gap-1.5 px-1 font-mono text-[10px] font-bold tracking-widest text-slate-500 uppercase"><x-ui.icon name="calendar" class="size-4" /> Period</span>
            <div class="inline-flex flex-wrap rounded-lg bg-slate-100 p-1" role="group">
                @foreach ($presets as $key => $label)
                    @if ($key === 'custom')
                        <button type="button" @click="custom = ! custom" :aria-pressed="custom"
                                @class(['rounded-md px-3 py-1.5 text-xs font-semibold transition', 'bg-white text-uew-navy shadow-sm' => $range->preset === 'custom', 'text-slate-600 hover:text-slate-900' => $range->preset !== 'custom'])>
                            {{ $label }}
                        </button>
                    @else
                        <a href="{{ route('workspace.reports.index', ['range' => $key]) }}" @if ($range->preset === $key) aria-current="true" @endif
                           @class(['rounded-md px-3 py-1.5 text-xs font-semibold transition', 'bg-white text-uew-navy shadow-sm' => $range->preset === $key, 'text-slate-600 hover:text-slate-900' => $range->preset !== $key])>{{ $label }}</a>
                    @endif
                @endforeach
            </div>

            <div class="flex flex-wrap items-center gap-2" x-show="custom" x-cloak>
                <input type="hidden" name="range" value="custom">
                <label class="sr-only" for="range-from">From</label>
                <input id="range-from" type="date" name="from" value="{{ $range->from->toDateString() }}" max="{{ now()->toDateString() }}" min="2015-01-01" required
                       class="rounded-lg border border-slate-300 px-2.5 py-1.5 font-mono text-xs focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
                <span class="text-xs text-slate-400" aria-hidden="true">to</span>
                <label class="sr-only" for="range-to">To</label>
                <input id="range-to" type="date" name="to" value="{{ $range->to->toDateString() }}" max="{{ now()->toDateString() }}" min="2015-01-01" required
                       class="rounded-lg border border-slate-300 px-2.5 py-1.5 font-mono text-xs focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
                <x-ui.button type="submit" size="xs" icon="check">Apply</x-ui.button>
            </div>

            <div class="ml-auto flex items-center gap-2">
                <span class="hidden font-mono text-[11px] text-slate-500 lg:inline">{{ $range->describe() }}</span>
                <x-ui.button type="button" size="sm" variant="gold" icon="download" @click="exporting = ! exporting" ::aria-expanded="exporting">Export PDF</x-ui.button>
            </div>
        </form>

        {{-- Export options --}}
        <form method="GET" action="{{ route('workspace.reports.pdf') }}" x-show="exporting" x-cloak x-transition
              class="mt-3 grid gap-4 rounded-lg border border-amber-200 bg-amber-50/50 p-4 md:grid-cols-[1fr_auto]">
            @foreach ($range->query() as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <fieldset>
                <legend class="text-xs font-bold text-slate-800">Include in the PDF <span class="font-normal text-slate-500">· {{ $range->describe() }}</span></legend>
                <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($sections as $key => $label)
                        <label class="flex items-center gap-2 rounded-md bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                            <input type="checkbox" name="sections[]" value="{{ $key }}" checked class="size-4 rounded border-slate-300 text-uew-navy focus:ring-uew-royal"> {{ $label }}
                        </label>
                    @endforeach
                </div>
                <p class="mt-2 text-[11px] text-slate-500">The PDF carries the UEW crest, the period, who exported it and when. Demographics appear only as totals. Exports are recorded in the audit log.</p>
            </fieldset>
            <div class="flex items-end">
                <x-ui.button type="submit" icon="download" class="w-full md:w-auto">Download PDF</x-ui.button>
            </div>
        </form>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-ui.stat label="New members" :value="number_format($totals['members_new'])" icon="users" tone="navy" :hint="number_format($totals['members_total']) . ' in total'" />
        <x-ui.stat label="Applications" :value="number_format($totals['applications'])" icon="inbox" tone="royal" hint="Submitted in the period" />
        <x-ui.stat label="Accepted" :value="number_format($totals['approved'])" icon="check-circle" tone="green"
            :hint="$totals['acceptance'] !== null ? $totals['acceptance'] . '% of decisions' : 'No decisions yet'" />
        <x-ui.stat label="Funds disbursed" :value="Format::moneyCompact($totals['disbursed'])" icon="banknotes" tone="gold" :hint="Format::moneyCompact($totals['spent']) . ' reported spent'" />
        <x-ui.stat label="Open programmes" :value="$totals['open_programs']" icon="sparkles" tone="purple" hint="Right now" />
    </div>

    <x-ui.card title="Programmes" description="Applications submitted in the period, by programme" padding="p-0">
        @if ($programs->isEmpty())
            <x-ui.empty title="No programme activity in this period" icon="sparkles" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3">Programme</th>
                            <th scope="col" class="px-3 py-3 text-right">Applications</th>
                            <th scope="col" class="px-3 py-3 text-right">Pending</th>
                            <th scope="col" class="px-3 py-3 text-right">Accepted</th>
                            <th scope="col" class="px-3 py-3 text-right">Not selected</th>
                            <th scope="col" class="px-5 py-3">Acceptance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($programs as $row)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3">
                                    @can('applications.review')
                                        <a href="{{ route('workspace.programs.show', $row->program) }}" class="font-semibold text-slate-900 hover:text-uew-royal">{{ $row->program->title }}</a>
                                    @else
                                        <span class="font-semibold text-slate-900">{{ $row->program->title }}</span>
                                    @endcan
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
        <x-ui.card title="New members per month" :description="$range->describe()" class="xl:col-span-2">
            <div class="flex h-52 items-end gap-1.5">
                @foreach ($months as $month => $count)
                    <div class="flex min-w-0 flex-1 flex-col items-center gap-1.5" title="{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}: {{ $count }}">
                        <span class="font-mono text-[10px] font-semibold text-slate-700">{{ $count }}</span>
                        <div class="w-full rounded-t-md bg-gradient-to-t from-uew-navy to-uew-royal" style="height: {{ max(4, round($count / $maxMonth * 160)) }}px"></div>
                        <span class="font-mono text-[9px] text-slate-500">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format(count($months) > 12 ? 'M y' : 'M') }}</span>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card title="Who joined" description="Affiliation of new members">
            @if ($affiliations->isEmpty())
                <p class="text-sm text-slate-500">No completed profiles in this period.</p>
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

    <div class="grid gap-6 xl:grid-cols-3">
        <x-ui.card title="Most active members" :description="$range->isAllTime() ? 'All-time XP' : 'XP earned in the period'" padding="p-0">
            @if ($leaders->isEmpty())
                <x-ui.empty title="No XP earned in this period" icon="trophy" class="py-10" />
            @else
                <ol class="divide-y divide-slate-100">
                    @foreach ($leaders as $i => $leader)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span class="w-5 font-mono text-xs font-bold text-slate-400">{{ $i + 1 }}</span>
                            <span class="min-w-0 flex-1">
                                @can('members.manage')
                                    <a href="{{ route('workspace.members.show', $leader->user) }}" class="block truncate text-sm font-semibold text-slate-900 hover:text-uew-royal">{{ $leader->user->name }}</a>
                                @else
                                    <span class="block truncate text-sm font-semibold text-slate-900">{{ $leader->user->name }}</span>
                                @endcan
                                <span class="text-[11px] text-slate-500">{{ $leader->user->level()['name'] }} · {{ $leader->badges }} {{ Str::plural('badge', $leader->badges) }}</span>
                            </span>
                            <span class="font-mono text-sm font-bold text-uew-navy">{{ number_format($leader->xp) }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-ui.card>

        <x-ui.card title="Inclusion" description="New members, anonymous counts">
            <p class="text-xs font-semibold text-slate-500">Gender</p>
            <ul class="mt-2 space-y-2">
                @foreach (Demographics::GENDERS + ['' => 'Not given'] as $key => $label)
                    @php $n = (int) ($genders[$key] ?? 0); @endphp
                    <li>
                        <div class="mb-1 flex justify-between text-xs"><span class="text-slate-700">{{ $label }}</span><span class="font-mono text-slate-500">{{ $n }}</span></div>
                        <x-ui.progress :value="round($n / $gTotal * 100)" />
                    </li>
                @endforeach
            </ul>
            <p class="mt-5 text-xs font-semibold text-slate-500">Age</p>
            <ul class="mt-2 space-y-2">
                @foreach ($ageBands as $band => $n)
                    <li>
                        <div class="mb-1 flex justify-between text-xs"><span class="text-slate-700">{{ $band }}</span><span class="font-mono text-slate-500">{{ $n }}</span></div>
                        <x-ui.progress :value="round($n / $aTotal * 100)" tone="gold" />
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        <x-ui.card title="Regions" description="Where new members live">
            @if ($regions->isEmpty())
                <p class="text-sm text-slate-500">No regions recorded in this period.</p>
            @else
                @php $rMax = max(1, $regions->max()); @endphp
                <ul class="space-y-2">
                    @foreach ($regions->take(10) as $region => $n)
                        <li>
                            <div class="mb-1 flex justify-between text-xs"><span class="text-slate-700">{{ $region }}</span><span class="font-mono text-slate-500">{{ $n }}</span></div>
                            <x-ui.progress :value="round($n / $rMax * 100)" tone="green" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card title="Incubation budgets" description="Latest progress report per team whose reporting period ended in this range" padding="p-0">
        <x-slot:actions>
            @can('applications.review')<x-ui.button :href="route('workspace.progress-reports.index')" variant="ghost" size="xs" icon-right="arrow-right">Incubation board</x-ui.button>@endcan
        </x-slot:actions>
        @if ($budgets->isEmpty())
            <x-ui.empty title="No accepted teams yet" icon="clipboard" class="py-10" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3">Programme</th>
                            <th scope="col" class="px-3 py-3 text-right">Teams reporting</th>
                            <th scope="col" class="px-3 py-3 text-right">Funding</th>
                            <th scope="col" class="px-3 py-3 text-right">Received</th>
                            <th scope="col" class="px-3 py-3 text-right">Spent</th>
                            <th scope="col" class="px-3 py-3 text-right">People reached</th>
                            <th scope="col" class="w-40 px-5 py-3">Burn</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($budgets as $row)
                            <tr>
                                <td class="px-5 py-3 font-semibold text-slate-900">{{ $row->program->title }}</td>
                                <td class="px-3 py-3 text-right font-mono">{{ $row->reporting }}<span class="text-slate-400">/{{ $row->accepted }}</span></td>
                                <td class="px-3 py-3 text-right font-mono text-slate-500">{{ $row->funding ? number_format($row->funding) : '—' }}</td>
                                <td class="px-3 py-3 text-right font-mono">{{ number_format($row->received) }}</td>
                                <td class="px-3 py-3 text-right font-mono font-semibold">{{ number_format($row->spent) }}</td>
                                <td class="px-3 py-3 text-right font-mono">{{ number_format($row->reached) }}</td>
                                <td class="px-5 py-3">
                                    @if ($row->burn !== null)
                                        <x-ui.progress :value="min(100, $row->burn)" :tone="$row->burn > 100 ? 'red' : 'royal'" />
                                        <p @class(['mt-1 font-mono text-[11px]', 'font-semibold text-red-600' => $row->burn > 100, 'text-slate-500' => $row->burn <= 100])>{{ $row->burn }}%</p>
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

    <x-ui.card title="Member interests" description="Focus areas and opportunity types followed by new members">
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
