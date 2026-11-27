@use('App\Support\Format')
<x-layouts.workspace :title="$application->reference_no">
    <x-ui.page-header :title="$application->project_title" :back="route('workspace.grants.index')" back-label="Grant applications">
        <x-slot:actions>
            <x-ui.badge :tone="$application->status->tone()" size="md" dot>{{ $application->status->label() }}</x-ui.badge>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.card title="Proposal">
                <dl class="grid gap-4 text-sm sm:grid-cols-3">
                    <div><dt class="text-xs text-slate-500">Reference</dt><dd class="mt-0.5 font-mono font-semibold text-uew-navy select-all">{{ $application->reference_no }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Focus area</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $application->category }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Funding requested</dt><dd class="mt-0.5 font-mono font-semibold text-emerald-700">{{ Format::money($application->funding_requested) }}</dd></div>
                </dl>
                <div class="mt-6 border-t border-slate-100 pt-5">
                    <h3 class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Summary</h3>
                    <p class="mt-2 text-sm leading-relaxed whitespace-pre-line text-slate-700">{{ $application->summary }}</p>
                </div>
                <div class="mt-6 flex items-center justify-between gap-3 rounded-lg bg-slate-50 p-4">
                    <div class="flex items-center gap-3 text-sm">
                        <x-ui.icon name="document" class="size-5 text-red-600" />
                        <span class="font-medium text-slate-700">{{ $application->hasProposal() ? 'Full proposal attached (PDF)' : 'No proposal document attached' }}</span>
                    </div>
                    @if ($application->hasProposal())
                        <x-ui.button :href="route('workspace.grants.proposal', $application)" variant="secondary" size="sm" icon="download" target="_blank" rel="noopener">Open PDF</x-ui.button>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card title="Applicant">
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-500">Name</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $application->applicant_name }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Department or organisation</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $application->institution_or_org ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Email</dt><dd class="mt-0.5"><a href="mailto:{{ $application->email }}" class="font-medium text-uew-royal hover:underline">{{ $application->email }}</a></dd></div>
                    <div><dt class="text-xs text-slate-500">Phone</dt><dd class="mt-0.5 font-mono text-slate-900">{{ $application->phone ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Submitted</dt><dd class="mt-0.5 text-slate-900">{{ $application->created_at->format('j M Y, H:i') }}</dd></div>
                </dl>
            </x-ui.card>
        </div>

        <div class="space-y-6">
            @can('review', $application)
                <x-ui.card title="Review decision" description="The applicant sees the status (not your notes) on the tracking page.">
                    <form method="POST" action="{{ route('workspace.grants.update', $application) }}" class="space-y-4">
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
                        <x-ui.textarea name="reviewer_notes" label="Panel notes" :value="$application->reviewer_notes" rows="5" hint="Internal. Required when declining." />
                        <x-ui.checkbox name="notify_applicant" label="Email the applicant if the status changes" checked />
                        <x-ui.button type="submit" class="w-full" icon="check">Save decision</x-ui.button>
                    </form>
                    @if ($application->reviewer)
                        <p class="mt-4 text-xs text-slate-500">Last reviewed by {{ $application->reviewer->name }} {{ $application->reviewed_at?->diffForHumans() }}.</p>
                    @endif
                </x-ui.card>
            @endcan

            <x-ui.card title="History" padding="p-0">
                <ol class="divide-y divide-slate-100">
                    @forelse ($history as $entry)
                        <li class="px-5 py-3">
                            <p class="text-xs text-slate-700">{{ $entry->description }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">{{ $entry->user?->name ?? 'Applicant' }} · {{ $entry->created_at->format('j M Y, H:i') }}</p>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-slate-500">No history yet.</li>
                    @endforelse
                </ol>
            </x-ui.card>
        </div>
    </div>
</x-layouts.workspace>
