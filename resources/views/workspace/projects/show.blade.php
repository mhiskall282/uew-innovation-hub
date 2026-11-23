@use('App\Support\Format')
<x-layouts.workspace :title="$project->title">
    <x-ui.page-header :title="$project->title" :back="route('workspace.projects.index')" back-label="Projects">
        <x-slot:actions>
            @if ($project->is_public)
                <x-ui.button :href="route('site.projects.show', $project)" variant="secondary" icon="globe" target="_blank" rel="noopener">View public page</x-ui.button>
            @endif
            @can('update', $project)
                <x-ui.button :href="route('workspace.projects.edit', $project)" icon="pencil">Edit</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex flex-wrap items-center gap-2">
        <x-ui.badge :tone="$project->status->tone()" size="md" dot>{{ $project->status->label() }}</x-ui.badge>
        <x-ui.badge tone="navy" size="md">{{ $project->category }}</x-ui.badge>
        @if ($project->is_public)
            <x-ui.badge tone="green" size="md"><x-ui.icon name="eye" class="size-3.5" /> Public</x-ui.badge>
        @else
            <x-ui.badge size="md"><x-ui.icon name="eye-off" class="size-3.5" /> Internal only</x-ui.badge>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="Beneficiaries reached" :value="number_format($project->beneficiaries_reached)" icon="users" tone="green" :hint="'Target ' . number_format($project->beneficiaries_target)">
            <x-ui.progress class="mt-3" :value="$project->progress" tone="green" />
        </x-ui.stat>
        <x-ui.stat label="Funding" :value="$project->grant_amount ? Format::moneyCompact($project->grant_amount) : '—'" icon="banknotes" tone="gold" :hint="$project->grant_amount ? Format::money($project->grant_amount) : 'Not recorded'" />
        <x-ui.stat label="Timeline" :value="$project->start_date?->format('M Y') ?? '—'" icon="calendar" tone="royal" :hint="'Ends ' . ($project->end_date?->format('j M Y') ?? 'not set')" />
        <x-ui.stat label="Partners" :value="$project->organizations->count()" icon="building" tone="purple" :hint="$project->publications->count() . ' linked publications'" />
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.card title="Summary">
                <p class="text-sm leading-relaxed whitespace-pre-line text-slate-700">{{ $project->summary ?: 'No summary yet.' }}</p>
                <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-5 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-500">Lead researcher</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $project->lead_researcher ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Department</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $project->department ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Created by</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $project->creator?->name ?? 'Imported' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Last updated</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $project->updated_at?->format('j M Y, H:i') }}</dd></div>
                </dl>
            </x-ui.card>

            <x-ui.card title="Partners" padding="p-0">
                @if ($project->organizations->isEmpty())
                    <x-ui.empty title="No partners linked" icon="building" class="py-8" />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($project->organizations as $partner)
                            <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                                <span class="font-medium text-slate-900">{{ $partner->name }}</span>
                                <x-ui.badge tone="blue">{{ $partner->pivot->role }}</x-ui.badge>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            @if ($project->publications->isNotEmpty())
                <x-ui.card title="Publications" padding="p-0">
                    <ul class="divide-y divide-slate-100">
                        @foreach ($project->publications as $publication)
                            <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                                <span class="min-w-0 truncate font-medium text-slate-900">{{ $publication->title }}</span>
                                <x-ui.badge tone="purple">{{ $publication->type }}</x-ui.badge>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif
        </div>

        <div class="space-y-6">
            <x-ui.card title="Project report">
                @if ($project->report_file_path)
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-lg bg-red-50 text-red-600"><x-ui.icon name="document" class="size-5" /></span>
                        <div class="min-w-0 flex-1 text-sm">
                            <p class="font-semibold text-slate-900">Report on file</p>
                            <p class="text-xs text-slate-500">{{ $project->is_public ? 'Downloadable from the public page' : 'Visible to staff only' }}</p>
                        </div>
                    </div>
                    <x-ui.button :href="route('workspace.projects.report', $project)" class="mt-4 w-full" variant="secondary" icon="download" target="_blank" rel="noopener">Open report</x-ui.button>
                @else
                    <p class="text-sm text-slate-500">No report uploaded yet.</p>
                @endif
            </x-ui.card>

            <x-ui.card title="History" padding="p-0">
                <ol class="divide-y divide-slate-100">
                    @forelse ($history as $entry)
                        <li class="px-5 py-3">
                            <p class="text-xs text-slate-700">{{ $entry->description }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">{{ $entry->user?->name ?? 'System' }} · <time datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('j M Y, H:i') }}</time></p>
                            @if (! empty($entry->properties))
                                <details class="mt-1.5 text-[11px] text-slate-500">
                                    <summary class="cursor-pointer font-semibold text-uew-royal">{{ count($entry->properties) }} {{ Str::plural('field', count($entry->properties)) }} changed</summary>
                                    <ul class="mt-1 space-y-0.5 font-mono">
                                        @foreach ($entry->properties as $field => $change)
                                            <li>{{ $field }}@if (is_array($change) && array_key_exists('to', $change)): {{ Str::limit(is_scalar($change['from']) ? (string) $change['from'] : json_encode($change['from']), 40) }} → {{ Str::limit(is_scalar($change['to']) ? (string) $change['to'] : json_encode($change['to']), 40) }}@endif</li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-slate-500">No recorded changes yet.</li>
                    @endforelse
                </ol>
            </x-ui.card>

            @can('delete', $project)
                <x-ui.card title="Archive project">
                    <p class="text-sm text-slate-600">Archiving hides the project everywhere. The record and report are kept and can be restored from the database.</p>
                    <x-ui.confirm-form class="mt-4 block" :action="route('workspace.projects.destroy', $project)" :message="'Archive ' . $project->title . '?'" label="Archive project" variant="danger" />
                </x-ui.card>
            @endcan
        </div>
    </div>
</x-layouts.workspace>
