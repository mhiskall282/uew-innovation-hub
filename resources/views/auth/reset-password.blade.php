<x-layouts.auth title="Choose a new password">
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Choose a new password</h1>
    <p class="mt-1.5 text-sm text-slate-500">Enter the code sent to <span class="font-semibold text-slate-800">{{ $email }}</span> and your new password.</p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        @include('auth.partials.code-input')
        <x-ui.input name="password" type="password" label="New password" required autocomplete="new-password" hint="12 or more characters with upper and lower case letters and a number." />
        <x-ui.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
        <x-ui.button type="submit" class="w-full" size="lg" ::disabled="busy">Reset password</x-ui.button>
    </form>

    <p class="mt-6 text-sm text-slate-600">
        Didn't get a code? <a href="{{ route('password.request') }}" class="font-semibold text-uew-royal hover:underline">Send another</a>
    </p>
</x-layouts.auth>
