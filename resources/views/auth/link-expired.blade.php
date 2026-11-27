<x-layouts.auth title="Link expired">
    <span class="flex size-12 items-center justify-center rounded-full bg-amber-50 text-uew-gold-dark ring-1 ring-amber-200"><x-ui.icon name="clock" class="size-6" /></span>
    <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">This link has expired</h1>
    <p class="mt-1.5 text-sm text-slate-600">E-mail links work for a short time and only once, and a newer e-mail replaces older links. Ask for a fresh one below.</p>
    <div class="mt-8 grid gap-3">
        <x-ui.button :href="route('login')" size="lg" icon="user">Sign in to get a new verification e-mail</x-ui.button>
        <x-ui.button :href="route('password.request')" variant="secondary" size="lg" icon="lock">Reset my password</x-ui.button>
    </div>
</x-layouts.auth>
