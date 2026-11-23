<x-layouts.site title="Contact us" description="Get in touch with the UEW Innovation Hub about projects, partnerships, funding or media enquiries.">
    <section class="border-b border-slate-200 bg-uew-canvas">
        <div class="mx-auto max-w-7xl px-6 py-14">
            <p class="text-xs font-bold tracking-widest text-uew-gold-dark uppercase">Contact</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Get in touch</h1>
            <p class="mt-3 max-w-2xl text-slate-600">Questions about a project, partnering with the Hub, funding or media requests. We usually reply within two working days.</p>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-10 px-6 py-12 lg:grid-cols-12">
        <aside class="space-y-5 lg:col-span-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
                <h2 class="text-sm font-bold text-uew-navy">UEW Innovation Hub</h2>
                <ul class="mt-5 space-y-4 text-sm">
                    <li class="flex gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-uew-navy/10 text-uew-navy"><x-ui.icon name="map-pin" class="size-4" /></span>
                        <div>
                            <p class="font-semibold text-slate-900">Visit</p>
                            <p class="text-slate-600">{{ $settings['address'] }}</p>
                            @if ($settings['map_url'])
                                <a href="{{ $settings['map_url'] }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-uew-royal hover:underline">Open in maps <x-ui.icon name="external" class="size-3" /></a>
                            @endif
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-uew-navy/10 text-uew-navy"><x-ui.icon name="mail" class="size-4" /></span>
                        <div>
                            <p class="font-semibold text-slate-900">Email</p>
                            <a href="mailto:{{ $settings['contact_email'] }}" class="text-uew-royal hover:underline">{{ $settings['contact_email'] }}</a>
                        </div>
                    </li>
                    @if ($settings['contact_phone'])
                        <li class="flex gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-uew-navy/10 text-uew-navy"><x-ui.icon name="phone" class="size-4" /></span>
                            <div>
                                <p class="font-semibold text-slate-900">Phone</p>
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings['contact_phone']) }}" class="text-slate-700 hover:text-uew-royal">{{ $settings['contact_phone'] }}</a>
                            </div>
                        </li>
                    @endif
                    @if ($settings['office_hours'])
                        <li class="flex gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-uew-navy/10 text-uew-navy"><x-ui.icon name="clock" class="size-4" /></span>
                            <div>
                                <p class="font-semibold text-slate-900">Office hours</p>
                                <p class="text-slate-600">{{ $settings['office_hours'] }}</p>
                            </div>
                        </li>
                    @endif
                </ul>
                @if ($socials)
                    <div class="mt-6 border-t border-slate-100 pt-5">
                        <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Follow us</p>
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($socials as $network => $url)
                                <li><a href="{{ $url }}" target="_blank" rel="noopener" class="inline-flex rounded-full bg-uew-canvas px-3 py-1 text-xs font-semibold text-uew-navy ring-1 ring-slate-200 hover:bg-white">{{ $network }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="rounded-2xl bg-uew-canvas p-6 text-sm">
                <p class="font-semibold text-slate-900">Looking for funding or a programme?</p>
                <p class="mt-1 text-slate-600">Apply through the programme page so your application reaches the review panel directly, and follow it from your account.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <x-ui.button :href="route('site.programs.index')" variant="gold" size="sm">Open programmes</x-ui.button>
                    @guest<x-ui.button :href="route('register')" variant="secondary" size="sm">Create an account</x-ui.button>@endguest
                </div>
            </div>
        </aside>

        <div class="lg:col-span-8">
            @if (session('contact_sent'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-8 text-center" role="status">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-white text-emerald-600 shadow-sm"><x-ui.icon name="check-circle" class="size-7" /></span>
                    <h2 class="mt-4 text-xl font-bold text-slate-900">Message sent</h2>
                    <p class="mt-2 text-slate-600">Thank you for contacting the UEW Innovation Hub. A member of the team will reply by email.</p>
                    <x-ui.button :href="route('site.contact')" variant="secondary" class="mt-6">Send another message</x-ui.button>
                </div>
            @else
                <form method="POST" action="{{ route('site.contact.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card sm:p-8" x-data="{ busy: false }" @submit="busy = true" novalidate>
                    @csrf
                    <h2 class="text-lg font-bold text-slate-900">Send us a message</h2>

                    @if ($errors->any())
                        <x-ui.alert tone="danger" class="mt-4">Please check the highlighted fields.</x-ui.alert>
                    @endif

                    <div class="hidden" aria-hidden="true">
                        <label for="company_website">Leave this field empty</label>
                        <input type="text" name="company_website" id="company_website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <x-ui.input name="name" label="Your name" required autocomplete="name" />
                        <x-ui.input name="email" type="email" label="Email address" required autocomplete="email" />
                        <x-ui.input name="phone" type="tel" label="Phone (optional)" autocomplete="tel" />
                        <x-ui.input name="subject" label="Subject" required placeholder="e.g. Partnership enquiry" />
                        <x-ui.textarea name="message" label="Message" rows="7" required wrap="sm:col-span-2" />
                    </div>

                    <p class="mt-5 text-xs text-slate-500">We use your details only to reply to this message, in line with the Data Protection Act, 2012 (Act 843).</p>

                    <div class="mt-6 flex justify-end">
                        <x-ui.button type="submit" size="lg" icon-right="arrow-right" ::disabled="busy">
                            <span x-show="! busy">Send message</span>
                            <span x-show="busy" x-cloak>Sending…</span>
                        </x-ui.button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-layouts.site>
