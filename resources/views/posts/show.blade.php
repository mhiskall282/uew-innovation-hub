<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $post->title }} — UEW Innovation Hub</title>

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
                    <span class="text-[10px] font-mono uppercase text-amber-600 block">News & Insights</span>
                </div>
            </a>
            <div class="flex items-center gap-4 text-xs font-semibold uppercase tracking-wider">
                <a href="{{ route('posts.index') }}" class="text-[#3157d5] flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    <span>All Articles</span>
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-4xl mx-auto px-6 py-12 w-full">
        <article class="bg-white rounded-3xl p-8 lg:p-12 border border-slate-200/90 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-semibold uppercase bg-blue-50 text-[#102a72] border border-blue-200">
                    {{ $post->type }}
                </span>
                <span class="text-xs font-medium text-slate-500">• {{ $post->category }}</span>
                <span class="text-xs font-mono text-slate-400 ml-auto">{{ $post->published_at ? $post->published_at->format('F d, Y') : 'Recent' }}</span>
            </div>

            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight mb-6">
                {{ $post->title }}
            </h1>

            <div class="flex items-center gap-3 pb-6 border-b border-slate-100 text-xs text-slate-500 font-mono">
                <span>By {{ $post->author?->name ?? 'UEW Innovation Hub Team' }}</span>
                <span>•</span>
                <span>Central Region, Ghana</span>
            </div>

            @if ($post->summary)
                <div class="my-6 p-4 rounded-xl bg-blue-50/60 border border-blue-100 text-sm text-[#102a72] font-medium leading-relaxed italic">
                    {{ $post->summary }}
                </div>
            @endif

            <div class="prose max-w-none text-slate-700 leading-relaxed text-sm sm:text-base space-y-4 pt-2">
                {!! nl2br(e($post->content)) !!}
            </div>
        </article>
    </main>

    <footer class="bg-[#071336] text-blue-200/70 py-8 text-center text-xs border-t border-blue-900/50">
        <p>© {{ date('Y') }} University of Education, Winneba (UEW) Innovation Hub. All rights reserved.</p>
    </footer>

</body>
</html>
