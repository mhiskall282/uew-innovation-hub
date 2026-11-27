<x-layouts.auth title="Choose a new password">
    <span class="flex size-12 items-center justify-center rounded-full bg-uew-navy/10 text-uew-navy"><x-ui.icon name="lock" class="size-6" /></span>
    <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">Choose a new password</h1>
    <p class="mt-1.5 text-sm text-slate-600">For <span class="font-semibold text-slate-900">{{ $email }}</span>. You'll be signed out on every other device.</p>

    @error('code')
        <x-ui.alert tone="danger" class="mt-6">{{ $message }} <a href="{{ route('password.request') }}" class="font-semibold underline">Request a new link</a>.</x-ui.alert>
    @enderror

    <form method="POST" action="{{ $action }}" class="mt-8 space-y-5" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        <x-ui.input name="password" type="password" label="New password" required autocomplete="new-password" autofocus
            :hint="app()->isProduction() ? 'At least 12 characters, with upper and lower case letters and a number.' : 'At least 8 characters.'" />
        <x-ui.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
        <x-ui.button type="submit" class="w-full" size="lg" icon="check" ::disabled="busy">Save new password</x-ui.button>
    </form>
    <p class="mt-6 text-xs text-slate-500">This link works once and expires 10 minutes after it was sent.</p>
</x-layouts.auth>
