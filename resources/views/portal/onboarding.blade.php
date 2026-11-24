<x-layouts.site title="Complete your profile" :flash="false">
    <section class="bg-uew-canvas">
        <div class="mx-auto max-w-3xl px-6 py-12">
            <div class="text-center">
                <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-8 ring-emerald-50/50"><x-ui.icon name="check-circle" class="size-7" /></span>
                <p class="mt-5 text-xs font-bold tracking-widest text-uew-gold-dark uppercase">Step 3 of 3 · Your background</p>
                <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-900">Welcome, {{ $user->firstName() }}</h1>
                <p class="mx-auto mt-2 max-w-xl text-slate-600">Your e-mail is verified. Tell us about your background and interests so we can recommend the right opportunities and fill in your applications for you.</p>
            </div>

            <ol class="mx-auto mt-8 flex max-w-md items-center justify-center gap-3 text-xs font-semibold" aria-label="Progress">
                <li class="flex items-center gap-2 text-emerald-700"><span class="flex size-6 items-center justify-center rounded-full bg-emerald-600 text-white"><x-ui.icon name="check" class="size-3.5" /></span> Account</li>
                <li class="h-px w-10 bg-slate-300" aria-hidden="true"></li>
                <li class="flex items-center gap-2 text-emerald-700"><span class="flex size-6 items-center justify-center rounded-full bg-emerald-600 text-white"><x-ui.icon name="check" class="size-3.5" /></span> E-mail</li>
                <li class="h-px w-10 bg-slate-300" aria-hidden="true"></li>
                <li class="flex items-center gap-2 text-uew-navy"><span class="flex size-6 items-center justify-center rounded-full bg-uew-navy text-white">3</span> Profile</li>
            </ol>

            <form method="POST" action="{{ route('onboarding.store') }}" class="mt-10 space-y-6" x-data="{ busy: false }" @submit="busy = true">
                @csrf
                @if ($errors->any())
                    <x-ui.alert tone="danger">Please check the highlighted fields.</x-ui.alert>
                @endif

                @include('portal.partials.profile-fields')

                <div class="flex justify-end">
                    <x-ui.button type="submit" size="lg" variant="gold" icon-right="arrow-right" ::disabled="busy">Finish and go to my account</x-ui.button>
                </div>
            </form>
        </div>
    </section>
</x-layouts.site>
