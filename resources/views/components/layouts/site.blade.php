@props(['title' => null, 'description' => null, 'flash' => true])
@inject('siteSettings', 'App\Services\SettingsService')
@php
    $s = $siteSettings->all();
    $socials = $siteSettings->socialLinks();
    $footerPages = \App\Models\Page::footerLinks();
    $user = auth()->user();
    $pageTitle = $title ? "{$title} · UEW Innovation Hub" : 'UEW Innovation Hub · University of Education, Winneba';
    $metaDescription = $description ?? 'The UEW Innovation Hub turns research at the University of Education, Winneba into education, technology and community projects across Ghana.';
    $nav = [
        ['label' => 'Programmes', 'route' => 'site.programs.index', 'match' => 'site.programs.*'],
        ['label' => 'Projects', 'route' => 'site.projects.index', 'match' => 'site.projects.*'],
        ['label' => 'Publications', 'route' => 'site.publications.index', 'match' => 'site.publications.*'],
        ['label' => 'News', 'route' => 'site.news.index', 'match' => 'site.news.*'],
        ['label' => 'Partners', 'route' => 'site.partners.index', 'match' => 'site.partners.*'],
        ['label' => 'Contact', 'route' => 'site.contact', 'match' => 'site.contact*'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ asset('images/innovation_hub_logo.jpg') }}">
    <meta name="theme-color" content="#102a72">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('images/innovation_hub_logo.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-slate-800 antialiased selection:bg-uew-royal selection:text-white">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:shadow-lg">Skip to content</a>

    @if ($s['announcement_enabled'] && $s['announcement_text'])
        <div class="bg-gradient-to-r from-uew-gold to-uew-gold-light text-uew-ink">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-3 gap-y-1 px-6 py-2 text-center text-sm font-semibold">
                <x-ui.icon name="sparkles" class="size-4" />
                <span>{{ $s['announcement_text'] }}</span>
                @if ($s['announcement_url'])
                    <a href="{{ $s['announcement_url'] }}" class="underline underline-offset-2 hover:no-underline">Learn more <span aria-hidden="true">→</span></a>
                @endif
            </div>
        </div>
    @endif

    {{-- Institutional strip --}}
    <div class="hidden bg-uew-ink text-[11px] text-blue-100/80 sm:block">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-2">
            <span class="font-medium tracking-wide">University of Education, Winneba · Central Region, Ghana</span>
            <div class="flex items-center gap-5">
                @if ($s['contact_phone'])
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $s['contact_phone']) }}" class="hidden items-center gap-1.5 hover:text-uew-gold-light md:inline-flex">
                        <x-ui.icon name="phone" class="size-3.5" /> {{ $s['contact_phone'] }}
                    </a>
                @endif
                <a href="mailto:{{ $s['contact_email'] }}" class="inline-flex items-center gap-1.5 hover:text-uew-gold-light">
                    <x-ui.icon name="mail" class="size-3.5" /> {{ $s['contact_email'] }}
                </a>
            </div>
        </div>
    </div>

    @include('components.layouts.partials.site-header', ['nav' => $nav, 'user' => $user])

    <main id="main" class="flex-1">
        @if ($flash && (session('status') || $errors->has('application')))
            <div class="mx-auto max-w-7xl px-6 pt-6" x-data="{ show: true }" x-show="show">
                @if (session('status'))
                    <x-ui.alert tone="success">
                        <div class="flex items-start justify-between gap-4"><span>{{ session('status') }}</span><button type="button" @click="show = false" class="opacity-70 hover:opacity-100"><span class="sr-only">Dismiss</span><x-ui.icon name="x" class="size-4" /></button></div>
                    </x-ui.alert>
                @endif
                @error('application')
                    <x-ui.alert tone="warning" class="mt-3">{{ $message }}</x-ui.alert>
                @enderror
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="bg-uew-ink text-blue-100/75">
        <div class="mx-auto grid max-w-7xl gap-10 px-6 py-14 md:grid-cols-12">
            <div class="md:col-span-4">
                <img src="{{ asset('images/uew/uew-logo-white.png') }}" alt="University of Education, Winneba" width="216" height="36" class="h-9 w-auto opacity-95">
                <div class="mt-5 flex items-center gap-3">
                    <x-ui.crest class="size-10" />
                    <span class="text-base font-bold text-white">UEW Innovation Hub</span>
                </div>
                <p class="mt-4 max-w-md text-sm leading-relaxed">{{ $s['footer_about'] }}</p>
                <p class="mt-4 inline-flex items-center gap-2 text-xs text-blue-100/60">
                    <x-ui.icon name="map-pin" class="size-4 text-uew-gold" /> {{ $s['address'] }}
                </p>
                @if ($socials)
                    <ul class="mt-5 flex flex-wrap gap-2">
                        @foreach ($socials as $network => $url)
                            <li><a href="{{ $url }}" target="_blank" rel="noopener" class="inline-flex rounded-full bg-white/5 px-3 py-1 text-xs font-semibold text-blue-100 ring-1 ring-white/10 hover:bg-white/10 hover:text-white">{{ $network }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="md:col-span-2">
                <h2 class="text-xs font-bold tracking-wider text-white uppercase">Explore</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('site.projects.index') }}" class="hover:text-white">Projects</a></li>
                    <li><a href="{{ route('site.publications.index') }}" class="hover:text-white">Publications</a></li>
                    <li><a href="{{ route('site.news.index') }}" class="hover:text-white">News</a></li>
                    <li><a href="{{ route('site.partners.index') }}" class="hover:text-white">Partners</a></li>
                    <li><a href="{{ route('site.community.index') }}" class="hover:text-white">Community &amp; leaderboard</a></li>
                </ul>
            </div>

            <div class="md:col-span-2">
                <h2 class="text-xs font-bold tracking-wider text-white uppercase">Opportunities</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('site.programs.index') }}" class="hover:text-white">Open programmes</a></li>
                    <li><a href="{{ route('site.programs.index', ['type' => 'event']) }}" class="hover:text-white">Events</a></li>
                    @guest
                        <li><a href="{{ route('register') }}" class="hover:text-white">Create an account</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-white">Sign in</a></li>
                    @else
                        <li><a href="{{ $user->homeUrl() }}" class="hover:text-white">{{ $user->isStaff() ? 'Staff workspace' : 'My applications' }}</a></li>
                    @endguest
                </ul>
            </div>

            <div class="md:col-span-2">
                <h2 class="text-xs font-bold tracking-wider text-white uppercase">About</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @foreach ($footerPages as $link)
                        <li><a href="{{ route('site.page', $link['slug']) }}" class="hover:text-white">{{ $link['title'] }}</a></li>
                    @endforeach
                    <li><a href="{{ route('api.v1.index') }}" class="inline-flex items-center gap-1 hover:text-white"><x-ui.icon name="code" class="size-3.5" /> Open data API</a></li>
                </ul>
            </div>

            <div class="md:col-span-2">
                <h2 class="text-xs font-bold tracking-wider text-white uppercase">Contact</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('site.contact') }}" class="hover:text-white">Contact us</a></li>
                    <li><a href="mailto:{{ $s['contact_email'] }}" class="break-all hover:text-white">{{ $s['contact_email'] }}</a></li>
                    @if ($s['contact_phone'])<li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $s['contact_phone']) }}" class="hover:text-white">{{ $s['contact_phone'] }}</a></li>@endif
                    <li><a href="https://uew.edu.gh" class="inline-flex items-center gap-1 hover:text-white" rel="noopener" target="_blank">uew.edu.gh <x-ui.icon name="external" class="size-3.5" /></a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-6 py-5 text-xs text-blue-100/50">
                <p>© {{ now()->year }} University of Education, Winneba. All rights reserved. · Campus photos from Wikimedia Commons (CC BY / CC BY-SA).</p>
                <p>Personal data is processed in line with the Data Protection Act, 2012 (Act 843).</p>
            </div>
        </div>
    </footer>
</body>
</html>
