<x-layouts.auth title="Reset your password">
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Forgot your password?</h1>
    <p class="mt-1.5 text-sm text-slate-500">Enter your e-mail address. If it belongs to an account, we will send a 6-digit code to reset your password.</p>

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        <x-ui.input name="email" type="email" label="E-mail address" required autofocus autocomplete="email" />
        <x-ui.button type="submit" class="w-full" size="lg" ::disabled="busy">Send reset code</x-ui.button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-600">
        Remembered it? <a href="{{ route('login') }}" class="font-semibold text-uew-royal hover:underline">Sign in</a>
    </p>
</x-layouts.auth>
