<x-layouts.app title="Grant Application Dossier — {{ $application->reference_no }}">
    <x-slot:header>
        Review Application: {{ $application->reference_no }}
    </x-slot:header>

    <div class="flex items-center gap-2 mb-4">
        <a href="{{ route('admin.grants.index') }}" class="text-xs font-semibold text-slate-600 hover:text-[#102a72] flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            <span>Back to All Applications</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left 2 Cols: Proposal Details -->
        <div class="lg:col-span-2 space-y-6">
            <x-npontu.card title="Project Proposal Abstract" subtitle="Submitted by {{ $application->applicant_name }}">
                <h1 class="text-xl font-bold text-slate-900 leading-snug mb-3">
                    {{ $application->project_title }}
                </h1>

                <div class="flex flex-wrap items-center gap-3 pb-4 border-b border-slate-100 text-xs font-mono">
                    <span class="px-2 py-0.5 rounded bg-blue-50 text-[#102a72] border border-blue-200">{{ $application->category }}</span>
                    <span class="font-bold text-emerald-700">Requested: GH₵ {{ number_format($application->funding_requested, 2) }}</span>
                    <span class="text-slate-400">Date: {{ $application->created_at->format('d M Y') }}</span>
                </div>

                <div class="mt-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 font-mono mb-2">Proposal Summary</h4>
                    <p class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line">
                        {{ $application->summary }}
                    </p>
                </div>

                @if ($application->proposal_file_path)
                    <div class="mt-6 pt-6 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs font-mono text-slate-500">Proposal File: {{ $application->proposal_file_path }}</span>
                        <a href="{{ route('files.stream', ['type' => 'reports', 'filename' => $application->proposal_file_path]) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 2l3 3h-3V4z" clip-rule="evenodd" /></svg>
                            <span>Download Full Proposal PDF</span>
                        </a>
                    </div>
                @endif
            </x-npontu.card>

            <!-- Applicant Info -->
            <x-npontu.card title="Applicant Contact Profile">
                <div class="grid grid-cols-2 gap-4 text-xs font-mono">
                    <div>
                        <span class="text-slate-400 block">Applicant Name</span>
                        <span class="font-bold text-slate-800 text-sm mt-0.5 block font-sans">{{ $application->applicant_name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Email Address</span>
                        <a href="mailto:{{ $application->email }}" class="text-[#3157d5] hover:underline mt-0.5 block">{{ $application->email }}</a>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Phone Contact</span>
                        <span class="text-slate-800 mt-0.5 block">{{ $application->phone }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Institution / Affiliation</span>
                        <span class="text-slate-800 mt-0.5 block">{{ $application->institution_or_org ?? 'Independent Innovator' }}</span>
                    </div>
                </div>
            </x-npontu.card>
        </div>

        <!-- Right 1 Col: Status Evaluation Form -->
        <div>
            <x-npontu.card title="Evaluation & Decision" subtitle="Appraise and record committee determination">
                <form method="POST" action="{{ route('admin.grants.status', $application) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Determination Status <span class="text-rose-500">*</span></label>
                        <select name="status" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-bold">
                            <option value="submitted" {{ $application->status === 'submitted' ? 'selected' : '' }}>Submitted (Pending)</option>
                            <option value="under_review" {{ $application->status === 'under_review' ? 'selected' : '' }}>Under Review</option>
                            <option value="shortlisted" {{ $application->status === 'shortlisted' ? 'selected' : '' }}>Shortlisted for Pitch</option>
                            <option value="approved" {{ $application->status === 'approved' ? 'selected' : '' }}>Approved for Grant</option>
                            <option value="rejected" {{ $application->status === 'rejected' ? 'selected' : '' }}>Rejected / Closed</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Reviewer Evaluation Notes</label>
                        <textarea name="reviewer_notes" rows="4" placeholder="Record rationale, feedback, or conditional approval terms..." class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden leading-relaxed">{{ old('reviewer_notes', $application->reviewer_notes) }}</textarea>
                    </div>

                    <div class="pt-2">
                        <x-npontu.button type="submit" variant="primary" class="w-full">
                            <span>Update Decision</span>
                        </x-npontu.button>
                    </div>
                </form>

                @if ($application->reviewer)
                    <div class="mt-4 pt-4 border-t border-slate-100 text-[11px] font-mono text-slate-400">
                        Last reviewed by: {{ $application->reviewer->name }}
                    </div>
                @endif
            </x-npontu.card>
        </div>

    </div>
</x-layouts.app>
