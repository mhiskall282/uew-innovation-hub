<x-layouts.portal title="My account" :heading="'Hello, ' . $user->firstName()" description="Your applications, saved drafts and programmes picked for your interests.">
    <x-slot:actions>
        <x-ui.button :href="route('site.programs.index')" variant="gold" icon-right="arrow-right">Browse programmes</x-ui.button>
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat label="In progress" :value="$counts['active']" icon="inbox" tone="royal" hint="Submitted, awaiting a decision" />
        <x-ui.stat label="Accepted" :value="$counts['accepted']" icon="check-circle" tone="green" />
        <x-ui.stat label="Drafts" :value="$counts['drafts']" icon="pencil" tone="gold" hint="Not yet submitted" />
        <x-ui.stat label="Profile" :value="$user->profileCompleteness() . '%'" icon="user" tone="purple" :href="route('portal.profile.edit')">
            <x-ui.progress class="mt-3" :value="$user->profileCompleteness()" />
        </x-ui.stat>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if ($drafts->isNotEmpty())
                <x-ui.alert tone="warning" title="Finish your drafts">
                    You have {{ $drafts->count() }} unsubmitted {{ Str::plural('application', $drafts->count()) }}. Drafts are not reviewed until you submit them.
                </x-ui.alert>
            @endif

            <x-ui.card title="My applications" padding="p-0">
                <x-slot:actions><x-ui.button :href="route('portal.applications.index')" variant="ghost" size="xs" icon-right="arrow-right">All</x-ui.button></x-slot:actions>
                @if ($applications->isEmpty())
                    <x-ui.empty title="You have not applied to anything yet" icon="inbox">
                        Find an open programme and apply in a few minutes.
                        <x-slot:action><x-ui.button :href="route('site.programs.index')" size="sm">See open programmes</x-ui.button></x-slot:action>
                    </x-ui.empty>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($applications->take(6) as $application)
                            <li class="flex flex-wrap items-center gap-4 px-5 py-4">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-uew-navy/10 text-uew-navy"><x-ui.icon :name="$application->program->type->icon()" class="size-5" /></span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ $application->isEditable() ? route('portal.apply', $application->program) : route('portal.applications.show', $application) }}" class="block truncate font-semibold text-slate-900 hover:text-uew-royal">{{ $application->program->title }}</a>
                                    <p class="text-xs text-slate-500">
                                        <span class="font-mono">{{ $application->reference }}</span> ·
                                        {{ $application->submitted_at ? 'Submitted '.$application->submitted_at->format('j M Y') : 'Edited '.$application->updated_at->diffForHumans() }}
                                    </p>
                                </div>
                                <x-ui.badge :tone="$application->status->tone()" dot>{{ $application->status->label() }}</x-ui.badge>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Recommended for you" description="Open programmes, matched to your interests first" padding="p-0">
                @if ($recommended->isEmpty())
                    <x-ui.empty title="Nothing new right now" icon="sparkles" class="py-10">
                        @if ($user->marketing_opt_in)
                            We will e-mail you when something opens.
                        @else
                            Switch on announcements in your <a class="text-uew-royal underline" href="{{ route('portal.profile.edit') }}">settings</a> to hear when something opens.
                        @endif
                    </x-ui.empty>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($recommended as $program)
                            <li class="px-5 py-4">
                                <p class="text-xs font-semibold text-uew-purple">{{ $program->type->label() }}@if ($program->focus_area) · {{ $program->focus_area }}@endif</p>
                                <a href="{{ route('site.programs.show', $program) }}" class="mt-0.5 block font-semibold text-slate-900 hover:text-uew-royal">{{ $program->title }}</a>
                                <p class="mt-1 text-xs text-slate-500">{{ $program->closes_at ? 'Closes '.$program->closes_at->format('j M Y') : 'Rolling deadline' }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            <x-ui.card title="Your interests">
                <div class="flex flex-wrap gap-2">
                    @forelse ($user->interests ?? [] as $interest)
                        <x-ui.badge tone="navy">{{ $interest }}</x-ui.badge>
                    @empty
                        <p class="text-sm text-slate-500">None chosen yet.</p>
                    @endforelse
                </div>
                <a href="{{ route('portal.profile.edit') }}" class="mt-4 inline-flex text-xs font-semibold text-uew-royal hover:underline">Edit interests</a>
            </x-ui.card>
        </div>
    </div>
</x-layouts.portal>
