<x-layouts.app title="Register New Project Initiative">
    <x-slot:header>
        Register New Initiative
    </x-slot:header>

    <div class="max-w-4xl mx-auto">
        <form method="POST" action="{{ route('projects.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Section 1: Core Initiative Identification -->
            <x-npontu.card title="Project Identification" subtitle="Institutional metadata and category classification">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Project Title <span class="text-rose-500">*</span></label>
                        <input 
                            type="text" 
                            name="title" 
                            value="{{ old('title') }}" 
                            required 
                            placeholder="e.g. Adaptive Robotics Pedagogy for Senior High Schools"
                            class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-medium"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Cluster / Category <span class="text-rose-500">*</span></label>
                        <select name="category" required class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Operational Status <span class="text-rose-500">*</span></label>
                        <select name="status" required class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Executive Summary / Scope</label>
                        <textarea 
                            name="summary" 
                            rows="3" 
                            placeholder="Provide a concise abstract of the initiative's objectives, pedagogical methodology, and expected national impact..."
                            class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden leading-relaxed"
                        >{{ old('summary') }}</textarea>
                    </div>

                </div>
            </x-npontu.card>

            <!-- Section 2: Timeline & Beneficiary Telemetry -->
            <x-npontu.card title="Metrics & Schedule" subtitle="Target beneficiaries and milestones">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Start Date</label>
                        <input 
                            type="date" 
                            name="start_date" 
                            value="{{ old('start_date', date('Y-m-d')) }}" 
                            class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-mono"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Target End Date</label>
                        <input 
                            type="date" 
                            name="end_date" 
                            value="{{ old('end_date') }}" 
                            class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-mono"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Target Beneficiaries Count</label>
                        <input 
                            type="number" 
                            name="beneficiaries_target" 
                            value="{{ old('beneficiaries_target', 0) }}" 
                            min="0"
                            class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-mono"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Beneficiaries Reached to Date</label>
                        <input 
                            type="number" 
                            name="beneficiaries_reached" 
                            value="{{ old('beneficiaries_reached', 0) }}" 
                            min="0"
                            class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-mono"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Grant Allocation (GHS)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-xs font-mono text-slate-500">GH₵</span>
                            <input 
                                type="number" 
                                step="0.01" 
                                name="grant_amount" 
                                value="{{ old('grant_amount') }}" 
                                placeholder="0.00"
                                class="w-full pl-10 pr-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-mono"
                            >
                        </div>
                    </div>

                </div>
            </x-npontu.card>

            <!-- Section 3: Institutional Lead & Partner Organizations -->
            <x-npontu.card title="Governance & Strategic Alliances" subtitle="Principal investigators, faculty, and collaborating agencies">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Lead Researcher / Coordinator</label>
                        <input 
                            type="text" 
                            name="lead_researcher" 
                            value="{{ old('lead_researcher') }}" 
                            placeholder="e.g. Dr. Emmanuel Kusi"
                            class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Faculty / Department</label>
                        <input 
                            type="text" 
                            name="department" 
                            value="{{ old('department') }}" 
                            placeholder="e.g. Department of ICT Education"
                            class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden"
                        >
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-2">Partner Organizations & Roles</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto p-3 bg-slate-50 rounded-xl border border-slate-200">
                        @foreach ($organizations as $org)
                            <div class="flex items-center justify-between p-2 rounded bg-white border border-slate-200/80">
                                <span class="text-xs font-medium text-slate-800 truncate mr-2">{{ $org->name }}</span>
                                <select name="organizations[{{ $org->id }}]" class="text-[11px] font-mono py-1 px-2 rounded border border-slate-300 bg-white">
                                    <option value="">None</option>
                                    <option value="Funding Partner">Funder</option>
                                    <option value="Implementing Partner">Implementer</option>
                                    <option value="Research Partner">Research</option>
                                    <option value="Technical Advisor">Advisor</option>
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-npontu.card>

            <!-- Section 4: Secure Document Upload -->
            <x-npontu.card title="Project Report Document" subtitle="Official validated PDF document">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Upload PDF Report (Max 20MB)</label>
                    <input 
                        type="file" 
                        name="report_file" 
                        accept="application/pdf"
                        class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#102a72] file:text-white hover:file:bg-[#0a1b4d] cursor-pointer"
                    >
                    <p class="text-[11px] text-slate-400 mt-1 font-mono">Document will be placed in encrypted private storage and served through authenticated proxy.</p>
                </div>
            </x-npontu.card>

            <!-- Submit Triggers -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <x-npontu.button href="{{ route('projects.index') }}" variant="secondary">
                    Cancel
                </x-npontu.button>
                <x-npontu.button type="submit" variant="primary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                    <span>Save Project Initiative</span>
                </x-npontu.button>
            </div>

        </form>
    </div>
</x-layouts.app>
