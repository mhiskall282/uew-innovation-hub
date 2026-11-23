<x-layouts.app title="Publications & Research Artifacts">
    <x-slot:header>
        Publications & Newsletters
    </x-slot:header>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-bold text-slate-900">Knowledge Repository</h2>
            <p class="text-xs text-slate-500 mt-0.5">Peer-reviewed publications, policy briefs, and official Innovation Hub newsletters.</p>
        </div>
        <a 
            href="/api/v1/publications" 
            target="_blank"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-mono font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors"
        >
            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
            <span>Public REST API (/api/v1/publications)</span>
        </a>
    </div>

    <!-- Publications Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($publications as $pub)
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-semibold uppercase bg-amber-50 text-amber-800 border border-amber-200">
                            {{ $pub->type }}
                        </span>
                        <span class="text-xs font-mono text-slate-400">
                            {{ $pub->publish_date ? $pub->publish_date->format('M Y') : 'Recent' }}
                        </span>
                    </div>

                    <h3 class="text-sm font-bold text-slate-900 leading-snug">
                        {{ $pub->title }}
                    </h3>

                    <p class="text-xs text-slate-500 mt-2 font-mono">
                        Authors: {{ $pub->authors ?? 'Directorate of Research & Innovation' }}
                    </p>

                    @if ($pub->journal_name)
                        <p class="text-[11px] text-slate-400 mt-1 italic">
                            Published in: {{ $pub->journal_name }}
                        </p>
                    @endif
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        @if ($pub->project)
                            <a href="{{ route('projects.show', $pub->project) }}" class="text-[11px] font-mono text-[#3157d5] hover:underline truncate max-w-[150px] block" title="{{ $pub->project->title }}">
                                Proj: #{{ $pub->project->id }}
                            </a>
                        @endif
                    </div>

                    @if ($pub->pdf_file_path)
                        <a 
                            href="{{ route('files.stream', ['type' => 'publications', 'filename' => $pub->pdf_file_path]) }}" 
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-gradient-to-r from-[#102a72] to-[#3157d5] hover:from-[#0a1b4d] hover:to-[#1e3a8a] shadow-xs transition-all"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <span>Download PDF</span>
                        </a>
                    @else
                        <span class="text-xs font-mono text-slate-400 italic">DOI Reference</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-3 py-12 text-center text-slate-500 bg-white rounded-2xl border border-slate-200">
                <p>No publications registered in the repository.</p>
            </div>
        @endforelse
    </div>

    @if ($publications->hasPages())
        <div class="pt-4">
            {{ $publications->links() }}
        </div>
    @endif
</x-layouts.app>
