@props(['title' => 'Sign in', 'wide' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · UEW Innovation Hub</title>
    <link rel="icon" href="{{ asset('images/innovation_hub_logo.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-uew-canvas font-sans text-slate-800 antialiased">
<div class="flex min-h-full">
    {{-- Brand panel --}}
    <div class="relative isolate hidden w-[44%] flex-col justify-between overflow-hidden bg-uew-ink p-12 text-white lg:flex">
        <img src="{{ asset('images/uew/campus-north.jpg') }}" alt="" aria-hidden="true" class="absolute inset-0 -z-20 size-full object-cover opacity-50">
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-uew-ink/95 via-uew-ink/80 to-uew-navy/90"></div>
        <div class="absolute inset-y-0 right-0 -z-10 w-1 bg-gradient-to-b from-uew-gold via-uew-gold-light to-uew-red"></div>

        <a href="{{ route('site.home') }}" class="relative flex items-center gap-3">
            <img src="{{ asset('images/uew/uew-logo.png') }}" alt="University of Education, Winneba crest" width="48" height="47" class="size-12 rounded-full bg-white p-0.5">
            <span class="h-10 w-px bg-white/20" aria-hidden="true"></span>
            <x-ui.crest class="size-12" />
            <div>
                <p class="text-lg font-bold">UEW Innovation Hub</p>
                <p class="font-mono text-[10px] font-semibold tracking-widest text-uew-gold uppercase">University of Education, Winneba</p>
            </div>
        </a>

        <div class="relative max-w-md">
            <h2 class="text-3xl leading-tight font-extrabold tracking-tight">One account for every Hub opportunity.</h2>
            <p class="mt-4 text-sm leading-relaxed text-blue-100/75">
                Apply to grants, fellowships, incubation, competitions, training and events. Save drafts, upload documents and follow every decision in one place.
            </p>
            <ul class="mt-8 space-y-3 text-sm text-blue-100/85">
                <li class="flex items-center gap-3"><x-ui.icon name="sparkles" class="size-5 text-uew-gold" /> Programmes matched to your interests</li>
                <li class="flex items-center gap-3"><x-ui.icon name="bell" class="size-5 text-uew-gold" /> E-mail updates when your status changes</li>
                <li class="flex items-center gap-3"><x-ui.icon name="lock" class="size-5 text-uew-gold" /> Verified accounts; data protected under Act 843</li>
            </ul>
        </div>

        <p class="relative text-xs text-blue-100/50">© {{ now()->year }} University of Education, Winneba</p>
    </div>

    {{-- Form panel --}}
    <div class="flex flex-1 items-center justify-center px-5 py-10 sm:px-8">
        <div @class(['w-full', 'max-w-xl' => $wide, 'max-w-sm' => ! $wide])>
            <a href="{{ route('site.home') }}" class="mb-8 flex items-center gap-3 lg:hidden">
                <x-ui.crest class="size-11" />
                <div>
                    <p class="font-bold text-uew-navy">UEW Innovation Hub</p>
                    <p class="font-mono text-[10px] font-semibold tracking-widest text-uew-gold-dark uppercase">University of Education, Winneba</p>
                </div>
            </a>

            @if (session('status'))
                <x-ui.alert tone="success" class="mb-6">{{ session('status') }}</x-ui.alert>
            @endif

            {{ $slot }}

            <a href="{{ route('site.home') }}" class="mt-10 inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-uew-navy">
                <x-ui.icon name="arrow-left" class="size-3.5" /> Back to the website
            </a>
        </div>
    </div>
</div>
</body>
</html>
