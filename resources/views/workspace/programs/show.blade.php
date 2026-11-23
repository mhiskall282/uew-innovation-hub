@use('App\Enums\ApplicationStatus')
<x-layouts.workspace :title="$program->title">
    @php
        [$state, $tone] = $program->availability();
        $total = $counts->except([ApplicationStatus::Draft->value, ApplicationStatus::Withdrawn->value])->sum();
    @endphp

    <x-ui.page-header :title="$program->title" :back="auth()->user()->can('programs.manage') ? route('workspace.programs.index') : route('workspace.applications.index')" back-label="Programmes">
        <x-slot:actions>
            <x-ui.badge :tone="$tone" size="md" dot>{{ $state }}</x-ui.badge>
            @if ($program->isVisible())
                <x-ui.button :href="route('site.programs.show', $program)" variant="secondary" size="sm" icon="external" target="_blank" rel="noopener">Public page</x-ui.button>
            @endif
            <x-ui.button :href="route('workspace.programs.export', $program)" variant="secondary" size="sm" icon="download">Export CSV</x-ui.button>
            @can('programs.manage')
                @if ($program->status->value === 'published')
                    <x-ui.confirm-form :action="route('workspace.programs.announce', $program)" method="POST" message="E-mail an announcement about this programme to matching members who opted in?" label="Announce" variant="secondary" icon="bell" />
                @endif
                <x-ui.button :href="route('workspace.programs.edit', $program)" size="sm" icon="pencil">Edit</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-ui.stat label="Applications" :value="$total" icon="inbox" tone="navy" :hint="$program->capacity ? 'of ' . $program->capacity . ' places' : null" />
        <x-ui.stat label="To review" :value="collect(ApplicationStatus::open())->sum(fn ($s) => $counts[$s->value] ?? 0)" icon="clock" tone="gold" />
        <x-ui.stat label="Shortlisted" :value="$counts[ApplicationStatus::Shortlisted->value] ?? 0" icon="sparkles" tone="purple" />
        <x-ui.stat label="Accepted" :value="$counts[ApplicationStatus::Approved->value] ?? 0" icon="check-circle" tone="green" />
        <x-ui.stat label="Drafts in progress" :value="$counts[ApplicationStatus::Draft->value] ?? 0" icon="pencil" tone="royal" hint="Not yet submitted" />
    </div>

    {{-- Status filter --}}
    <nav class="flex flex-wrap gap-2" aria-label="Filter by status">
        <a href="{{ route('workspace.programs.show', [$program, 'search' => $search]) }}" @class(['inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 ring-inset', 'bg-uew-navy text-white ring-uew-navy' => ! $activeStatus, 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' => $activeStatus])>All submitted <span class="font-mono opacity-75">{{ $total }}</span></a>
        @foreach ($statuses as $status)
            <a href="{{ route('workspace.programs.show', [$program, 'status' => $status->value, 'search' => $search]) }}" @class(['inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 ring-inset', 'bg-uew-navy text-white ring-uew-navy' => $activeStatus === $status, 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' => $activeStatus !== $status])>
                {{ $status->label() }} <span class="font-mono opacity-75">{{ $counts[$status->value] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    <x-ui.card padding="p-0">
        <div class="border-b border-slate-100 p-3">
            <form method="GET" class="flex gap-2" role="search">
                @if ($activeStatus)<input type="hidden" name="status" value="{{ $activeStatus->value }}">@endif
                <label class="relative flex-1">
                    <span class="sr-only">Search applicants</span>
                    <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                    <input type="search" name="search" value="{{ $search }}" placeholder="Reference, name, e-mail or institution"
                           class="block w-full rounded-lg border border-slate-300 py-2 pr-3 pl-9 text-sm focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
                </label>
                <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
            </form>
        </div>

        @if ($applications->isEmpty())
            <x-ui.empty title="No applications here" icon="inbox">Submitted applications appear here. Drafts are private to applicants until they submit.</x-ui.empty>
        @else
            <form method="POST" action="{{ route('workspace.applications.bulk', $program) }}" x-data="{ selected: [], all: false }">
                @csrf
                <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-2.5 text-xs" x-show="selected.length > 0" x-cloak>
                    <span class="font-semibold text-slate-700"><span x-text="selected.length"></span> selected</span>
                    <select name="status" class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs" aria-label="New status">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">Mark as {{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <label class="inline-flex items-center gap-1.5 text-slate-600"><input type="checkbox" name="notify" value="1" checked class="size-3.5 rounded border-slate-300 text-uew-navy"> E-mail applicants</label>
                    <x-ui.button type="submit" size="xs">Apply to selected</x-ui.button>
                </div>
                @error('ids')<p class="px-5 pt-2 text-xs font-medium text-red-600">{{ $message }}</p>@enderror

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                            <tr>
                                <th scope="col" class="w-10 px-5 py-3">
                                    <input type="checkbox" aria-label="Select all" class="size-4 rounded border-slate-300 text-uew-navy"
                                           @change="all = $event.target.checked; selected = all ? @js($applications->pluck('id')->map(fn ($id) => (string) $id)->values()) : []">
                                </th>
                                <th scope="col" class="px-3 py-3">Applicant</th>
                                <th scope="col" class="hidden px-3 py-3 lg:table-cell">Reference</th>
                                <th scope="col" class="px-3 py-3">Status</th>
                                <th scope="col" class="hidden px-3 py-3 text-right md:table-cell">Score</th>
                                <th scope="col" class="hidden px-5 py-3 md:table-cell">Submitted</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($applications as $application)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-5 py-3.5"><input type="checkbox" name="ids[]" value="{{ $application->id }}" x-model="selected" aria-label="Select {{ $application->applicant?->name }}" class="size-4 rounded border-slate-300 text-uew-navy"></td>
                                    <td class="px-3 py-3.5">
                                        <a href="{{ route('workspace.applications.show', $application) }}" class="font-semibold text-slate-900 hover:text-uew-royal">{{ $application->applicant?->name ?? 'Deleted account' }}</a>
                                        <p class="text-xs text-slate-500">{{ $application->applicant?->institution ?? $application->applicant?->email }}</p>
                                    </td>
                                    <td class="hidden px-3 py-3.5 font-mono text-xs text-slate-500 lg:table-cell">{{ $application->reference }}</td>
                                    <td class="px-3 py-3.5">
                                        <x-ui.badge :tone="$application->status->tone()" dot>{{ $application->status->label() }}</x-ui.badge>
                                        @if ($application->reviewer)<p class="mt-1 text-[11px] text-slate-400">by {{ $application->reviewer->name }}</p>@endif
                                    </td>
                                    <td class="hidden px-3 py-3.5 text-right font-mono text-xs text-slate-700 md:table-cell">{{ $application->score ?? '—' }}</td>
                                    <td class="hidden px-5 py-3.5 text-xs text-slate-500 md:table-cell">{{ $application->submitted_at?->format('j M Y, H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
            <div class="border-t border-slate-100 px-5 py-3">{{ $applications->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.workspace>
