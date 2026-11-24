<x-layouts.portal title="Progress reports" description="Accepted into a programme? Report progress, milestones and spending to the Hub here.">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card title="Your reports" padding="p-0">
                @if ($reports->isEmpty())
                    <x-ui.empty title="No reports yet" icon="clipboard">
                        @if ($accepted->isEmpty())
                            Reports open once one of your applications is accepted.
                        @else
                            Choose a programme on the right to send your first report.
                        @endif
                    </x-ui.empty>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                                <tr>
                                    <th scope="col" class="px-5 py-3">Period</th>
                                    <th scope="col" class="hidden px-3 py-3 md:table-cell">Programme</th>
                                    <th scope="col" class="px-3 py-3 text-right">Spent / received (GH₵)</th>
                                    <th scope="col" class="px-5 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($reports as $report)
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-5 py-3">
                                            <a href="{{ route('portal.reports.show', $report) }}" class="font-semibold text-slate-900 hover:text-uew-royal">{{ $report->period }}</a>
                                            <p class="font-mono text-[11px] text-slate-500">{{ $report->reference }}</p>
                                        </td>
                                        <td class="hidden px-3 py-3 text-slate-600 md:table-cell">{{ $report->application?->program?->title }}</td>
                                        <td class="px-3 py-3 text-right font-mono">{{ number_format((float) $report->budget_spent, 2) }} <span class="text-slate-400">/ {{ number_format((float) $report->budget_received, 2) }}</span></td>
                                        <td class="px-5 py-3"><x-ui.badge :tone="$report->status->tone()" dot>{{ $report->status->label() }}</x-ui.badge></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.card>
            {{ $reports->links() }}
        </div>

        <div class="space-y-6">
            <x-ui.card title="Programmes you report on" padding="p-0">
                @forelse ($accepted as $application)
                    <div class="border-b border-slate-100 px-5 py-4 last:border-0">
                        <p class="text-xs font-semibold text-uew-purple">{{ $application->program->type->label() }}</p>
                        <p class="font-semibold text-slate-900">{{ $application->program->title }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $application->progress_reports_count }} {{ Str::plural('report', $application->progress_reports_count) }} sent</p>
                        <x-ui.button :href="route('portal.reports.create', $application)" size="sm" icon="plus" class="mt-3">New report</x-ui.button>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-slate-500">None yet. <a href="{{ route('site.programs.index') }}" class="font-semibold text-uew-royal hover:underline">Find a programme</a></p>
                @endforelse
            </x-ui.card>

            <x-ui.alert tone="info" title="What goes in a report?">
                What you achieved, the milestones you hit, problems you ran into, what's next, and how the money was spent. Keep receipts; the Hub may ask for them.
            </x-ui.alert>
        </div>
    </div>
</x-layouts.portal>
