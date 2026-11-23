<x-layouts.app title="{{ $project->title }}">
    <x-slot:header>
        Project Dossier: #{{ $project->id }}
    </x-slot:header>

    <!-- Top Dossier Navigation & Actions -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-2">
        <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-[#102a72] transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            <span>Back to Initiatives Registry</span>
        </a>

        <div class="flex items-center gap-2">
            @if ($project->report_file_path)
                <a 
                    href="{{ route('files.stream', ['type' => 'reports', 'filename' => $project->report_file_path]) }}" 
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-700 hover:to-rose-800 shadow-sm transition-all"
                >
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 2l3 3h-3V4z" clip-rule="evenodd" /></svg>
                    <span>Download Official Report PDF</span>
                </a>
            @endif

            @auth
                @if (Auth::user()->canManageProjects())
                    <x-npontu.button href="{{ route('projects.edit', $project) }}" variant="secondary" size="sm">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        <span>Edit</span>
                    </x-npontu.button>
                @endif
            @endauth
        </div>
    </div>

    <!-- Main Dossier Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left 2 Cols: Title, Abstract, Timeline, Publications -->
        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs">
                <div class="flex items-center gap-2 mb-3">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-[#102a72] border border-blue-200">
                        {{ $project->category }}
                    </span>
                    <x-npontu.badge :variant="$project->status" :pulse="$project->status === 'active'">
                        {{ ucfirst($project->status) }}
                    </x-npontu.badge>
                    <span class="text-xs font-mono text-slate-400 ml-auto">Initiative #{{ $project->id }}</span>
                </div>

                <h1 class="text-2xl font-bold text-slate-900 tracking-tight leading-snug">
                    {{ $project->title }}
                </h1>

                <div class="mt-6 pt-6 border-t border-slate-100">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 font-mono">Executive Abstract</h3>
                    <p class="text-sm text-slate-700 leading-relaxed">
                        {{ $project->summary ?? 'No abstract description provided.' }}
                    </p>
                </div>

                <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <span class="text-[10px] font-mono uppercase text-slate-400 block">Commencement</span>
                        <span class="text-xs font-bold font-mono text-slate-800 mt-0.5 block">
                            {{ $project->start_date ? $project->start_date->format('d M Y') : 'N/A' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] font-mono uppercase text-slate-400 block">Conclusion</span>
                        <span class="text-xs font-bold font-mono text-slate-800 mt-0.5 block">
                            {{ $project->end_date ? $project->end_date->format('d M Y') : 'Ongoing' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] font-mono uppercase text-slate-400 block">Disbursed Grant</span>
                        <span class="text-xs font-bold font-mono text-emerald-700 mt-0.5 block">
                            {{ $project->grant_amount ? 'GH₵ ' . number_format($project->grant_amount, 2) : 'Unspecified' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] font-mono uppercase text-slate-400 block">Legacy Record</span>
                        <span class="text-xs font-mono text-slate-500 mt-0.5 block">
                            {{ $project->legacy_id ? 'Legacy #' . $project->legacy_id : 'Native L12' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Beneficiary Reach Visualizer -->
            <x-npontu.card title="Beneficiary Telemetry & Target Realization">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <span class="text-3xl font-extrabold font-mono text-slate-900">{{ number_format($project->beneficiaries_reached) }}</span>
                        <span class="text-xs font-mono text-slate-500 ml-1">/ {{ number_format($project->beneficiaries_target) }} target individuals</span>
                    </div>
                    <span class="text-lg font-bold font-mono text-[#3157d5]">{{ $project->progress_percentage }}%</span>
                </div>

                <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden shadow-inner">
                    <div class="bg-gradient-to-r from-[#102a72] to-[#3157d5] h-3 rounded-full transition-all duration-500" style="width: {{ $project->progress_percentage }}%"></div>
                </div>
            </x-npontu.card>

            <!-- Linked Publications -->
            @if ($project->publications->count() > 0)
                <x-npontu.card title="Associated Research Papers & Briefs" padding="p-0">
                    <div class="divide-y divide-slate-100">
                        @foreach ($project->publications as $pub)
                            <div class="p-4 hover:bg-slate-50 transition-colors flex items-center justify-between">
                                <div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-purple-50 text-purple-700 border border-purple-200">
                                        {{ $pub->type }}
                                    </span>
                                    <h4 class="text-xs font-bold text-slate-900 mt-1">{{ $pub->title }}</h4>
                                    <span class="text-[11px] font-mono text-slate-400">{{ $pub->publish_date?->format('d M Y') }} • {{ $pub->authors }}</span>
                                </div>
                                @if ($pub->pdf_file_path)
                                    <a href="{{ route('files.stream', ['type' => 'publications', 'filename' => $pub->pdf_file_path]) }}" target="_blank" class="p-2 text-[#102a72] hover:text-[#3157d5]">
                                        <svg class="w-5 h-5 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 2l3 3h-3V4z" clip-rule="evenodd" /></svg>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-npontu.card>
            @endif

        </div>

        <!-- Right 1 Col: Lead Researcher & Partner Organizations -->
        <div class="space-y-6">
            
            <!-- Lead Investigator -->
            <x-npontu.card title="Principal Investigator">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-full bg-gradient-to-tr from-[#102a72] to-[#3157d5] text-white flex items-center justify-center font-bold text-sm shrink-0">
                        {{ substr($project->lead_researcher ?? 'U', 0, 1) }}
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">{{ $project->lead_researcher ?? 'UEW Innovation Directorate' }}</h4>
                        <p class="text-xs text-slate-500">{{ $project->department ?? 'University of Education, Winneba' }}</p>
                    </div>
                </div>
            </x-npontu.card>

            <!-- Strategic Partners & Role Allocations -->
            <x-npontu.card title="Collaborating Partners" padding="p-0">
                <div class="divide-y divide-slate-100">
                    @forelse ($project->organizations as $org)
                        <div class="p-4 flex items-center justify-between">
                            <div>
                                <h5 class="text-xs font-bold text-slate-800">{{ $org->name }}</h5>
                                <span class="text-[11px] font-mono text-slate-400">{{ $org->type }}</span>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-blue-50 text-[#102a72] border border-blue-200">
                                {{ $org->pivot->role }}
                            </span>
                        </div>
                    @empty
                        <div class="p-4 text-xs text-slate-500 italic">
                            No external partner organizations registered.
                        </div>
                    @endforelse
                </div>
            </x-npontu.card>

        </div>

    </div>
</x-layouts.app>
