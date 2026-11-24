<x-layouts.auth title="Verify your e-mail">
    <span class="flex size-12 items-center justify-center rounded-xl bg-uew-navy/10 text-uew-navy"><x-ui.icon name="mail" class="size-6" /></span>
    <p class="mt-5 text-xs font-bold tracking-widest text-uew-gold-dark uppercase">Step 2 of 3 · Confirm your e-mail</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Check your inbox</h1>
    <p class="mt-1.5 text-sm text-slate-500">Enter the 6-digit code we sent to <span class="font-semibold text-slate-800">{{ $email }}</span>. It expires in 10 minutes.</p>

    <form method="POST" action="{{ route('verification.verify') }}" class="mt-8 space-y-5" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        @include('auth.partials.code-input')
        <x-ui.button type="submit" class="w-full" size="lg" ::disabled="busy">Verify e-mail</x-ui.button>
    </form>

    <div class="mt-6 flex items-center justify-between gap-3 text-sm">
        <form method="POST" action="{{ route('verification.resend') }}">
            @csrf
            <button type="submit" class="font-semibold text-uew-royal hover:underline">Send a new code</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-slate-500 hover:text-slate-800">Use a different account</button>
        </form>
    </div>

    <p class="mt-6 text-xs text-slate-500">No e-mail? Check your spam or promotions folder, then ask for a new code.</p>
</x-layouts.auth>
