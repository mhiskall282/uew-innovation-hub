<x-layouts.workspace title="Audit log">
    <x-ui.page-header title="Audit log" description="Every sign-in and change made in the workspace, plus public grant submissions. Entries cannot be edited or deleted." />

    <form method="GET" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-card sm:grid-cols-12" role="search">
        <label class="relative sm:col-span-6">
            <span class="sr-only">Search</span>
            <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Description, action or IP address"
                   class="block w-full rounded-lg border border-slate-300 py-2 pr-3 pl-9 text-sm focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
        </label>
        <x-ui.select name="area" :options="collect($areas)->mapWithKeys(fn ($a) => [$a => ucfirst($a)])->all()" :value="$filters['area']" placeholder="All areas" wrap="sm:col-span-2" aria-label="Area" />
        <x-ui.select name="user" :options="$users->pluck('name', 'id')->all()" :value="$filters['user']" placeholder="Anyone" wrap="sm:col-span-2" aria-label="Staff member" />
        <x-ui.button type="submit" icon="filter" class="sm:col-span-2">Filter</x-ui.button>
    </form>

    <x-ui.card padding="p-0">
        @if ($logs->isEmpty())
            <x-ui.empty title="No entries match" icon="shield" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3">When</th>
                            <th scope="col" class="px-3 py-3">Who</th>
                            <th scope="col" class="px-3 py-3">Action</th>
                            <th scope="col" class="px-3 py-3">Details</th>
                            <th scope="col" class="hidden px-5 py-3 lg:table-cell">IP address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($logs as $log)
                            <tr class="align-top hover:bg-slate-50/60">
                                <td class="px-5 py-3 font-mono text-[11px] whitespace-nowrap text-slate-500">
                                    <time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d M Y') }}<br>{{ $log->created_at->format('H:i:s') }}</time>
                                </td>
                                <td class="px-3 py-3 text-xs font-medium whitespace-nowrap text-slate-800">{{ $log->user?->name ?? 'Public / system' }}</td>
                                <td class="px-3 py-3"><x-ui.badge :tone="$log->tone()" size="xs" class="font-mono">{{ $log->action }}</x-ui.badge></td>
                                <td class="max-w-lg px-3 py-3 text-xs text-slate-700">
                                    {{ $log->description }}
                                    @if (! empty($log->properties))
                                        <details class="mt-1 text-[11px] text-slate-500">
                                            <summary class="cursor-pointer font-semibold text-uew-royal">Changes</summary>
                                            <pre class="mt-1 overflow-x-auto rounded bg-slate-50 p-2 font-mono text-[11px] whitespace-pre-wrap">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    @endif
                                </td>
                                <td class="hidden px-5 py-3 font-mono text-[11px] text-slate-500 lg:table-cell">{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">{{ $logs->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.workspace>
