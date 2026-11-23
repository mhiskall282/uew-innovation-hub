<x-layouts.app title="Projects & Initiatives Registry">
    <x-slot:header>
        Projects & Research Initiatives
    </x-slot:header>

    <!-- Filters & Action Bar -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-2xs">
        <form method="GET" action="{{ route('projects.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            
            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-mono uppercase font-semibold text-slate-500 mb-1">Search Keywords</label>
                <div class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Search title, researcher, department..." 
                        class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
            </div>

            <!-- Category -->
            <div>
                <label class="block text-[10px] font-mono uppercase font-semibold text-slate-500 mb-1">Category</label>
                <select name="category" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden">
                    <option value="all">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-[10px] font-mono uppercase font-semibold text-slate-500 mb-1">Status</label>
                <select name="status" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden">
                    <option value="all">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>

            <!-- Year -->
            <div>
                <label class="block text-[10px] font-mono uppercase font-semibold text-slate-500 mb-1">Initiative Year</label>
                <select name="year" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden">
                    <option value="all">All Years</option>
                    @foreach ($years as $yr)
                        <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Actions (Filter + Export) -->
            <div class="flex items-end gap-2">
                <x-npontu.button type="submit" variant="primary" class="w-full py-1.5">
                    Filter
                </x-npontu.button>
                <a 
                    href="{{ route('projects.export', request()->all()) }}" 
                    class="p-2 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors"
                    title="Export Filtered Results to Excel"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </a>
            </div>
        </form>
    </div>

    <!-- High-Density Projects Data Table -->
    <x-npontu.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-mono uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3 px-4">ID</th>
                        <th class="py-3 px-4">Initiative & Description</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Beneficiaries</th>
                        <th class="py-3 px-4">Grant (GHS)</th>
                        <th class="py-3 px-4">Partners</th>
                        <th class="py-3 px-4 text-center">Report</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($projects as $project)
                        <tr class="hover:bg-slate-50/80 transition-colors group">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-500">
                                #{{ $project->id }}
                            </td>
                            <td class="py-3.5 px-4 max-w-sm">
                                <a href="{{ route('projects.show', $project) }}" class="font-bold text-slate-900 hover:text-[#3157d5] block transition-colors leading-tight">
                                    {{ $project->title }}
                                </a>
                                <p class="text-[11px] text-slate-500 mt-1 line-clamp-1 leading-snug">
                                    {{ $project->summary }}
                                </p>
                                <span class="text-[10px] font-mono text-slate-400 mt-0.5 block">
                                    Lead: {{ $project->lead_researcher ?? 'UEW Directorate' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $project->category }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <x-npontu.badge :variant="$project->status" :pulse="$project->status === 'active'">
                                    {{ ucfirst($project->status) }}
                                </x-npontu.badge>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                <div class="flex items-center gap-1.5 font-bold text-slate-800">
                                    <span>{{ number_format($project->beneficiaries_reached) }}</span>
                                    <span class="text-[10px] font-normal text-slate-400">/ {{ number_format($project->beneficiaries_target) }}</span>
                                </div>
                                <div class="w-20 bg-slate-100 rounded-full h-1 mt-1 overflow-hidden">
                                    <div class="bg-gradient-to-r from-[#102a72] to-[#3157d5] h-1 rounded-full" style="width: {{ $project->progress_percentage }}%"></div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-semibold text-slate-700">
                                {{ $project->grant_amount ? 'GH₵ ' . number_format($project->grant_amount, 2) : '—' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @forelse ($project->organizations as $org)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-blue-50 text-[#102a72] border border-blue-200 truncate max-w-[120px]" title="{{ $org->name }} ({{ $org->pivot->role }})">
                                            {{ $org->name }}
                                        </span>
                                    @empty
                                        <span class="text-slate-400 text-[11px] italic">UEW Hub</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if ($project->report_file_path)
                                    <a 
                                        href="{{ route('files.stream', ['type' => 'reports', 'filename' => $project->report_file_path]) }}" 
                                        target="_blank"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-mono font-semibold text-rose-700 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition-colors"
                                        title="View Official Report"
                                    >
                                        <span>PDF</span>
                                    </a>
                                @else
                                    <span class="text-slate-300 text-[11px] font-mono">—</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('projects.show', $project) }}" class="p-1 rounded text-slate-500 hover:text-[#3157d5] hover:bg-slate-100" title="View Dossier">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    </a>

                                    @auth
                                        @if (Auth::user()->canManageProjects())
                                            <a href="{{ route('projects.edit', $project) }}" class="p-1 rounded text-slate-500 hover:text-amber-600 hover:bg-slate-100" title="Edit Initiative">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </a>
                                        @endif

                                        @if (Auth::user()->isSuperAdmin())
                                            <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Are you certain you wish to archive this research initiative?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50" title="Archive Project">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </form>
                                        @endif
                                    @endauth
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-500">
                                <div class="max-w-xs mx-auto">
                                    <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                    <p class="font-bold text-slate-700">No initiatives found</p>
                                    <p class="text-xs text-slate-500 mt-1">Try adjusting your keyword search or active filter selections.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($projects->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $projects->links() }}
            </div>
        @endif
    </x-npontu.card>
</x-layouts.app>
