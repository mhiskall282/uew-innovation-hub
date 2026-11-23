<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>News & Insights — UEW Innovation Hub</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: 'Inter', sans-serif; } .font-mono { font-family: 'JetBrains Mono', monospace; }</style>
</head>
<body class="bg-slate-50 text-slate-800 flex flex-col min-h-screen">

    <!-- Top Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <a href="{{ route('landing') }}" class="flex items-center gap-3">
                <img src="/images/innovation_hub_logo.jpg" alt="UEW Crest" class="h-10 w-10 rounded-lg object-contain bg-white p-0.5">
                <div>
                    <span class="font-extrabold text-base text-[#102a72] block leading-tight">UEW INNOVATION HUB</span>
                    <span class="text-[10px] font-mono uppercase text-amber-600 block">News & Research Insights</span>
                </div>
            </a>
            <div class="flex items-center gap-4 text-xs font-semibold uppercase tracking-wider">
                <a href="{{ route('landing') }}" class="hover:text-[#3157d5] text-slate-600">Home</a>
                <a href="{{ route('projects.index') }}" class="hover:text-[#3157d5] text-slate-600">Projects</a>
                <a href="{{ route('grants.apply') }}" class="px-3.5 py-2 rounded-lg bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold transition-all">Apply for Grant</a>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-7xl mx-auto px-6 py-12 w-full">
        <div class="mb-10">
            <span class="px-3 py-1 rounded-full bg-blue-50 text-[#102a72] font-mono text-xs uppercase font-semibold border border-blue-200">
                Institutional Dispatches
            </span>
            <h1 class="text-3xl font-extrabold text-slate-900 mt-3 tracking-tight">News, Blogs & Innovation Stories</h1>
            <p class="text-sm text-slate-500 mt-1">Read the latest breakthroughs, thought leadership, and grant calls from our researchers.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse ($posts as $post)
                <article class="bg-white rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all flex flex-col justify-between overflow-hidden group">
                    <div class="p-6">
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-semibold uppercase bg-blue-50 text-[#102a72] border border-blue-200">
                                {{ $post->type }}
                            </span>
                            <span class="text-xs font-mono text-slate-400">
                                {{ $post->published_at ? $post->published_at->format('M d, Y') : 'Recent' }}
                            </span>
                        </div>

                        <h2 class="text-lg font-bold text-slate-900 group-hover:text-[#3157d5] transition-colors leading-snug">
                            <a href="{{ route('posts.show', $post) }}">{{ $post->title }}</a>
                        </h2>

                        <p class="text-xs text-slate-600 mt-2.5 leading-relaxed line-clamp-3">
                            {{ $post->summary ?? Str::limit(strip_tags($post->content), 120) }}
                        </p>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between text-xs">
                        <span class="text-slate-500 font-mono text-[11px]">
                            By {{ $post->author?->name ?? 'Hub Media Desk' }}
                        </span>
                        <a href="{{ route('posts.show', $post) }}" class="font-bold text-[#102a72] hover:text-[#3157d5] flex items-center gap-1 transition-colors">
                            <span>Read Article</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    </div>
                </article>
            @empty
                <div class="col-span-3 py-16 text-center text-slate-500 bg-white rounded-2xl border border-slate-200">
                    <p class="font-bold">No articles currently published.</p>
                </div>
            @endforelse
        </div>

        @if ($posts->hasPages())
            <div class="mt-8">
                {{ $posts->links() }}
            </div>
        @endif
    </main>

    <footer class="bg-[#071336] text-blue-200/70 py-8 text-center text-xs border-t border-blue-900/50">
        <p>© {{ date('Y') }} University of Education, Winneba (UEW) Innovation Hub. All rights reserved.</p>
    </footer>

</body>
</html>
