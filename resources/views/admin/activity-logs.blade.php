<x-layouts.app title="Security Audit Logs">
    <x-slot:header>
        Telemetry & Security Audit Trail
    </x-slot:header>

    <x-npontu.card title="Audit Events Trail" subtitle="Chronological immutable activity tracking (Act 843 compliant)" padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-mono uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Timestamp</th>
                        <th class="py-3 px-4">Actor</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4 text-right">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-2.5 px-4 text-slate-400">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="py-2.5 px-4 font-bold text-slate-800">
                                {{ $log->user?->name ?? 'System / Anonymous' }}
                            </td>
                            <td class="py-2.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-[#102a72] border border-blue-200">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-slate-700 font-sans text-xs">
                                {{ $log->description }}
                            </td>
                            <td class="py-2.5 px-4 text-right text-slate-400">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500 font-sans">No audit events recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        @endif
    </x-npontu.card>
</x-layouts.app>
