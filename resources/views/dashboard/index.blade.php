<x-layouts.app title="Cockpit Telemetry">
    <x-slot:header>
        Innovation Telemetry & Cockpit
    </x-slot:header>

    <!-- Row 1: High-Density Npontu KPI Telemetry Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Projects -->
        <x-npontu.stat-card 
            title="Total Initiatives" 
            value="{{ $metrics['total_projects'] }}"
            subtext="{{ $metrics['active_projects'] }} Active | {{ $metrics['completed_projects'] }} Completed"
            accentColor="blue"
            trend="+100% Retained"
            trendType="up"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-[#3157d5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </x-slot:icon>
        </x-npontu.stat-card>

        <!-- Beneficiaries Reached -->
        <x-npontu.stat-card 
            title="Total Beneficiaries" 
            value="{{ number_format($metrics['total_beneficiaries']) }}"
            subtext="Target: {{ number_format($metrics['total_target_beneficiaries']) }} individuals"
            accentColor="gold"
            trend="{{ round(($metrics['total_beneficiaries'] / max(1, $metrics['total_target_beneficiaries'])) * 100) }}% Target Achieved"
            trendType="up"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </x-slot:icon>
        </x-npontu.stat-card>

        <!-- Total Grant Funding -->
        <x-npontu.stat-card 
            title="Grant Capital (GHS)" 
            value="GH₵ {{ number_format($metrics['total_funding'] / 1000, 1) }}k"
            subtext="Allocated across research clusters"
            accentColor="emerald"
            trend="100% Deployed"
            trendType="neutral"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-npontu.stat-card>

        <!-- Partner Organizations -->
        <x-npontu.stat-card 
            title="Strategic Partners" 
            value="{{ $metrics['total_organizations'] }}"
            subtext="Global & National Stakeholders"
            accentColor="purple"
            trend="Active Alliances"
            trendType="up"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </x-slot:icon>
        </x-npontu.stat-card>
    </div>

    <!-- Row 2: Recent Projects Cockpit Table & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left 2 Cols: High-Density Initiatives Table -->
        <div class="lg:col-span-2">
            <x-npontu.card title="Active Research & Innovation Initiatives" subtitle="High-priority projects registered with the Innovation Hub" padding="p-0">
                <x-slot:action>
                    <a href="{{ route('projects.index') }}" class="text-xs font-semibold uppercase tracking-wider text-[#3157d5] hover:text-[#102a72] flex items-center gap-1 transition-colors">
                        <span>View All Projects</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </x-slot:action>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200/80 text-slate-500 font-mono uppercase text-[10px] tracking-wider">
                            <tr>
                                <th class="py-3 px-4">Initiative</th>
                                <th class="py-3 px-4">Category</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Beneficiaries</th>
                                <th class="py-3 px-4 text-right">Report</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($recentProjects as $project)
                                <tr class="hover:bg-slate-50/80 transition-colors group">
                                    <td class="py-3 px-4">
                                        <a href="{{ route('projects.show', $project) }}" class="font-semibold text-slate-900 hover:text-[#3157d5] block transition-colors leading-tight">
                                            {{ $project->title }}
                                        </a>
                                        <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-500">
                                            <span class="font-mono">ID: #{{ $project->id }}</span>
                                            <span>•</span>
                                            <span>{{ $project->organizations->pluck('name')->first() ?? 'UEW Innovation' }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ $project->category }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <x-npontu.badge :variant="$project->status" :pulse="$project->status === 'active'">
                                            {{ ucfirst($project->status) }}
                                        </x-npontu.badge>
                                    </td>
                                    <td class="py-3 px-4 font-mono">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800">{{ number_format($project->beneficiaries_reached) }}</span>
                                            <span class="text-slate-400 text-[10px]">/ {{ number_format($project->beneficiaries_target) }}</span>
                                        </div>
                                        <div class="w-24 bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                                            <div class="bg-gradient-to-r from-[#102a72] to-[#3157d5] h-1.5 rounded-full" style="width: {{ $project->progress_percentage }}%"></div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        @if ($project->report_file_path)
                                            <a 
                                                href="{{ route('files.stream', ['type' => 'reports', 'filename' => $project->report_file_path]) }}" 
                                                target="_blank"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded text-[11px] font-medium text-[#3157d5] bg-blue-50 border border-blue-200 hover:bg-blue-100 transition-all"
                                                title="View Official PDF Report"
                                            >
                                                <svg class="w-3.5 h-3.5 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" /></svg>
                                                <span>PDF</span>
                                            </a>
                                        @else
                                            <span class="text-slate-400 text-[11px] italic">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-npontu.card>
        </div>

        <!-- Right 1 Col: Strategic Alliances & Recent Publications -->
        <div class="space-y-6">
            
            <!-- Quick Actions Panel -->
            <x-npontu.card title="Institutional Actions" subtitle="Operational shortcuts">
                <div class="space-y-2.5">
                    @auth
                        @if(Auth::user()->canManageProjects())
                            <x-npontu.button href="{{ route('projects.create') }}" variant="primary" class="w-full">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                <span>Register New Initiative</span>
                            </x-npontu.button>
                        @endif
                    @endauth

                    <x-npontu.button href="{{ route('projects.export') }}" variant="secondary" class="w-full">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        <span>Download Excel Data Dump</span>
                    </x-npontu.button>
                </div>
            </x-npontu.card>

            <!-- Latest Publications & Newsletters -->
            <x-npontu.card title="Knowledge Artifacts" subtitle="Recent publications & newsletters" padding="p-0">
                <div class="divide-y divide-slate-100">
                    @foreach ($publications as $pub)
                        <div class="p-4 hover:bg-slate-50 transition-colors">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold uppercase bg-amber-50 text-amber-800 border border-amber-200">
                                        {{ $pub->type }}
                                    </span>
                                    <h4 class="text-xs font-bold text-slate-900 mt-1.5 leading-snug">
                                        {{ $pub->title }}
                                    </h4>
                                    <p class="text-[11px] text-slate-500 mt-0.5 font-mono">
                                        {{ $pub->publish_date ? $pub->publish_date->format('M Y') : 'Recent' }} • {{ $pub->authors ?? 'UEW Directorate' }}
                                    </p>
                                </div>
                            </div>

                            @if ($pub->pdf_file_path)
                                <div class="mt-2.5">
                                    <a 
                                        href="{{ route('files.stream', ['type' => 'publications', 'filename' => $pub->pdf_file_path]) }}" 
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#102a72] hover:text-[#3157d5] transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 2l3 3h-3V4z" clip-rule="evenodd" /></svg>
                                        <span>Download Artifact PDF</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-npontu.card>

        </div>
    </div>
</x-layouts.app>
