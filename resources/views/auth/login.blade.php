<x-layouts.auth title="Sign in">
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Welcome back</h1>
    <p class="mt-1.5 text-sm text-slate-500">Sign in to apply for programmes and follow your applications. Hub staff use the same page.</p>

    <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5" x-data="{ show: false, busy: false }" @submit="busy = true">
        @csrf

        <x-ui.input name="email" type="email" label="E-mail address" required autofocus autocomplete="username" placeholder="you@example.com" />

        <x-ui.field label="Password" for="password" name="password" required>
            <div class="relative">
                <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required autocomplete="current-password"
                       class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10 text-sm text-slate-900 shadow-xs focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
                <button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600" :aria-label="show ? 'Hide password' : 'Show password'">
                    <x-ui.icon name="eye" class="size-4" x-show="! show" />
                    <x-ui.icon name="eye-off" class="size-4" x-show="show" x-cloak />
                </button>
            </div>
        </x-ui.field>

        <div class="flex items-center justify-between gap-3">
            <x-ui.checkbox name="remember" label="Keep me signed in" />
            <a href="{{ route('password.request') }}" class="text-xs font-semibold text-uew-royal hover:underline">Forgot password?</a>
        </div>

        <x-ui.button type="submit" class="w-full" size="lg" ::disabled="busy">
            <span x-show="! busy">Sign in</span>
            <span x-show="busy" x-cloak>Signing in…</span>
        </x-ui.button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-600">
        New to the Hub? <a href="{{ route('register') }}" class="font-semibold text-uew-royal hover:underline">Create a free account</a>
    </p>
</x-layouts.auth>
