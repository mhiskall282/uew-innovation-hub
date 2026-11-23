@use('App\Support\Format')
<x-layouts.workspace title="Grant applications">
    <x-ui.page-header title="Grant applications" description="Applications submitted through the public site. Applicant details are confidential to the review panel." />

    {{-- Status tabs --}}
    <nav class="flex flex-wrap gap-2" aria-label="Filter by status">
        <a href="{{ route('workspace.grants.index', $filters) }}" @class(['inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 ring-inset', 'bg-uew-navy text-white ring-uew-navy' => ! $activeStatus, 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' => $activeStatus])>
            All <span class="font-mono opacity-75">{{ $counts['total'] }}</span>
        </a>
        @foreach ($statuses as $status)
            <a href="{{ route('workspace.grants.index', array_merge($filters, ['status' => $status->value])) }}" @class(['inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 ring-inset', 'bg-uew-navy text-white ring-uew-navy' => $activeStatus === $status, 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' => $activeStatus !== $status])>
                {{ $status->label() }} <span class="font-mono opacity-75">{{ $counts['by_status'][$status->value] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-card sm:flex-row" role="search">
        @if ($activeStatus)<input type="hidden" name="status" value="{{ $activeStatus->value }}">@endif
        <label class="relative flex-1">
            <span class="sr-only">Search</span>
            <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Reference, applicant, email or title"
                   class="block w-full rounded-lg border border-slate-300 py-2 pr-3 pl-9 text-sm focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
        </label>
        <x-ui.select name="category" :options="$focusAreas" :value="$filters['category'] ?? ''" placeholder="All focus areas" wrap="sm:w-56" aria-label="Focus area" />
        <x-ui.button type="submit" icon="filter">Filter</x-ui.button>
    </form>

    <x-ui.card padding="p-0">
        @if ($applications->isEmpty())
            <x-ui.empty title="No applications here" icon="inbox">New applications from the public site will appear in this queue.</x-ui.empty>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3">Application</th>
                            <th scope="col" class="px-3 py-3">Applicant</th>
                            <th scope="col" class="hidden px-3 py-3 text-right md:table-cell">Requested</th>
                            <th scope="col" class="px-3 py-3">Status</th>
                            <th scope="col" class="hidden px-5 py-3 lg:table-cell">Received</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($applications as $application)
                            <tr class="hover:bg-slate-50/60">
                                <td class="max-w-sm px-5 py-3.5">
                                    <a href="{{ route('workspace.grants.show', $application) }}" class="font-semibold text-slate-900 hover:text-uew-royal">{{ $application->project_title }}</a>
                                    <p class="mt-0.5 font-mono text-[11px] text-slate-500">{{ $application->reference_no }} · {{ $application->category }}</p>
                                </td>
                                <td class="px-3 py-3.5">
                                    <p class="font-medium text-slate-800">{{ $application->applicant_name }}</p>
                                    <p class="text-xs text-slate-500">{{ $application->institution_or_org ?? $application->email }}</p>
                                </td>
                                <td class="hidden px-3 py-3.5 text-right font-mono text-xs whitespace-nowrap text-slate-700 md:table-cell">{{ Format::money($application->funding_requested, 0) }}</td>
                                <td class="px-3 py-3.5">
                                    <x-ui.badge :tone="$application->status->tone()" dot>{{ $application->status->label() }}</x-ui.badge>
                                    @if ($application->reviewer)<p class="mt-1 text-[11px] text-slate-400">by {{ $application->reviewer->name }}</p>@endif
                                </td>
                                <td class="hidden px-5 py-3.5 text-xs whitespace-nowrap text-slate-500 lg:table-cell" title="{{ $application->created_at->format('j M Y, H:i') }}">{{ $application->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">{{ $applications->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.workspace>
