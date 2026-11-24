{{-- Progress report body, shared by the portal and workspace views. Expects $report --}}
@php
    $burn = $report->burnRate();
    $balance = $report->balance();
@endphp
<div class="grid gap-4 sm:grid-cols-3">
    <x-ui.stat label="Funds received" :value="'GH₵ ' . number_format((float) $report->budget_received, 2)" icon="banknotes" tone="navy" />
    <x-ui.stat label="Spent" :value="'GH₵ ' . number_format((float) $report->budget_spent, 2)" icon="chart" :tone="$balance < 0 ? 'gold' : 'royal'" :hint="$burn !== null ? $burn . '% of funds received' : null" />
    <x-ui.stat label="Balance" :value="'GH₵ ' . number_format($balance, 2)" icon="check-circle" :tone="$balance < 0 ? 'gold' : 'green'" :hint="$balance < 0 ? 'Overspent' : null" />
</div>

<x-ui.card title="Progress">
    <dl class="space-y-5 text-sm">
        @foreach (['Summary' => $report->summary, 'Milestones reached' => $report->milestones, 'Challenges' => $report->challenges, 'Next steps' => $report->next_steps] as $label => $text)
            @if (filled($text))
                <div><dt class="text-xs font-semibold text-slate-500">{{ $label }}</dt><dd class="mt-1 leading-relaxed whitespace-pre-line text-slate-900">{{ $text }}</dd></div>
            @endif
        @endforeach
        @if ($report->beneficiaries !== null)
            <div><dt class="text-xs font-semibold text-slate-500">People reached so far</dt><dd class="mt-1 font-mono text-lg font-bold text-uew-navy">{{ number_format($report->beneficiaries) }}</dd></div>
        @endif
    </dl>
</x-ui.card>

@if (! empty($report->line_items))
    <x-ui.card title="Spending lines" padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                    <tr>
                        <th scope="col" class="px-5 py-3">Category</th>
                        <th scope="col" class="px-3 py-3">Item</th>
                        <th scope="col" class="px-3 py-3 text-right">Budgeted</th>
                        <th scope="col" class="px-5 py-3 text-right">Spent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($report->line_items as $item)
                        <tr>
                            <td class="px-5 py-2.5 text-xs text-slate-500">{{ $item['category'] }}</td>
                            <td class="px-3 py-2.5 text-slate-900">{{ $item['description'] }}</td>
                            <td class="px-3 py-2.5 text-right font-mono">{{ number_format((float) $item['budgeted'], 2) }}</td>
                            <td @class(['px-5 py-2.5 text-right font-mono', 'text-red-600' => (float) $item['spent'] > (float) $item['budgeted'] && (float) $item['budgeted'] > 0])>{{ number_format((float) $item['spent'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-slate-200 bg-slate-50/60 font-mono text-sm font-semibold">
                    <tr>
                        <td colspan="2" class="px-5 py-2.5 text-right font-sans text-xs text-slate-500">Totals (GH₵)</td>
                        <td class="px-3 py-2.5 text-right">{{ number_format(array_sum(array_column($report->line_items, 'budgeted')), 2) }}</td>
                        <td class="px-5 py-2.5 text-right">{{ number_format(array_sum(array_column($report->line_items, 'spent')), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-ui.card>
@endif
