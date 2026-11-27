<x-layouts.auth title="Confirm your e-mail">
    <span class="flex size-12 items-center justify-center rounded-full bg-uew-navy/10 text-uew-navy"><x-ui.icon name="mail" class="size-6" /></span>
    <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">Confirm your e-mail</h1>
    <p class="mt-1.5 text-sm text-slate-600">You're confirming <span class="font-semibold text-slate-900">{{ $email }}</span> for your UEW Innovation Hub account.</p>

    @error('code')
        <x-ui.alert tone="danger" class="mt-6">{{ $message }} <a href="{{ route('login') }}" class="font-semibold underline">Sign in</a> to get a new one.</x-ui.alert>
    @enderror

    <form method="POST" action="{{ $action }}" class="mt-8" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        <x-ui.button type="submit" class="w-full" size="lg" icon="check" ::disabled="busy">Confirm my e-mail</x-ui.button>
    </form>
    <p class="mt-6 text-xs text-slate-500">This link works once and expires 10 minutes after it was sent. Didn't create an account? Close this page; nothing will change.</p>
</x-layouts.auth>
