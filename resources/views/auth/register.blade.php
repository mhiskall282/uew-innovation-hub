@use('App\Support\Demographics')
<x-layouts.auth title="Create an account" wide>
    <p class="text-xs font-bold tracking-widest text-uew-gold-dark uppercase">Step 1 of 3 · Your details</p>
    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Create your account</h1>
    <p class="mt-1.5 text-sm text-slate-500">One account for every Hub programme. We'll e-mail you a 6-digit code to confirm your address, then ask a few questions about your background.</p>

    @unless ($open)
        <x-ui.alert tone="warning" class="mt-6">New registrations are paused at the moment. Please check back soon.</x-ui.alert>
    @else
        <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-8" x-data="{ show: false, busy: false, pw: '' }" @submit="busy = true" novalidate>
            @csrf

            @if ($errors->any())
                <x-ui.alert tone="danger">Please check the highlighted fields.</x-ui.alert>
            @endif

            <div class="hidden" aria-hidden="true">
                <label for="company_website">Leave this field empty</label>
                <input type="text" name="company_website" id="company_website" tabindex="-1" autocomplete="off">
            </div>

            <fieldset class="space-y-5">
                <legend class="mb-4 text-sm font-bold text-uew-navy">Contact</legend>
                <x-ui.input name="name" label="Full name" required autofocus autocomplete="name" hint="As it appears on your ID or student records." />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-ui.input name="email" type="email" label="E-mail address" required autocomplete="email" />
                    <x-ui.input name="phone" type="tel" label="Phone number" required autocomplete="tel" inputmode="tel" placeholder="+233 24 123 4567" />
                </div>
            </fieldset>

            <fieldset class="space-y-5">
                <legend class="mb-1 text-sm font-bold text-uew-navy">About you</legend>
                <p class="text-xs text-slate-500">Used to check eligibility for targeted programmes and for anonymous reporting to funders. Reviewers do not see your gender.</p>
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-ui.select name="gender" label="Gender" :options="Demographics::GENDERS" placeholder="Choose" required />
                    <x-ui.input name="date_of_birth" type="date" label="Date of birth" required autocomplete="bday"
                        :max="now()->subYears(Demographics::MIN_AGE)->toDateString()" min="1920-01-02" />
                    <x-ui.select name="region" label="Region" :options="Demographics::REGIONS" placeholder="Choose" required />
                </div>
            </fieldset>

            <fieldset class="space-y-5">
                <legend class="mb-4 text-sm font-bold text-uew-navy">Security</legend>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-ui.field label="Password" for="password" name="password" required>
                        <div class="relative">
                            <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required autocomplete="new-password" x-model="pw"
                                   class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10 text-sm text-slate-900 shadow-xs focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
                            <button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-slate-600" :aria-label="show ? 'Hide password' : 'Show password'">
                                <x-ui.icon name="eye" class="size-4" x-show="! show" />
                                <x-ui.icon name="eye-off" class="size-4" x-show="show" x-cloak />
                            </button>
                        </div>
                    </x-ui.field>
                    <x-ui.input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" />
                </div>
                <div>
                    <div class="grid grid-cols-4 gap-1" aria-hidden="true">
                        <span class="h-1 rounded-full" :class="pw.length >= 8 ? 'bg-emerald-500' : 'bg-slate-200'"></span>
                        <span class="h-1 rounded-full" :class="/[A-Z]/.test(pw) && /[a-z]/.test(pw) ? 'bg-emerald-500' : 'bg-slate-200'"></span>
                        <span class="h-1 rounded-full" :class="/\d/.test(pw) ? 'bg-emerald-500' : 'bg-slate-200'"></span>
                        <span class="h-1 rounded-full" :class="pw.length >= 12 ? 'bg-emerald-500' : 'bg-slate-200'"></span>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">Use 12 or more characters with upper and lower case letters and a number.</p>
                </div>
            </fieldset>

            <x-ui.checkbox name="terms" required>
                <x-slot:label>I agree to the <a href="{{ url('/terms') }}" target="_blank" class="text-uew-royal underline">terms</a> and <a href="{{ url('/privacy') }}" target="_blank" class="text-uew-royal underline">privacy notice</a>, and confirm the details above are accurate.</x-slot:label>
            </x-ui.checkbox>

            <x-ui.button type="submit" class="w-full" size="lg" variant="gold" ::disabled="busy">
                <span x-show="! busy">Create account and get my code</span>
                <span x-show="busy" x-cloak>Creating your account…</span>
            </x-ui.button>
        </form>
    @endunless

    <p class="mt-8 text-center text-sm text-slate-600">
        Already have an account? <a href="{{ route('login') }}" class="font-semibold text-uew-royal hover:underline">Sign in</a>
    </p>
</x-layouts.auth>
