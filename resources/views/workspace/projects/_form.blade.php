{{-- Shared create/edit form. Expects $project, $statuses, $focusAreas, $partnerRoles, $organizations, $linked --}}
@php
    $oldPartners = collect(old('partners', []));
    $isLinked = fn ($orgId) => $oldPartners->isNotEmpty()
        ? $oldPartners->contains(fn ($row) => (string) ($row['organization_id'] ?? '') === (string) $orgId)
        : array_key_exists($orgId, $linked);
    $roleFor = fn ($orgId, $index) => old("partners.{$index}.role", $linked[$orgId] ?? 'Partner');
@endphp

<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <x-ui.card title="Project details">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.input name="title" label="Title" :value="$project->title" required wrap="sm:col-span-2" />
                <x-ui.select name="category" label="Focus area" :options="$focusAreas" :value="$project->category" placeholder="Choose a focus area" required />
                <x-ui.select name="status" label="Status" :options="$statuses" :value="$project->status" required />
                <x-ui.textarea name="summary" label="Summary" :value="$project->summary" rows="6" wrap="sm:col-span-2" hint="Shown on the public project page. Plain text; blank lines start new paragraphs." />
                <x-ui.input name="lead_researcher" label="Lead researcher" :value="$project->lead_researcher" />
                <x-ui.input name="department" label="Department" :value="$project->department" />
            </div>
        </x-ui.card>

        <x-ui.card title="Timeline, reach and funding">
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <x-ui.input name="start_date" type="date" label="Start date" :value="$project->start_date?->format('Y-m-d')" />
                <x-ui.input name="end_date" type="date" label="End date" :value="$project->end_date?->format('Y-m-d')" />
                <x-ui.input name="grant_amount" type="number" step="0.01" min="0" label="Funding (GH₵)" :value="$project->grant_amount" inputmode="decimal" />
                <x-ui.input name="beneficiaries_target" type="number" min="0" label="Beneficiaries targeted" :value="$project->beneficiaries_target" />
                <x-ui.input name="beneficiaries_reached" type="number" min="0" label="Beneficiaries reached so far" :value="$project->beneficiaries_reached" />
            </div>
        </x-ui.card>

        <x-ui.card title="Partners" description="Tick each organisation involved and choose its role.">
            @if ($organizations->isEmpty())
                <p class="text-sm text-slate-500">No partner organisations yet. @can('partners.manage')<a class="font-semibold text-uew-royal hover:underline" href="{{ route('workspace.organizations.create') }}">Add one</a>.@endcan</p>
            @else
                <div x-data="{ filter: '' }">
                    <input type="search" x-model="filter" placeholder="Filter organisations" aria-label="Filter organisations"
                           class="mb-3 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
                    <ul class="max-h-96 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200">
                        @foreach ($organizations as $index => $organization)
                            <li class="flex flex-wrap items-center gap-3 px-3 py-2.5"
                                x-data="{ on: @js($isLinked($organization->id)) }"
                                x-show="filter === '' || @js(Str::lower($organization->name)).includes(filter.toLowerCase())">
                                <label class="flex min-w-0 flex-1 items-center gap-3 text-sm">
                                    <input type="checkbox" name="partners[{{ $index }}][organization_id]" value="{{ $organization->id }}" x-model="on"
                                           class="size-4 rounded border-slate-300 text-uew-navy focus:ring-uew-royal">
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-slate-900">{{ $organization->name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $organization->type }}</span>
                                    </span>
                                </label>
                                <select name="partners[{{ $index }}][role]" x-bind:disabled="! on" aria-label="Role for {{ $organization->name }}"
                                        class="w-48 rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs disabled:bg-slate-50 disabled:text-slate-400">
                                    @foreach ($partnerRoles as $role)
                                        <option value="{{ $role }}" @selected($roleFor($organization->id, $index) === $role)>{{ $role }}</option>
                                    @endforeach
                                </select>
                            </li>
                        @endforeach
                    </ul>
                    @error('partners')<p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    @foreach ($errors->get('partners.*') as $messages)
                        <p class="mt-2 text-xs font-medium text-red-600">{{ $messages[0] }}</p>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </div>

    <div class="space-y-6">
        <x-ui.card title="Visibility">
            <x-ui.checkbox name="is_public" label="Show on the public website" :checked="$project->is_public ?? true"
                hint="Untick for projects that are confidential or not ready to announce. Internal projects still appear here and in exports." />
        </x-ui.card>

        <x-ui.card title="Project report">
            @if ($project->report_file_path)
                <div class="mb-4 flex items-center justify-between gap-3 rounded-lg bg-slate-50 p-3 text-sm">
                    <span class="inline-flex items-center gap-2 font-medium text-slate-700"><x-ui.icon name="document" class="size-4 text-red-600" /> Report on file</span>
                    <a href="{{ route('workspace.projects.report', $project) }}" target="_blank" rel="noopener" class="text-xs font-semibold text-uew-royal hover:underline">Open</a>
                </div>
                <x-ui.checkbox name="remove_report" label="Remove the current report" class="mb-4" />
            @endif
            <x-ui.input name="report_file" type="file" :label="$project->report_file_path ? 'Replace report' : 'Upload report'" accept="application/pdf"
                :hint="'PDF, up to ' . (int) floor(config('hub.max_upload_kb') / 1024) . ' MB.'" />
        </x-ui.card>

        <div class="flex flex-col gap-2">
            <x-ui.button type="submit" size="lg" icon="check">{{ $project->exists ? 'Save changes' : 'Create project' }}</x-ui.button>
            <x-ui.button :href="$project->exists ? route('workspace.projects.show', $project) : route('workspace.projects.index')" variant="ghost">Cancel</x-ui.button>
        </div>
    </div>
</div>
