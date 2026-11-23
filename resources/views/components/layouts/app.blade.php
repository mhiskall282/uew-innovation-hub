<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Cockpit' }} — UEW Innovation Hub</title>

    <!-- Institutional Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])


    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="h-full text-slate-800 bg-slate-50 flex overflow-hidden" x-data="{ sidebarOpen: false, sidebarCollapsed: false }">

    <!-- Left SRE Sidebar -->
    <aside 
        class="bg-[#0a1b4d] text-slate-200 flex flex-col z-30 transition-all duration-300 ease-in-out shrink-0 border-r border-blue-950/60 shadow-xl"
        :class="sidebarCollapsed ? 'w-20' : 'w-64'"
    >
        <!-- Brand & Crest -->
        <div class="h-16 px-4 flex items-center justify-between border-b border-blue-900/40 bg-[#071336]">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 overflow-hidden">
                <img src="/images/innovation_hub_logo.jpg" alt="UEW Crest" class="h-9 w-9 rounded-lg object-contain bg-white p-0.5 shadow-sm ring-1 ring-amber-400/40 shrink-0">
                <div x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity duration-200">
                    <span class="font-bold text-sm tracking-tight text-white block">UEW INNO-HUB</span>
                    <span class="text-[10px] font-mono uppercase tracking-wider text-amber-400 block -mt-0.5">Enterprise Cockpit</span>
                </div>
            </a>
            
            <button 
                @click="sidebarCollapsed = !sidebarCollapsed" 
                class="hidden md:flex p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-blue-900/50 transition-colors"
                title="Toggle Sidebar"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                </svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto">
            <div x-show="!sidebarCollapsed" class="px-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-blue-300/60">
                Core Operations
            </div>

            <!-- Dashboard / Telemetry -->
            <a 
                href="{{ route('dashboard') }}" 
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs font-medium transition-all group {{ request()->routeIs('dashboard') ? 'bg-[#3157d5] text-white shadow-sm shadow-blue-500/20' : 'text-slate-300 hover:text-white hover:bg-blue-900/40' }}"
            >
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('dashboard') ? 'text-amber-300' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span x-show="!sidebarCollapsed" class="truncate">Cockpit Telemetry</span>
            </a>

            <!-- Projects Subsystem -->
            <a 
                href="{{ route('projects.index') }}" 
                class="flex items-center justify-between px-3 py-2.5 rounded-lg text-xs font-medium transition-all group {{ request()->routeIs('projects.*') ? 'bg-[#3157d5] text-white shadow-sm shadow-blue-500/20' : 'text-slate-300 hover:text-white hover:bg-blue-900/40' }}"
            >
                <div class="flex items-center gap-3 truncate">
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('projects.*') ? 'text-amber-300' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span x-show="!sidebarCollapsed" class="truncate">Projects & Initiatives</span>
                </div>
                <span x-show="!sidebarCollapsed" class="px-2 py-0.5 text-[10px] font-mono rounded bg-blue-950/60 text-amber-300 border border-blue-800/40">
                    {{ \App\Models\Project::count() }}
                </span>
            </a>

            <!-- Publications & Newsletters -->
            <a 
                href="{{ route('publications.index') }}" 
                class="flex items-center justify-between px-3 py-2.5 rounded-lg text-xs font-medium transition-all group {{ request()->routeIs('publications.*') ? 'bg-[#3157d5] text-white shadow-sm shadow-blue-500/20' : 'text-slate-300 hover:text-white hover:bg-blue-900/40' }}"
            >
                <div class="flex items-center gap-3 truncate">
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('publications.*') ? 'text-amber-300' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span x-show="!sidebarCollapsed" class="truncate">Publications & Papers</span>
                </div>
                <span x-show="!sidebarCollapsed" class="px-2 py-0.5 text-[10px] font-mono rounded bg-blue-950/60 text-amber-300 border border-blue-800/40">
                    {{ \App\Support\HubStats::publicationCount() }}
                </span>
            </a>

            <!-- Partner Organizations -->
            <a 
                href="{{ route('organizations.index') }}" 
                class="flex items-center justify-between px-3 py-2.5 rounded-lg text-xs font-medium transition-all group {{ request()->routeIs('organizations.*') ? 'bg-[#3157d5] text-white shadow-sm shadow-blue-500/20' : 'text-slate-300 hover:text-white hover:bg-blue-900/40' }}"
            >
                <div class="flex items-center gap-3 truncate">
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('organizations.*') ? 'text-amber-300' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span x-show="!sidebarCollapsed" class="truncate">Partner Organizations</span>
                </div>
            </a>

            @auth
                <div x-show="!sidebarCollapsed" class="pt-5 px-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-amber-300/80">
                    Cockpit Modules
                </div>

                @if(Auth::user()->canReviewApplications())
                <!-- Grant Applications Review -->
                <a 
                    href="{{ route('admin.grants.index') }}" 
                    class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium transition-all group {{ request()->routeIs('admin.grants.*') ? 'bg-[#3157d5] text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-blue-900/40' }}"
                >
                    <div class="flex items-center gap-3 truncate">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('admin.grants.*') ? 'text-amber-300' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span x-show="!sidebarCollapsed" class="truncate">Grant Applications</span>
                    </div>
                    <span x-show="!sidebarCollapsed" class="px-1.5 py-0.5 text-[10px] font-mono rounded bg-emerald-950 text-emerald-300 border border-emerald-800/40">
                        {{ \App\Support\HubStats::pendingGrantCount() }}
                    </span>
                </a>
                @endif

                @if(Auth::user()->canManagePosts())
                <!-- CMS News & Blogs -->
                <a 
                    href="{{ route('admin.posts.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition-all group {{ request()->routeIs('admin.posts.*') ? 'bg-[#3157d5] text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-blue-900/40' }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('admin.posts.*') ? 'text-amber-300' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                    </svg>
                    <span x-show="!sidebarCollapsed" class="truncate">CMS News & Posts</span>
                </a>
                @endif

                @if(Auth::user()->isSuperAdmin())
                    <div x-show="!sidebarCollapsed" class="pt-5 px-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-amber-300/80">
                        Administration & SRE
                    </div>

                    <!-- Staff Directory -->
                    <a 
                        href="{{ route('admin.users.index') }}" 
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition-all group {{ request()->routeIs('admin.users.*') ? 'bg-[#3157d5] text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-blue-900/40' }}"
                    >
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('admin.users.*') ? 'text-amber-300' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span x-show="!sidebarCollapsed" class="truncate">Staff Directory</span>
                    </a>

                    <!-- Security Audit Logs -->
                    <a 
                        href="{{ route('admin.activity-logs') }}" 
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition-all group {{ request()->routeIs('admin.activity-logs') ? 'bg-[#3157d5] text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-blue-900/40' }}"
                    >
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('admin.activity-logs') ? 'text-amber-300' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span x-show="!sidebarCollapsed" class="truncate">Security Audit Trail</span>
                    </a>
                @endif
            @endauth

            <div x-show="!sidebarCollapsed" class="pt-5 px-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-blue-300/60">
                Institutional Data
            </div>

            <!-- Public Showcase Link -->
            <a 
                href="{{ route('landing') }}" 
                class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-slate-300 hover:text-white hover:bg-blue-900/40 transition-all group"
            >
                <svg class="w-4 h-4 text-slate-400 group-hover:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                </svg>
                <span x-show="!sidebarCollapsed" class="truncate">Public Portal</span>
            </a>

            <!-- Data Export (Excel/CSV) -->
            <a 
                href="{{ route('projects.export') }}" 
                class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-slate-300 hover:text-white hover:bg-blue-900/40 transition-all group"
            >
                <svg class="w-4 h-4 text-slate-400 group-hover:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span x-show="!sidebarCollapsed" class="truncate">Export to Excel / CSV</span>
            </a>
        </nav>

        <!-- System & Status Footer -->
        <div class="p-3 border-t border-blue-900/40 bg-[#071336]">
            @auth
                <div class="flex items-center justify-between" x-show="!sidebarCollapsed">
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-amber-500 to-[#3157d5] text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-inner">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="truncate">
                            <p class="text-xs font-bold text-white truncate leading-tight">{{ Auth::user()->name }}</p>
                            <p class="text-[10px] font-mono text-amber-400 uppercase tracking-wider truncate">{{ str_replace('_', ' ', Auth::user()->role) }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Logout" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-blue-950 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            @else
                <div x-show="!sidebarCollapsed">
                    <a href="{{ route('login') }}" class="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-bold uppercase tracking-wider bg-amber-400 hover:bg-amber-300 text-slate-950 rounded-lg shadow-sm transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        Sign In Cockpit
                    </a>
                </div>
            @endauth
        </div>
    </aside>

    <!-- Main Workspace -->
    <div class="flex-1 flex flex-col h-full overflow-hidden">
        
        <!-- Top Telemetry & Control Bar -->
        <header class="h-16 bg-white border-b border-slate-200/90 px-6 flex items-center justify-between shrink-0 z-10 shadow-2xs">
            <div class="flex items-center gap-4">
                <button @click="sidebarCollapsed = !sidebarCollapsed" class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <div>
                    <h1 class="text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        <span>{{ $header ?? 'Cockpit Overview' }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono uppercase font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span>
                            Online SRE
                        </span>
                    </h1>
                </div>
            </div>

            <!-- Right Actions & User Pill -->
            <div class="flex items-center gap-3">
                <span class="hidden lg:inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100 text-xs font-mono text-slate-600 border border-slate-200">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ now()->format('d M Y | H:i') }} GMT</span>
                </span>

                @auth
                    @if(Auth::user()->canManageProjects())
                        <x-npontu.button href="{{ route('projects.create') }}" variant="primary" size="sm">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                            <span>New Project</span>
                        </x-npontu.button>
                    @endif
                @else
                    <x-npontu.button href="{{ route('login') }}" variant="secondary" size="sm">
                        Staff Sign In
                    </x-npontu.button>
                @endauth
            </div>
        </header>

        <!-- Flash Status Alerts -->
        @if (session('status'))
            <div class="mx-6 mt-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center justify-between shadow-2xs" role="alert">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mx-6 mt-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium" role="alert">
                <div class="flex items-center gap-2 mb-1.5 font-bold">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                    <span>Please review the form errors below:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-slate-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Scrollable Cockpit Content Canvas -->
        <main class="flex-1 overflow-y-auto p-6 space-y-6">
            {{ $slot }}
        </main>
    </div>

</body>
</html>
