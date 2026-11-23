<x-layouts.auth title="Create an account">
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create your account</h1>
    <p class="mt-1.5 text-sm text-slate-500">It takes a minute. We will e-mail you a code to confirm your address.</p>

    @unless ($open)
        <x-ui.alert tone="warning" class="mt-6">New registrations are paused at the moment. Please check back soon.</x-ui.alert>
    @else
        <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-5" x-data="{ show: false, busy: false, pw: '' }" @submit="busy = true">
            @csrf

            <div class="hidden" aria-hidden="true">
                <label for="company_website">Leave this field empty</label>
                <input type="text" name="company_website" id="company_website" tabindex="-1" autocomplete="off">
            </div>

            <x-ui.input name="name" label="Full name" required autofocus autocomplete="name" />
            <x-ui.input name="email" type="email" label="E-mail address" required autocomplete="email" hint="We send your verification code and application updates here." />

            <x-ui.field label="Password" for="password" name="password" required>
                <div class="relative">
                    <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required autocomplete="new-password" x-model="pw"
                           class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10 text-sm text-slate-900 shadow-xs focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
                    <button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600" :aria-label="show ? 'Hide password' : 'Show password'">
                        <x-ui.icon name="eye" class="size-4" x-show="! show" />
                        <x-ui.icon name="eye-off" class="size-4" x-show="show" x-cloak />
                    </button>
                </div>
                <div class="mt-2 grid grid-cols-4 gap-1" aria-hidden="true">
                    <span class="h-1 rounded-full" :class="pw.length >= 8 ? 'bg-emerald-500' : 'bg-slate-200'"></span>
                    <span class="h-1 rounded-full" :class="/[A-Z]/.test(pw) && /[a-z]/.test(pw) ? 'bg-emerald-500' : 'bg-slate-200'"></span>
                    <span class="h-1 rounded-full" :class="/\d/.test(pw) ? 'bg-emerald-500' : 'bg-slate-200'"></span>
                    <span class="h-1 rounded-full" :class="pw.length >= 12 ? 'bg-emerald-500' : 'bg-slate-200'"></span>
                </div>
                <p class="mt-1 text-xs text-slate-500">Use 12 or more characters with upper and lower case letters and a number.</p>
            </x-ui.field>

            <x-ui.input name="password_confirmation" :type="'password'" label="Confirm password" required autocomplete="new-password" />

            <x-ui.checkbox name="terms" required>
                <x-slot:label>I agree to the <a href="{{ url('/terms') }}" target="_blank" class="text-uew-royal underline">terms</a> and <a href="{{ url('/privacy') }}" target="_blank" class="text-uew-royal underline">privacy notice</a>.</x-slot:label>
            </x-ui.checkbox>

            <x-ui.button type="submit" class="w-full" size="lg" variant="gold" ::disabled="busy">
                <span x-show="! busy">Create account</span>
                <span x-show="busy" x-cloak>Creating your account…</span>
            </x-ui.button>
        </form>
    @endunless

    <p class="mt-8 text-center text-sm text-slate-600">
        Already have an account? <a href="{{ route('login') }}" class="font-semibold text-uew-royal hover:underline">Sign in</a>
    </p>
</x-layouts.auth>
