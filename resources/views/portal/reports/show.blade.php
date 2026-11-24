<x-layouts.portal :title="$report->period" :heading="$report->period" :description="($report->application?->program?->title ?? '') . ' · ' . $report->reference">
    <x-slot:actions>
        <x-ui.badge :tone="$report->status->tone()" size="md" dot>{{ $report->status->label() }}</x-ui.badge>
        @can('update', $report)
            <x-ui.button :href="route('portal.reports.edit', $report)" icon="pencil">Make changes</x-ui.button>
        @endcan
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @include('reports._details')
        </div>
        <div class="space-y-6">
            <x-ui.card title="Review">
                @if ($report->reviewed_at)
                    <p class="text-sm text-slate-700">{{ $report->status->label() }} by {{ $report->reviewer?->name ?? 'the Hub' }} on {{ $report->reviewed_at->format('j M Y') }}.</p>
                    @if ($report->feedback)
                        <div class="mt-4 rounded-xl border-l-4 border-uew-gold bg-amber-50/60 p-4">
                            <p class="text-xs font-semibold tracking-wider text-amber-900 uppercase">Feedback</p>
                            <p class="mt-1 text-sm whitespace-pre-line text-slate-800">{{ $report->feedback }}</p>
                        </div>
                    @endif
                @else
                    <p class="text-sm text-slate-600">Submitted {{ $report->updated_at->diffForHumans() }}. The Hub team will review it and you'll get a notification.</p>
                @endif
            </x-ui.card>
            @if ($report->application)
                <x-ui.button :href="route('portal.applications.show', $report->application)" variant="secondary" icon="arrow-left" class="w-full">Back to the application</x-ui.button>
            @endif
        </div>
    </div>
</x-layouts.portal>
