<x-layouts.workspace :title="$member->name">
    <x-ui.page-header :title="$member->name" :description="$member->email" :back="route('workspace.members.index')" back-label="Members">
        <x-slot:actions>
            <x-ui.badge :tone="$member->is_active ? 'green' : 'red'" size="md" dot>{{ $member->is_active ? 'Active' : 'Suspended' }}</x-ui.badge>
            <x-ui.confirm-form :action="route('workspace.members.toggle', $member)" method="PATCH"
                :message="$member->is_active ? 'Suspend ' . $member->name . '? They will be signed out and cannot sign in.' : 'Restore access for ' . $member->name . '?'"
                :label="$member->is_active ? 'Suspend account' : 'Restore account'" :variant="$member->is_active ? 'danger-ghost' : 'secondary'" icon="power" />
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.card title="Applications" padding="p-0">
                @if ($applications->isEmpty())
                    <x-ui.empty title="No applications yet" icon="inbox" class="py-10" />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($applications as $application)
                            <li class="flex flex-wrap items-center gap-4 px-5 py-3.5">
                                <div class="min-w-0 flex-1">
                                    @if ($application->isEditable())
                                        <p class="truncate font-semibold text-slate-900">{{ $application->program?->title }}</p>
                                    @else
                                        <a href="{{ route('workspace.applications.show', $application) }}" class="block truncate font-semibold text-slate-900 hover:text-uew-royal">{{ $application->program?->title }}</a>
                                    @endif
                                    <p class="font-mono text-[11px] text-slate-500">{{ $application->reference }}</p>
                                </div>
                                <x-ui.badge :tone="$application->status->tone()" dot>{{ $application->status->label() }}</x-ui.badge>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            @if ($member->bio)
                <x-ui.card title="Bio"><p class="text-sm whitespace-pre-line text-slate-700">{{ $member->bio }}</p></x-ui.card>
            @endif

            @php $level = $member->level(); @endphp
            <x-ui.card title="Badges & XP" :description="$level['name'] . ' · ' . number_format($member->xp) . ' XP' . ($member->public_profile ? ' · on the public community page' : ' · profile is private')" padding="p-0">
                @if ($achievements->isEmpty())
                    <x-ui.empty title="No badges yet" icon="trophy" class="py-10">Accepted applications earn a badge automatically. Award winners, finalists, alumni and mentors here.</x-ui.empty>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($achievements as $achievement)
                            <li class="flex flex-wrap items-center gap-4 px-5 py-3.5">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-50 text-uew-gold-dark ring-1 ring-amber-200"><x-ui.icon :name="$achievement->type->icon()" class="size-5" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-slate-900">{{ $achievement->title }}</p>
                                    <p class="text-xs text-slate-500">
                                        <x-ui.badge :tone="$achievement->type->tone()" size="xs">{{ $achievement->type->label() }}</x-ui.badge>
                                        +{{ $achievement->xp }} XP · {{ $achievement->awarded_at->format('j M Y') }}{{ $achievement->awarder ? ' · by ' . $achievement->awarder->name : ' · automatic' }}
                                    </p>
                                    @if ($achievement->description)<p class="mt-1 text-xs text-slate-600">{{ $achievement->description }}</p>@endif
                                </div>
                                <x-ui.confirm-form :action="route('workspace.achievements.destroy', $achievement)" :message="'Remove the badge “' . $achievement->title . '” and take back its XP?'" label="Remove" size="xs" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Award a badge">
                @include('workspace.members._award-form', ['types' => $achievementTypes, 'programs' => $programs])
            </x-ui.card>
            <x-ui.card title="Profile">
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        'Phone' => $member->phone, 'Affiliation' => $member->affiliation?->label(), 'Institution' => $member->institution,
                        'Department' => $member->department,
                        'Education' => \App\Support\Demographics::EDUCATION_LEVELS[$member->education_level ?? ''] ?? null,
                        'Gender' => \App\Support\Demographics::GENDERS[$member->gender ?? ''] ?? null,
                        'Age' => $member->age(), 'Region' => $member->region,
                        'Disability' => \App\Support\Demographics::DISABILITY[$member->disability ?? ''] ?? null, 'Verified' => $member->email_verified_at?->format('j M Y') ?? 'No',
                        'Profile completed' => $member->onboarded_at?->format('j M Y') ?? 'No', 'Announcements' => $member->marketing_opt_in ? 'Subscribed' : 'Off',
                        'Last sign-in' => $member->last_login_at?->diffForHumans() ?? 'Never', 'Joined' => $member->created_at->format('j M Y'),
                    ] as $label => $value)
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ $label }}</dt><dd class="text-right font-medium text-slate-900">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                </dl>
            </x-ui.card>
            <x-ui.card title="Interests">
                <div class="flex flex-wrap gap-1.5">
                    @forelse ($member->interests ?? [] as $interest)<x-ui.badge tone="navy">{{ $interest }}</x-ui.badge>@empty<p class="text-sm text-slate-500">None chosen.</p>@endforelse
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layouts.workspace>
