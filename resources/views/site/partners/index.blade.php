<x-layouts.site title="Partners" description="Funders, government agencies and institutions working with the UEW Innovation Hub.">
    <x-ui.page-hero eyebrow="Collaboration" title="Our partners" photo="creative-arts">
        {{ $total }} organisations fund, implement and advise on Hub projects. To partner with us, e-mail <a class="font-semibold hover:underline" href="mailto:{{ config('hub.contact_email') }}">{{ config('hub.contact_email') }}</a>.
    </x-ui.page-hero>

    <section class="mx-auto max-w-7xl space-y-12 px-6 py-12">
        @forelse ($groups as $type => $partners)
            <div>
                <h2 class="text-xs font-bold tracking-widest text-slate-500 uppercase">{{ Str::plural($type) }}</h2>
                <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($partners as $partner)
                        <li class="flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-uew-navy font-bold text-uew-gold-light">{{ Str::upper(Str::substr($partner->name, 0, 1)) }}</span>
                            <div class="min-w-0">
                                <p class="font-semibold leading-snug text-slate-900">{{ $partner->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $partner->projects_count }} {{ Str::plural('project', $partner->projects_count) }}</p>
                                @if ($partner->website)
                                    <a href="{{ $partner->website }}" target="_blank" rel="noopener nofollow" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-uew-royal hover:underline">
                                        Website <x-ui.icon name="external" class="size-3" />
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <x-ui.empty title="Partners will be listed here soon" icon="building" />
        @endforelse
    </section>
</x-layouts.site>
