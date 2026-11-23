@use('App\Support\FormSchema')
<x-layouts.workspace :title="$application->reference">
    @php $applicant = $application->applicant; @endphp

    <x-ui.page-header :title="$applicant?->name ?? 'Deleted account'" :description="$application->program->title . ' · ' . $application->reference"
        :back="route('workspace.programs.show', $application->program)" back-label="Back to programme">
        <x-slot:actions>
            <x-ui.badge :tone="$application->status->tone()" size="md" dot>{{ $application->status->label() }}</x-ui.badge>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.card title="Answers" :description="'Submitted ' . $application->submitted_at?->format('j M Y, H:i')">
                <dl class="divide-y divide-slate-100">
                    @forelse ($application->program->fields() as $field)
                        <div class="py-4 first:pt-0 last:pb-0">
                            <dt class="text-xs font-semibold text-slate-500">{{ $field['label'] }}</dt>
                            <dd class="mt-1 text-sm leading-relaxed whitespace-pre-line text-slate-900">
                                @if ($field['type'] === 'file' && $application->answer($field['key']))
                                    <a href="{{ route('workspace.applications.file', [$application, $field['key']]) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100"><x-ui.icon name="document" class="size-4" /> Open PDF</a>
                                @else
                                    {{ FormSchema::display($field, $application->answer($field['key'])) }}
                                @endif
                            </dd>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">This programme has no questions.</p>
                    @endforelse
                </dl>
            </x-ui.card>

            <x-ui.card title="Applicant">
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-500">E-mail</dt><dd class="mt-0.5"><a href="mailto:{{ $applicant?->email }}" class="font-medium text-uew-royal hover:underline">{{ $applicant?->email }}</a></dd></div>
                    <div><dt class="text-xs text-slate-500">Phone</dt><dd class="mt-0.5 font-mono text-slate-900">{{ $applicant?->phone ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Affiliation</dt><dd class="mt-0.5 text-slate-900">{{ $applicant?->affiliation?->label() ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Institution</dt><dd class="mt-0.5 text-slate-900">{{ $applicant?->institution ?? '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Department / programme</dt><dd class="mt-0.5 text-slate-900">{{ $applicant?->department ?? '—' }}</dd></div>
                    @if ($applicant?->bio)
                        <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Bio</dt><dd class="mt-0.5 whitespace-pre-line text-slate-700">{{ $applicant->bio }}</dd></div>
                    @endif
                </dl>
                @if ($otherApplications->isNotEmpty())
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <p class="text-xs font-semibold text-slate-500">Other applications</p>
                        <ul class="mt-2 space-y-1.5 text-sm">
                            @foreach ($otherApplications as $other)
                                <li class="flex items-center justify-between gap-3">
                                    <a href="{{ route('workspace.applications.show', $other) }}" class="truncate text-slate-700 hover:text-uew-royal">{{ $other->program?->title }}</a>
                                    <x-ui.badge :tone="$other->status->tone()" size="xs">{{ $other->status->label() }}</x-ui.badge>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @can('members.manage')
                    @if ($applicant)
                        <x-ui.button :href="route('workspace.members.show', $applicant)" variant="ghost" size="xs" class="mt-4" icon-right="arrow-right">Member profile</x-ui.button>
                    @endif
                @endcan
            </x-ui.card>
        </div>

        <div class="space-y-6">
            @can('review', $application)
                <x-ui.card title="Decision">
                    <form method="POST" action="{{ route('workspace.applications.update', $application) }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <fieldset>
                            <legend class="mb-2 text-xs font-semibold text-slate-700">Status</legend>
                            <div class="space-y-1.5">
                                @foreach ($statuses as $status)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm has-[:checked]:border-uew-royal has-[:checked]:bg-blue-50/60">
                                        <input type="radio" name="status" value="{{ $status->value }}" @checked(old('status', $application->status->value) === $status->value) class="size-4 border-slate-300 text-uew-navy focus:ring-uew-royal">
                                        <x-ui.badge :tone="$status->tone()" dot>{{ $status->label() }}</x-ui.badge>
                                    </label>
                                @endforeach
                            </div>
                            @error('status')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                        </fieldset>
                        <x-ui.input name="score" type="number" min="0" max="100" label="Score (0 to 100)" :value="$application->score" />
                        <x-ui.textarea name="decision_message" label="Message to the applicant" :value="$application->decision_message" rows="3" hint="Optional. Shown in their account and in the status e-mail." />
                        <x-ui.textarea name="internal_notes" label="Internal notes" :value="$application->internal_notes" rows="4" hint="Only staff can see these." />
                        <x-ui.checkbox name="notify" label="E-mail the applicant if the status changes" checked />
                        <x-ui.button type="submit" class="w-full" icon="check">Save decision</x-ui.button>
                    </form>
                    @if ($application->reviewer)
                        <p class="mt-4 text-xs text-slate-500">Last reviewed by {{ $application->reviewer->name }} {{ $application->updated_at->diffForHumans() }}.</p>
                    @endif
                </x-ui.card>
            @else
                <x-ui.alert tone="info">You cannot review your own application.</x-ui.alert>
            @endcan

            <x-ui.card title="History" padding="p-0">
                <ol class="divide-y divide-slate-100">
                    @forelse ($history as $entry)
                        <li class="px-5 py-3">
                            <p class="text-xs text-slate-700">{{ $entry->description }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">{{ $entry->user?->name ?? 'System' }} · {{ $entry->created_at->format('j M Y, H:i') }}</p>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-slate-500">No history yet.</li>
                    @endforelse
                </ol>
            </x-ui.card>
        </div>
    </div>
</x-layouts.workspace>
