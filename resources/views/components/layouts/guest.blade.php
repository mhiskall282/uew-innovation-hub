<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-900 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Sign In' }} — UEW Innovation Hub</title>

    <!-- Institutional Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4 bg-radial from-[#102a72] via-[#0a1b4d] to-[#040b20]">

    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-6">
            <div class="inline-block p-2 bg-white rounded-2xl shadow-xl ring-2 ring-amber-400/40 mb-3">
                <img src="/images/innovation_hub_logo.jpg" alt="UEW Innovation Hub Crest" class="h-16 w-16 object-contain">
            </div>
            <h2 class="text-xl font-bold text-white tracking-tight">University of Education, Winneba</h2>
            <p class="text-xs font-mono uppercase tracking-widest text-amber-400 mt-1">Innovation Hub Cockpit Gateway</p>
        </div>

        <!-- Glassmorphic Card -->
        <div class="bg-white/95 backdrop-blur-md rounded-2xl p-8 border border-white/20 shadow-2xl">
            {{ $slot }}
        </div>

        <p class="text-center text-xs text-blue-200/60 mt-6 font-mono">
            Protected by Institutional Role-Based Access Control & Act 843.
        </p>
    </div>

</body>
</html>
