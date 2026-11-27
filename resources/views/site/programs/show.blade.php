@use('App\Support\Format')
<x-layouts.site :title="$program->title" :description="$program->summary">
    @php
        [$state, $tone] = $program->availability();
        $user = auth()->user();
        $accepting = $program->isAcceptingApplications();
    @endphp

    <section class="relative overflow-hidden bg-uew-ink text-white">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_80%_10%,rgba(49,87,213,0.45),transparent_50%)]"></div>
        <div class="relative mx-auto max-w-7xl px-6 py-14">
            <a href="{{ route('site.programs.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-100/70 hover:text-white">
                <x-ui.icon name="arrow-left" class="size-3.5" /> All programmes
            </a>
            <div class="mt-5 flex flex-wrap items-center gap-2">
                <x-ui.badge tone="gold"><x-ui.icon :name="$program->type->icon()" class="size-3.5" /> {{ $program->type->label() }}</x-ui.badge>
                @if ($program->focus_area)<x-ui.badge tone="purple">{{ $program->focus_area }}</x-ui.badge>@endif
                <x-ui.badge :tone="$tone" dot>{{ $state }}</x-ui.badge>
            </div>
            <h1 class="mt-4 max-w-4xl text-3xl leading-tight font-extrabold tracking-tight sm:text-5xl">{{ $program->title }}</h1>
            <p class="mt-4 max-w-3xl text-lg text-blue-100/80">{{ $program->summary }}</p>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-10 px-6 py-12 lg:grid-cols-12">
        <div class="space-y-10 lg:col-span-8">
            @if ($program->description)
                <section class="prose-uew">
                    {!! Str::markdown($program->description, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
                </section>
            @endif

            @if ($program->eligibility)
                <section class="rounded-2xl border border-slate-200 bg-uew-canvas p-6">
                    <h2 class="flex items-center gap-2 text-lg font-bold text-slate-900"><x-ui.icon name="check-circle" class="size-5 text-emerald-600" /> Who can apply</h2>
                    <div class="prose-uew mt-3">{!! Str::markdown($program->eligibility, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
                </section>
            @endif

            @if ($program->fields())
                <section>
                    <h2 class="text-lg font-bold text-slate-900">What you will be asked</h2>
                    <p class="mt-1 text-sm text-slate-500">Prepare these answers before you start. You can save a draft and finish later.</p>
                    <ol class="mt-4 space-y-2">
                        @foreach ($program->fields() as $field)
                            <li class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm">
                                <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-uew-navy/10 font-mono text-[11px] font-bold text-uew-navy">{{ $loop->iteration }}</span>
                                <span class="flex-1 text-slate-800">{{ $field['label'] }} @if ($field['required'])<span class="text-red-500">*</span>@endif</span>
                                <span class="shrink-0 text-xs text-slate-500">{{ \App\Support\FormSchema::TYPES[$field['type']] ?? '' }}</span>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif
        </div>

        <aside class="lg:col-span-4">
            <div class="sticky top-24 space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
                    <dl class="space-y-3 text-sm">
                        @if ($program->type->isEvent() && $program->starts_at)
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Date</dt><dd class="text-right font-semibold text-slate-900">{{ $program->starts_at->format('l j F Y, H:i') }}</dd></div>
                        @endif
                        @if ($program->location)
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Location</dt><dd class="text-right font-semibold text-slate-900">{{ $program->location }}</dd></div>
                        @endif
                        @if ($program->funding_amount)
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Funding</dt><dd class="font-mono font-semibold text-emerald-700">up to {{ Format::money($program->funding_amount, 0) }}</dd></div>
                        @endif
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Opens</dt><dd class="text-right font-semibold text-slate-900">{{ $program->opens_at?->format('j M Y') ?? 'Now' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Closes</dt><dd class="text-right font-semibold text-slate-900">{{ $program->closes_at?->format('j M Y, H:i') ?? 'Rolling' }}</dd></div>
                        @if ($program->capacity)
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Places</dt><dd class="font-semibold text-slate-900">{{ number_format($program->capacity) }}</dd></div>
                        @endif
                    </dl>

                    <div class="mt-6 border-t border-slate-100 pt-6">
                        @if ($application && ! $application->isEditable())
                            <x-ui.alert tone="info" title="You have applied">
                                Status: <strong>{{ $application->status->label() }}</strong>.
                            </x-ui.alert>
                            <x-ui.button :href="route('portal.applications.show', $application)" class="mt-4 w-full" variant="secondary">View my application</x-ui.button>
                        @elseif (! $accepting)
                            <p class="text-sm text-slate-600">
                                @if ($program->hasClosed()) Applications for this programme have closed.
                                @elseif (! $program->hasOpened()) Applications open on {{ $program->opens_at->format('j F Y') }}.
                                @else All places have been filled. @endif
                            </p>
                        @elseif (! $user)
                            <x-ui.button :href="route('register')" variant="gold" size="lg" class="w-full">Create an account to apply</x-ui.button>
                            <p class="mt-3 text-center text-xs text-slate-500">Already registered? <a href="{{ route('login') }}" class="font-semibold text-uew-royal hover:underline">Sign in</a></p>
                        @elseif ($user->isStaff())
                            <p class="text-sm text-slate-600">Staff accounts cannot apply. Manage this programme from the <a class="font-semibold text-uew-royal hover:underline" href="{{ route('workspace.dashboard') }}">workspace</a>.</p>
                        @else
                            <x-ui.button :href="route('portal.apply', $program)" variant="gold" size="lg" class="w-full" icon-right="arrow-right">
                                {{ $application ? 'Continue my draft' : 'Apply now' }}
                            </x-ui.button>
                            @if ($program->closes_at)
                                <p class="mt-3 text-center text-xs text-slate-500">Deadline {{ $program->closes_at->diffForHumans() }}</p>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="rounded-2xl bg-uew-canvas p-5 text-sm text-slate-600">
                    Questions about this programme? <a href="{{ route('site.contact') }}" class="font-semibold text-uew-royal hover:underline">Contact the Hub</a>.
                </div>
            </div>
        </aside>
    </div>

    @if ($related->isNotEmpty())
        <section class="border-t border-slate-200 bg-uew-canvas py-14">
            <div class="mx-auto max-w-7xl px-6">
                <h2 class="text-xl font-bold text-slate-900">Other open {{ Str::lower($program->type->plural()) }}</h2>
                <div class="mt-6 grid gap-6 md:grid-cols-3">
                    @foreach ($related as $item)
                        @include('site.partials.program-card', ['program' => $item])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.site>
