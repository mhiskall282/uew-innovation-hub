<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Submitted — UEW Innovation Hub</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: 'Inter', sans-serif; } .font-mono { font-family: 'JetBrains Mono', monospace; }</style>
</head>
<body class="h-full flex items-center justify-center p-6 bg-slate-100">

    <div class="max-w-md w-full bg-white rounded-3xl p-8 border border-slate-200 shadow-xl text-center">
        <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-4 border border-emerald-200 shadow-inner">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
        </div>

        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Application Received</h1>
        <p class="text-xs text-slate-500 mt-2 leading-relaxed">
            Your research and innovation grant proposal has been successfully registered with the UEW Innovation Hub Review Committee.
        </p>

        <div class="my-6 p-4 rounded-xl bg-slate-50 border border-slate-200">
            <span class="text-[10px] font-mono uppercase font-semibold text-slate-400 block">Tracking Reference Number</span>
            <span class="text-base font-bold font-mono text-[#102a72] mt-1 block">{{ $ref }}</span>
        </div>

        <p class="text-xs text-slate-500 mb-6">
            A confirmation dispatch has been logged. Our peer review panel will evaluate your submission during the current academic cycle.
        </p>

        <div class="space-y-2">
            <a href="{{ route('landing') }}" class="block w-full py-2.5 rounded-lg text-xs font-semibold uppercase tracking-wider text-white bg-[#102a72] hover:bg-[#0a1b4d] transition-all">
                Return to Innovation Portal
            </a>
            <a href="{{ route('projects.index') }}" class="block w-full py-2.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 transition-all">
                Browse Active Initiatives
            </a>
        </div>
    </div>

</body>
</html>
