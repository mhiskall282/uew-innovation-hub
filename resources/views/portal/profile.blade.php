<x-layouts.portal title="Profile & settings" description="Keep your details up to date. Reviewers see them with your applications.">
    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('portal.profile.update') }}" class="space-y-6 lg:col-span-2">
            @csrf
            @method('PUT')
            @if ($errors->hasAny(['name', 'phone', 'affiliation', 'institution', 'department', 'bio', 'interests']))
                <x-ui.alert tone="danger">Please check the highlighted fields.</x-ui.alert>
            @endif
            @include('portal.partials.profile-fields')
            <div class="flex justify-end"><x-ui.button type="submit" size="lg" icon="check">Save profile</x-ui.button></div>
        </form>

        <div class="space-y-6">
            <x-ui.card title="Account">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">E-mail</dt><dd class="truncate font-medium text-slate-900">{{ $user->email }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Verified</dt><dd class="font-medium text-emerald-700">{{ $user->email_verified_at?->format('j M Y') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Member since</dt><dd class="font-medium text-slate-900">{{ $user->created_at->format('j M Y') }}</dd></div>
                </dl>
                <div class="mt-5"><p class="mb-1 text-xs font-semibold text-slate-600">Profile completeness</p><x-ui.progress :value="$user->profileCompleteness()" :label="null" /></div>
            </x-ui.card>

            <x-ui.card title="Change password">
                <form method="POST" action="{{ route('portal.profile.password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <x-ui.input name="current_password" type="password" label="Current password" required autocomplete="current-password" />
                    <x-ui.input name="password" type="password" label="New password" required autocomplete="new-password" />
                    <x-ui.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
                    <x-ui.button type="submit" icon="lock" class="w-full">Update password</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-layouts.portal>
