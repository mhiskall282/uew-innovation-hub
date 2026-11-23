{{-- Shared error page. Included with $code, $title and $message. --}}
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · UEW Innovation Hub</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif
</head>
<body class="flex h-full items-center justify-center bg-uew-canvas px-6 font-sans text-slate-800 antialiased">
    <main class="w-full max-w-lg text-center">
        <img src="{{ asset('images/innovation_hub_logo.jpg') }}" alt="UEW Innovation Hub" width="64" height="64" class="mx-auto size-16 rounded-xl bg-white object-contain p-1 shadow ring-1 ring-amber-300/50">
        <p class="mt-8 font-mono text-sm font-bold tracking-widest text-uew-gold-dark">ERROR {{ $code }}</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ $title }}</h1>
        <p class="mt-3 text-slate-600">{{ $message }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ url('/') }}" class="rounded-lg bg-uew-navy px-4 py-2.5 text-sm font-semibold text-white hover:bg-uew-navy-deep">Go to the homepage</a>
            @auth
                <a href="{{ route('workspace.dashboard') }}" class="rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 ring-inset hover:bg-slate-50">Back to workspace</a>
            @endauth
        </div>
    </main>
</body>
</html>
