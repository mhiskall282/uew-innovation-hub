<x-layouts.workspace title="Account settings">
    <x-ui.page-header title="Account settings" description="Your profile, notifications, password and signed-in devices." />

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <form method="POST" action="{{ route('workspace.profile.update') }}">
                @csrf
                @method('PUT')
                <x-ui.card title="Profile">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-ui.input name="name" label="Full name" :value="$user->name" required />
                        <x-ui.input name="department" label="Department or office" :value="$user->department" />
                        <x-ui.field label="E-mail" for="email-ro" class="sm:col-span-2" hint="Ask the Hub Director to change your e-mail or role.">
                            <input id="email-ro" value="{{ $user->email }}" disabled class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">
                        </x-ui.field>
                    </div>

                    @if ($canReceiveGrants || $canReceiveMessages)
                        <div class="mt-6 space-y-4 border-t border-slate-100 pt-5">
                            <p class="flex items-center gap-2 text-xs font-semibold tracking-wider text-slate-500 uppercase"><x-ui.icon name="bell" class="size-4" /> E-mail notifications</p>
                            @if ($canReceiveGrants)
                                <x-ui.checkbox name="notify_new_grants" label="New programme applications" :checked="$user->notify_new_grants" hint="An e-mail each time someone submits an application." />
                            @else
                                <input type="hidden" name="notify_new_grants" value="{{ $user->notify_new_grants ? 1 : 0 }}">
                            @endif
                            @if ($canReceiveMessages)
                                <x-ui.checkbox name="notify_contact_messages" label="New contact-form enquiries" :checked="$user->notify_contact_messages" />
                            @else
                                <input type="hidden" name="notify_contact_messages" value="{{ $user->notify_contact_messages ? 1 : 0 }}">
                            @endif
                        </div>
                    @endif

                    <x-slot:footer>
                        <div class="flex justify-end"><x-ui.button type="submit" icon="check">Save profile</x-ui.button></div>
                    </x-slot:footer>
                </x-ui.card>
            </form>

            <x-ui.card title="Signed-in devices" description="Sessions on other browsers and devices.">
                @if ($sessions->isEmpty())
                    <p class="text-sm text-slate-500">Session details are only available with the database session driver.</p>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($sessions as $session)
                            <li class="flex items-center gap-3 py-3">
                                <span class="flex size-9 items-center justify-center rounded-lg bg-slate-100 text-slate-500"><x-ui.icon name="device" class="size-5" /></span>
                                <div class="min-w-0 flex-1 text-sm">
                                    <p class="font-medium text-slate-900">{{ $session->agent }}</p>
                                    <p class="text-xs text-slate-500">{{ $session->ip }} · {{ $session->current ? 'This device' : 'Active '.$session->last_active->diffForHumans() }}</p>
                                </div>
                                @if ($session->current)<x-ui.badge tone="green" dot>Current</x-ui.badge>@endif
                            </li>
                        @endforeach
                    </ul>
                    @if ($sessions->count() > 1)
                        <form method="POST" action="{{ route('workspace.profile.sessions.destroy') }}" class="mt-4 flex flex-wrap items-end gap-3 border-t border-slate-100 pt-4">
                            @csrf
                            @method('DELETE')
                            <x-ui.input name="current_password" type="password" label="Confirm with your password" required wrap="flex-1" autocomplete="current-password" />
                            <x-ui.button type="submit" variant="danger-ghost" icon="logout">Sign out other devices</x-ui.button>
                        </form>
                    @endif
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Your access">
                <x-ui.badge :tone="$user->role->tone()" size="md">{{ $user->role->label() }}</x-ui.badge>
                <p class="mt-3 text-sm text-slate-600">{{ $user->role->description() }}</p>
                <p class="mt-4 text-xs text-slate-500">Last sign-in {{ $user->last_login_at?->format('j M Y, H:i') ?? '—' }}</p>
            </x-ui.card>

            <x-ui.card title="Change password">
                <form method="POST" action="{{ route('workspace.profile.password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <x-ui.input name="current_password" id="pw-current" type="password" label="Current password" required autocomplete="current-password" />
                    <x-ui.input name="password" type="password" label="New password" required autocomplete="new-password"
                        :hint="app()->isProduction() ? '12+ characters, upper and lower case and a number.' : 'At least 8 characters.'" />
                    <x-ui.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
                    <x-ui.button type="submit" icon="lock" class="w-full">Update password</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-layouts.workspace>
