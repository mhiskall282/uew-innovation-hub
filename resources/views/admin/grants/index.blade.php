<x-layouts.app title="Grant Applications Management">
    <x-slot:header>
        Grant & Incubation Review
    </x-slot:header>

    <!-- Telemetry Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <x-npontu.stat-card title="Total Received" value="{{ $counts['total'] }}" accentColor="blue" />
        <x-npontu.stat-card title="New Submissions" value="{{ $counts['submitted'] }}" accentColor="gold" />
        <x-npontu.stat-card title="Under Review" value="{{ $counts['under_review'] }}" accentColor="purple" />
        <x-npontu.stat-card title="Approved Grants" value="{{ $counts['approved'] }}" accentColor="emerald" />
    </div>

    <!-- Applications Table -->
    <x-npontu.card title="Grant Application Dossiers" subtitle="Proposals submitted through the public innovation intake portal" padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-mono uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Ref #</th>
                        <th class="py-3 px-4">Applicant & Institution</th>
                        <th class="py-3 px-4">Project Title</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">Grant Requested</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($applications as $app)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3.5 px-4 font-mono font-bold text-[#102a72]">
                                <a href="{{ route('admin.grants.show', $app) }}" class="hover:underline">{{ $app->reference_no }}</a>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900 block leading-tight">{{ $app->applicant_name }}</span>
                                <span class="text-[11px] text-slate-500 block truncate max-w-xs">{{ $app->institution_or_org ?? $app->email }}</span>
                            </td>
                            <td class="py-3.5 px-4 max-w-xs font-medium text-slate-800 truncate">
                                {{ $app->project_title }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-blue-50 text-[#102a72] border border-blue-200">
                                    {{ $app->category }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-800">
                                GH₵ {{ number_format($app->funding_requested, 2) }}
                            </td>
                            <td class="py-3.5 px-4">
                                @php
                                    $variant = match($app->status) {
                                        'approved' => 'active',
                                        'under_review' => 'purple',
                                        'shortlisted' => 'completed',
                                        'rejected' => 'suspended',
                                        default => 'pending',
                                    };
                                @endphp
                                <x-npontu.badge :variant="$variant">
                                    {{ strtoupper(str_replace('_', ' ', $app->status)) }}
                                </x-npontu.badge>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('admin.grants.show', $app) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-semibold text-[#102a72] bg-slate-100 hover:bg-slate-200 transition-colors">
                                    <span>Review Dossier</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">No grant applications recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($applications->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $applications->links() }}
            </div>
        @endif
    </x-npontu.card>
</x-layouts.app>
