<x-layouts.site title="Unsubscribed" :flash="false">
    <section class="mx-auto max-w-xl px-6 py-24 text-center">
        <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"><x-ui.icon name="check-circle" class="size-7" /></span>
        <h1 class="mt-6 text-2xl font-extrabold text-slate-900">You have been unsubscribed</h1>
        <p class="mt-3 text-slate-600">{{ $email }} will no longer receive announcement e-mails. You will still get messages about your own applications and account.</p>
        <p class="mt-6 text-sm text-slate-500">Changed your mind? Switch announcements back on in your <a href="{{ route('portal.profile.edit') }}" class="font-semibold text-uew-royal hover:underline">profile settings</a>.</p>
    </section>
</x-layouts.site>
