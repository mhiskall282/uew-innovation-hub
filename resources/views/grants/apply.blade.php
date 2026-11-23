<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Apply for Innovation Grant / Incubation — UEW Innovation Hub</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: 'Inter', sans-serif; } .font-mono { font-family: 'JetBrains Mono', monospace; }</style>
</head>
<body class="bg-slate-50 text-slate-800 flex flex-col min-h-screen">

    <header class="bg-[#0a1b4d] text-white py-12 border-b border-blue-950">
        <div class="max-w-4xl mx-auto px-6">
            <a href="{{ route('landing') }}" class="inline-flex items-center gap-2 text-xs font-mono text-amber-400 hover:underline mb-4">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                <span>Return to Portal</span>
            </a>
            <div class="flex items-center gap-4">
                <img src="/images/innovation_hub_logo.jpg" alt="UEW Crest" class="h-14 w-14 rounded-xl object-contain bg-white p-1 ring-2 ring-amber-400/50">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Call for Innovation & Research Grant Applications</h1>
                    <p class="text-xs text-blue-200 mt-1 font-mono">University of Education, Winneba • Directorate of Research & Innovation</p>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-4xl mx-auto px-6 py-10 w-full -mt-6">
        <div class="bg-white rounded-3xl p-8 lg:p-10 border border-slate-200 shadow-xl">
            <div class="mb-8 pb-6 border-b border-slate-100">
                <h2 class="text-lg font-bold text-slate-900">Applicant & Initiative Profile</h2>
                <p class="text-xs text-slate-500 mt-1">Submit your proposal for competitive seed funding, laboratory incubation, and pedagogical testing support.</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                    <p class="font-bold mb-1">Please correct the following errors:</p>
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('grants.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Section: Personal Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Full Legal Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="applicant_name" value="{{ old('applicant_name') }}" required placeholder="e.g. John Kwame Okyere" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Email Address <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="applicant@..." class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Phone Number <span class="text-rose-500">*</span></label>
                        <input type="text" name="phone" value="{{ old('phone') }}" required placeholder="+233 ..." class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Institution / Department / Startup</label>
                        <input type="text" name="institution_or_org" value="{{ old('institution_or_org') }}" placeholder="e.g. Faculty of Science Education, UEW" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden">
                    </div>
                </div>

                <!-- Section: Project Proposal -->
                <div class="pt-6 border-t border-slate-100 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Project / Innovation Title <span class="text-rose-500">*</span></label>
                        <input type="text" name="project_title" value="{{ old('project_title') }}" required placeholder="e.g. Automated Soil Moisture & Nutrient IoT Probe for Cassava Farmers" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-semibold">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Research Pillar / Cluster <span class="text-rose-500">*</span></label>
                            <select name="category" required class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden">
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Requested Grant Amount (GHS) <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs font-mono text-slate-400">GH₵</span>
                                <input type="number" step="0.01" name="funding_requested" value="{{ old('funding_requested') }}" required placeholder="15000.00" class="w-full pl-10 pr-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-mono">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Proposal Abstract & Impact Statement <span class="text-rose-500">*</span></label>
                        <textarea name="summary" rows="4" required placeholder="Detail the specific educational or community problem, your proposed methodology, and how beneficiaries will be reached..." class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden leading-relaxed">{{ old('summary') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Upload Full Proposal PDF (Max 20MB)</label>
                        <input type="file" name="proposal_file" accept="application/pdf" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#102a72] file:text-white hover:file:bg-[#0a1b4d] cursor-pointer">
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="submit" class="px-6 py-3 rounded-xl text-xs font-bold uppercase tracking-wider text-white bg-gradient-to-r from-[#102a72] to-[#3157d5] hover:from-[#0a1b4d] hover:to-[#1e3a8a] shadow-lg shadow-blue-900/20 active:scale-[0.98] transition-all">
                        Submit Grant Application
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer class="bg-[#071336] text-blue-200/70 py-8 text-center text-xs border-t border-blue-900/50 mt-12">
        <p>© {{ date('Y') }} University of Education, Winneba (UEW) Innovation Hub. Protected by Act 843.</p>
    </footer>

</body>
</html>
