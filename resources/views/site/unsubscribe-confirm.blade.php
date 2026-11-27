<x-layouts.site title="Unsubscribe" :flash="false">
    <section class="mx-auto max-w-xl px-6 py-20 text-center">
        <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-uew-navy/10 text-uew-navy"><x-ui.icon name="mail" class="size-7" /></span>
        @if ($subscribed)
            <h1 class="mt-6 text-3xl font-extrabold tracking-tight text-slate-900">Stop announcement e-mails?</h1>
            <p class="mt-3 text-slate-600">{{ $email }} will stop getting news about new programmes and events. You'll still get e-mails about your own account and applications.</p>
            <form method="POST" action="{{ $action }}" class="mt-8">
                @csrf
                <x-ui.button type="submit" size="lg" icon="check">Unsubscribe</x-ui.button>
            </form>
        @else
            <h1 class="mt-6 text-3xl font-extrabold tracking-tight text-slate-900">You're already unsubscribed</h1>
            <p class="mt-3 text-slate-600">{{ $email }} doesn't get announcement e-mails.</p>
        @endif
        <p class="mt-6 text-sm text-slate-500">Changed your mind? Switch announcements back on in <a href="{{ route('portal.profile.edit') }}" class="font-semibold text-uew-royal hover:underline">your profile settings</a>.</p>
    </section>
</x-layouts.site>
