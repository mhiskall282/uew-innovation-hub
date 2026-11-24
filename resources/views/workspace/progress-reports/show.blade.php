<x-layouts.workspace :title="$report->reference">
    <x-ui.page-header :title="$report->period" :description="($report->author?->name ?? 'Deleted account') . ' · ' . ($report->application?->program?->title ?? '') . ' · ' . $report->reference"
        :back="route('workspace.progress-reports.index')" back-label="Incubation board">
        <x-slot:actions>
            <x-ui.badge :tone="$report->status->tone()" size="md" dot>{{ $report->status->label() }}</x-ui.badge>
            @if ($report->application)
                <x-ui.button :href="route('workspace.applications.show', $report->application)" variant="secondary" size="sm" icon="inbox">Application</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            @include('reports._details')
        </div>

        <div class="space-y-6">
            @can('review', $report)
                <x-ui.card title="Review">
                    <form method="POST" action="{{ route('workspace.progress-reports.update', $report) }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <fieldset>
                            <legend class="mb-2 text-xs font-semibold text-slate-700">Decision</legend>
                            <div class="space-y-1.5">
                                @foreach ([\App\Enums\ReportStatus::Accepted, \App\Enums\ReportStatus::ChangesRequested] as $status)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm has-[:checked]:border-uew-royal has-[:checked]:bg-blue-50/60">
                                        <input type="radio" name="status" value="{{ $status->value }}" @checked(old('status', $report->status->value) === $status->value) class="size-4 border-slate-300 text-uew-navy focus:ring-uew-royal">
                                        <x-ui.badge :tone="$status->tone()" dot>{{ $status->label() }}</x-ui.badge>
                                    </label>
                                @endforeach
                            </div>
                            @error('status')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                        </fieldset>
                        <x-ui.textarea name="feedback" label="Feedback to the team" :value="$report->feedback" rows="5" hint="Required when asking for changes. The member sees this." />
                        <x-ui.button type="submit" class="w-full" icon="check">Save review</x-ui.button>
                    </form>
                    @if ($report->reviewed_at)
                        <p class="mt-4 text-xs text-slate-500">Last reviewed by {{ $report->reviewer?->name ?? 'staff' }} {{ $report->reviewed_at->diffForHumans() }}.</p>
                    @endif
                </x-ui.card>
            @endcan

            <x-ui.card title="Team member">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Name</dt><dd class="font-medium text-slate-900">{{ $report->author?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">E-mail</dt><dd class="truncate font-medium text-slate-900">{{ $report->author?->email ?? '—' }}</dd></div>
                    @if ($report->application?->program?->funding_amount)
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Programme funding</dt><dd class="font-mono font-medium text-slate-900">GH₵ {{ number_format((float) $report->application->program->funding_amount, 2) }}</dd></div>
                    @endif
                </dl>
            </x-ui.card>

            <x-ui.card title="Earlier reports" padding="p-0">
                <ul class="divide-y divide-slate-100">
                    @forelse ($earlier as $other)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <a href="{{ route('workspace.progress-reports.show', $other) }}" class="truncate text-sm text-slate-700 hover:text-uew-royal">{{ $other->period }}</a>
                            <x-ui.badge :tone="$other->status->tone()" size="xs">{{ $other->status->label() }}</x-ui.badge>
                        </li>
                    @empty
                        <li class="px-5 py-5 text-sm text-slate-500">This is the first report.</li>
                    @endforelse
                </ul>
            </x-ui.card>
        </div>
    </div>
</x-layouts.workspace>
