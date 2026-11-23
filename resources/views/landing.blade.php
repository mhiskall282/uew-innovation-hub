<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UEW Innovation Hub — University of Education, Winneba</title>

    <!-- Institutional Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (CDN fallback) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        uew: {
                            navy: {
                                deep: '#0a1b4d',
                                DEFAULT: '#102a72',
                                light: '#1e3a8a',
                            },
                            royal: '#3157d5',
                            purple: '#6d28d9',
                            gold: {
                                DEFAULT: '#f59e0b',
                                light: '#fbbf24',
                                dark: '#d97706',
                            }
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-[#3157d5] selection:text-white">

    <!-- Institutional Top Bar -->
    <div class="bg-[#071336] text-blue-200/80 px-6 py-2 text-[11px] font-mono border-b border-blue-900/50 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>UNIVERSITY OF EDUCATION, WINNEBA • CENTRAL REGION, GHANA</span>
        </div>
        <div class="flex items-center gap-4">
            <a href="mailto:inno-hub@uew.edu.gh" class="hover:text-amber-400 transition-colors">inno-hub@uew.edu.gh</a>
            <span>•</span>
            <span class="text-amber-400 font-bold">Research & Innovation Directorate</span>
        </div>
    </div>

    <!-- Navigation Header -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-xs">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <a href="{{ route('landing') }}" class="flex items-center gap-3.5 group">
                <img src="/images/innovation_hub_logo.jpg" alt="UEW Crest" class="h-12 w-12 rounded-xl object-contain bg-white p-0.5 shadow-sm ring-1 ring-amber-400/50 group-hover:ring-[#3157d5] transition-all">
                <div>
                    <span class="font-extrabold text-base tracking-tight text-[#102a72] block leading-tight">UEW INNOVATION HUB</span>
                    <span class="text-[11px] font-mono uppercase tracking-wider text-amber-600 block">Research • Pedagogy • Impact</span>
                </div>
            </a>

            <nav class="hidden md:flex items-center gap-7 text-xs font-semibold uppercase tracking-wider text-slate-600">
                <a href="#clusters" class="hover:text-[#3157d5] transition-colors">Pillars</a>
                <a href="#initiatives" class="hover:text-[#3157d5] transition-colors">Initiatives</a>
                <a href="{{ route('posts.index') }}" class="hover:text-[#3157d5] transition-colors">News & CMS</a>
                <a href="{{ route('publications.index') }}" class="hover:text-[#3157d5] transition-colors">Publications</a>
                <a href="{{ route('grants.apply') }}" class="text-amber-600 hover:text-amber-700 font-bold transition-colors">Apply for Grant</a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-semibold uppercase tracking-wider text-slate-700 bg-slate-100 hover:bg-slate-200 transition-all">
                    <span>Cockpit</span>
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-semibold uppercase tracking-wider text-white bg-gradient-to-r from-[#102a72] to-[#3157d5] hover:from-[#0a1b4d] hover:to-[#1e3a8a] shadow-sm shadow-blue-900/20 transition-all">
                    <span>Staff Sign In</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-b from-[#0a1b4d] via-[#102a72] to-[#152a65] text-white py-20 lg:py-28 overflow-hidden">
        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fbbf24_1px,transparent_1px)] [background-size:24px_24px]"></div>
        
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="max-w-3xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-mono text-amber-300 mb-6">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    <span>National Center of Excellence in Educational Innovation</span>
                </div>
                
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.1] mb-6">
                    Pioneering Solutions for <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-amber-200 to-white">Africa's Educational</span> & Social Transformation
                </h1>
                
                <p class="text-base sm:text-lg text-blue-100/90 leading-relaxed mb-8 font-light">
                    The University of Education, Winneba (UEW) Innovation Hub serves as the institutional incubator accelerating research-driven breakthroughs in STEM pedagogy, educational technologies, agricultural modernization, and inclusive community empowerment.
                </p>

                <div class="flex flex-wrap items-center gap-4">
                    <a href="{{ route('grants.apply') }}" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl text-xs font-bold uppercase tracking-wider text-slate-950 bg-gradient-to-r from-[#f59e0b] to-[#fbbf24] hover:from-[#d97706] hover:to-[#f59e0b] shadow-lg shadow-amber-500/25 transition-all active:scale-[0.98]">
                        <span>Apply for Grant / Incubation</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                    </a>

                    <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl text-xs font-semibold uppercase tracking-wider text-white bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-md transition-all active:scale-[0.98]">
                        <span>Browse Projects</span>
                    </a>
                    
                    <a href="{{ route('posts.index') }}" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl text-xs font-semibold uppercase tracking-wider text-blue-200 hover:text-white transition-all">
                        <span>Latest News & Dispatches</span>
                    </a>
                </div>
            </div>

            <!-- Telemetry Metrics Bar in Hero -->
            <div class="mt-16 pt-10 border-t border-white/10 grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <p class="text-xs font-mono uppercase tracking-wider text-blue-200/80">Active Initiatives</p>
                    <p class="text-3xl lg:text-4xl font-extrabold font-mono text-white mt-1">{{ $metrics['total_projects'] }}</p>
                    <p class="text-xs text-amber-400 mt-0.5">Across 6 Specialized Clusters</p>
                </div>
                <div>
                    <p class="text-xs font-mono uppercase tracking-wider text-blue-200/80">Beneficiaries Reached</p>
                    <p class="text-3xl lg:text-4xl font-extrabold font-mono text-amber-300 mt-1">{{ number_format($metrics['total_beneficiaries']) }}+</p>
                    <p class="text-xs text-blue-200/80 mt-0.5">Educators, Learners & Farmers</p>
                </div>
                <div>
                    <p class="text-xs font-mono uppercase tracking-wider text-blue-200/80">Disbursed Research Capital</p>
                    <p class="text-3xl lg:text-4xl font-extrabold font-mono text-white mt-1">GH₵ {{ number_format($metrics['total_funding'] / 1000, 1) }}k</p>
                    <p class="text-xs text-emerald-400 mt-0.5">Competitive Grant Allocations</p>
                </div>
                <div>
                    <p class="text-xs font-mono uppercase tracking-wider text-blue-200/80">Strategic Partners</p>
                    <p class="text-3xl lg:text-4xl font-extrabold font-mono text-white mt-1">{{ $metrics['total_organizations'] }}</p>
                    <p class="text-xs text-purple-300 mt-0.5">Global & Regional Alliances</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Research Pillars Section -->
    <section id="clusters" class="py-20 bg-white border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="px-3 py-1 rounded-full bg-blue-50 text-[#3157d5] font-mono text-xs uppercase font-semibold border border-blue-200">Innovation Clusters</span>
                <h2 class="text-3xl font-extrabold text-slate-900 mt-3 tracking-tight">Interdisciplinary Research Hubs</h2>
                <p class="text-sm text-slate-500 mt-2">Targeted research thrusts addressing urgent educational and socio-economic challenges across Ghana and West Africa.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Cluster 1: STEM & Robotics -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 hover:shadow-lg hover:border-[#3157d5]/40 transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-[#102a72] flex items-center justify-center font-bold mb-4 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-[#102a72] transition-colors">STEM & Robotics Pedagogy</h3>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed">Developing low-cost robotics learning kits, teacher upskilling toolkits, and open-source electronics curricula for basic and secondary schools.</p>
                </div>

                <!-- Cluster 2: Educational Tech -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 hover:shadow-lg hover:border-[#3157d5]/40 transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-purple-100 text-[#6d28d9] flex items-center justify-center font-bold mb-4 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-[#6d28d9] transition-colors">EdTech & Digital Pedagogy</h3>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed">Offline-first digital learning platforms, localized AI translation interfaces, and mobile learning resources designed for low-connectivity environments.</p>
                </div>

                <!-- Cluster 3: Inclusive Education -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 hover:shadow-lg hover:border-[#3157d5]/40 transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold mb-4 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-amber-700 transition-colors">Inclusive & Special Needs Tech</h3>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed">Assistive tactile hardware, braille transcription software, and pedagogical modifications for visually, hearing, and physically impaired learners.</p>
                </div>

                <!-- Cluster 4: Agriculture -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 hover:shadow-lg hover:border-[#3157d5]/40 transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold mb-4 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-emerald-700 transition-colors">Agrotech & Food Systems</h3>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed">Hydroponic vertical testbeds, soil-nutrient IoT sensor probes, and climate-resilient youth agribusiness incubators.</p>
                </div>

                <!-- Cluster 5: Community Water IoT -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 hover:shadow-lg hover:border-[#3157d5]/40 transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center font-bold mb-4 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-cyan-700 transition-colors">Clean Water & IoT Sensing</h3>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed">Solar-powered automated community filtration stations coupled with telemetry reporting for potable water security in Central Region.</p>
                </div>

                <!-- Cluster 6: Policy & Institutional -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 hover:shadow-lg hover:border-[#3157d5]/40 transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold mb-4 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-rose-700 transition-colors">Policy Briefs & Public IP</h3>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed">Translating empirical research findings into actionable ministerial guidelines, patent registrations, and open curriculum materials.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Initiatives Section -->
    <section id="initiatives" class="py-20 bg-slate-100">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-wrap items-end justify-between mb-12 gap-4">
                <div>
                    <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-800 font-mono text-xs uppercase font-semibold border border-amber-300">Active Dossiers</span>
                    <h2 class="text-3xl font-extrabold text-slate-900 mt-3 tracking-tight">Featured Innovation Initiatives</h2>
                    <p class="text-sm text-slate-500 mt-1">High-impact research deployments currently active across Ghana.</p>
                </div>
                <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-[#102a72] hover:text-[#3157d5] transition-colors">
                    <span>Explore Full Registry ({{ $metrics['total_projects'] }})</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($featuredProjects as $project)
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 text-[#102a72] border border-blue-200">
                                    {{ $project->category }}
                                </span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono uppercase font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    {{ $project->status }}
                                </span>
                            </div>

                            <h3 class="text-base font-bold text-slate-900 leading-snug hover:text-[#3157d5] transition-colors">
                                <a href="{{ route('projects.show', $project) }}">{{ $project->title }}</a>
                            </h3>

                            <p class="text-xs text-slate-600 mt-2 line-clamp-3 leading-relaxed">
                                {{ $project->summary }}
                            </p>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100">
                            <div class="flex items-center justify-between text-xs font-mono mb-2">
                                <span class="text-slate-500">Beneficiaries</span>
                                <span class="font-bold text-slate-800">{{ number_format($project->beneficiaries_reached) }} / {{ number_format($project->beneficiaries_target) }}</span>
                            </div>

                            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden mb-4">
                                <div class="bg-gradient-to-r from-[#102a72] to-[#3157d5] h-1.5 rounded-full" style="width: {{ $project->progress_percentage }}%"></div>
                            </div>

                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 text-[11px] font-mono">
                                    {{ $project->lead_researcher ?? 'UEW Directorate' }}
                                </span>
                                <a href="{{ route('projects.show', $project) }}" class="font-bold text-[#102a72] hover:text-[#3157d5] flex items-center gap-1 transition-colors">
                                    <span>Details</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Strategic Alliances & Partners -->
    <section id="partners" class="py-16 bg-white border-y border-slate-200/80">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <span class="text-xs font-mono uppercase tracking-widest text-slate-400">Institutional Collaborators & Granting Agencies</span>
            <div class="flex flex-wrap items-center justify-center gap-8 lg:gap-14 mt-8 opacity-80">
                <span class="font-bold text-lg text-slate-700 tracking-tight">UNESCO</span>
                <span class="font-bold text-lg text-slate-700 tracking-tight">Ghana Education Service</span>
                <span class="font-bold text-lg text-slate-700 tracking-tight">Mastercard Foundation</span>
                <span class="font-bold text-lg text-slate-700 tracking-tight">UNICEF Ghana</span>
                <span class="font-bold text-lg text-slate-700 tracking-tight">British Council</span>
                <span class="font-bold text-lg text-slate-700 tracking-tight">MESTI Ghana</span>
            </div>
        </div>
    </section>

    <!-- Publications & Knowledge Section -->
    <section id="publications" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="px-3 py-1 rounded-full bg-purple-50 text-[#6d28d9] font-mono text-xs uppercase font-semibold border border-purple-200">Knowledge Transfer</span>
                <h2 class="text-3xl font-extrabold text-slate-900 mt-3 tracking-tight">Directorate Publications & Newsletters</h2>
                <p class="text-sm text-slate-500 mt-2">Disseminating findings, peer-reviewed articles, and quarterly progress newsletters.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($publications as $pub)
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs flex flex-col justify-between">
                        <div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono uppercase font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                {{ $pub->type }}
                            </span>
                            <h3 class="text-sm font-bold text-slate-900 mt-2.5 leading-snug">
                                {{ $pub->title }}
                            </h3>
                            <p class="text-xs text-slate-500 mt-1 font-mono">
                                Published: {{ $pub->publish_date ? $pub->publish_date->format('M Y') : 'Recent' }}
                            </p>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[11px] text-slate-500 truncate max-w-[150px]">
                                {{ $pub->authors ?? 'UEW Directorate' }}
                            </span>

                            @if ($pub->pdf_file_path)
                                <a 
                                    href="{{ route('files.stream', ['type' => 'publications', 'filename' => $pub->pdf_file_path]) }}" 
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-[#102a72] bg-blue-50 hover:bg-blue-100 transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 2l3 3h-3V4z" clip-rule="evenodd" /></svg>
                                    <span>Download PDF</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Institutional Footer -->
    <footer class="bg-[#071336] text-blue-200/80 py-16 border-t border-blue-900/60">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-4 gap-10">
            <div class="md:col-span-2">
                <div class="flex items-center gap-3 mb-4">
                    <img src="/images/innovation_hub_logo.jpg" alt="UEW Crest" class="h-10 w-10 rounded-lg object-contain bg-white p-0.5">
                    <span class="text-white font-bold text-base tracking-tight">University of Education, Winneba</span>
                </div>
                <p class="text-xs text-blue-200/70 max-w-md leading-relaxed">
                    The Directorate of Research & Innovation Hub coordinates institutional research, intellectual property management, and commercialization of academic technologies in service of national development.
                </p>
                <p class="text-[11px] font-mono text-amber-400 mt-4">
                    P.O. Box 25, Winneba, Central Region, Ghana
                </p>
            </div>

            <div>
                <h4 class="text-white font-semibold text-xs uppercase tracking-wider mb-4 font-mono">Platform</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="{{ route('dashboard') }}" class="hover:text-white transition-colors">Cockpit Telemetry</a></li>
                    <li><a href="{{ route('projects.index') }}" class="hover:text-white transition-colors">Initiatives Registry</a></li>
                    <li><a href="{{ route('publications.index') }}" class="hover:text-white transition-colors">Publications & Media</a></li>
                    <li><a href="{{ route('projects.export') }}" class="hover:text-white transition-colors">Export Registry (.xlsx)</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-semibold text-xs uppercase tracking-wider mb-4 font-mono">Staff Access</h4>
                <p class="text-xs text-blue-200/70 mb-3">Access the SRE administration cockpit with institutional single sign-on.</p>
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs uppercase tracking-wider transition-all">
                    <span>Cockpit Login</span>
                </a>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-6 mt-12 pt-6 border-t border-blue-900/40 text-center text-xs text-blue-300/50 flex flex-wrap justify-between items-center gap-4">
            <p>© {{ date('Y') }} University of Education, Winneba (UEW). All Rights Reserved.</p>
            <p class="font-mono text-[11px]">Compliant with Data Protection Act, 2012 (Act 843) • Laravel 12 Enterprise</p>
        </div>
    </footer>

</body>
</html>
