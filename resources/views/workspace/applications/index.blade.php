<x-layouts.workspace title="Applications">
    <x-ui.page-header title="Applications" description="Submitted applications across all programmes. Open a programme to filter, bulk-decide and export." />

    <nav class="flex flex-wrap gap-2" aria-label="Filter by status">
        <a href="{{ route('workspace.applications.index') }}" @class(['rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 ring-inset', 'bg-uew-navy text-white ring-uew-navy' => ! $activeStatus, 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' => $activeStatus])>Waiting for a decision</a>
        @foreach ($statuses as $status)
            <a href="{{ route('workspace.applications.index', ['status' => $status->value]) }}" @class(['rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 ring-inset', 'bg-uew-navy text-white ring-uew-navy' => $activeStatus === $status, 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' => $activeStatus !== $status])>{{ $status->label() }}</a>
        @endforeach
    </nav>

    <x-ui.card padding="p-0">
        @if ($applications->isEmpty())
            <x-ui.empty title="Nothing waiting" icon="check-circle">New submissions appear here.</x-ui.empty>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3">Applicant</th>
                            <th scope="col" class="px-3 py-3">Programme</th>
                            <th scope="col" class="px-3 py-3">Status</th>
                            <th scope="col" class="hidden px-5 py-3 md:table-cell">Submitted</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($applications as $application)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('workspace.applications.show', $application) }}" class="font-semibold text-slate-900 hover:text-uew-royal">{{ $application->applicant?->name ?? 'Deleted account' }}</a>
                                    <p class="font-mono text-[11px] text-slate-500">{{ $application->reference }}</p>
                                </td>
                                <td class="px-3 py-3.5">
                                    <a href="{{ route('workspace.programs.show', $application->program) }}" class="text-slate-700 hover:text-uew-royal">{{ $application->program->title }}</a>
                                    <p class="text-xs text-slate-500">{{ $application->program->type->label() }}</p>
                                </td>
                                <td class="px-3 py-3.5"><x-ui.badge :tone="$application->status->tone()" dot>{{ $application->status->label() }}</x-ui.badge></td>
                                <td class="hidden px-5 py-3.5 text-xs text-slate-500 md:table-cell" title="{{ $application->submitted_at?->format('j M Y, H:i') }}">{{ $application->submitted_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">{{ $applications->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.workspace>
