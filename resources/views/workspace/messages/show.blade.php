<x-layouts.workspace :title="$enquiry->subject">
    <x-ui.page-header :title="$enquiry->subject" :back="route('workspace.messages.index')" back-label="Enquiries">
        <x-slot:actions><x-ui.badge :tone="$enquiry->status->tone()" size="md">{{ $enquiry->status->label() }}</x-ui.badge></x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <x-ui.card class="xl:col-span-2">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 pb-4">
                <span class="flex size-10 items-center justify-center rounded-full bg-uew-navy/10 text-sm font-bold text-uew-navy">{{ Str::upper(Str::substr($enquiry->name, 0, 1)) }}</span>
                <div class="min-w-0">
                    <p class="font-semibold text-slate-900">{{ $enquiry->name }}</p>
                    <p class="text-xs text-slate-500">{{ $enquiry->email }}@if ($enquiry->phone) · {{ $enquiry->phone }}@endif · {{ $enquiry->created_at->format('j M Y, H:i') }}</p>
                </div>
            </div>
            <p class="mt-5 text-sm leading-relaxed whitespace-pre-line text-slate-800">{{ $enquiry->message }}</p>
            <x-slot:footer>
                <x-ui.button :href="'mailto:' . $enquiry->email . '?subject=' . rawurlencode('Re: ' . $enquiry->subject)" icon="mail" size="sm">Reply by e-mail</x-ui.button>
            </x-slot:footer>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card title="Status">
                <form method="POST" action="{{ route('workspace.messages.update', $enquiry) }}" class="space-y-3">
                    @csrf
                    @method('PATCH')
                    <x-ui.select name="status" :options="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" :value="$enquiry->status" />
                    <x-ui.button type="submit" variant="secondary" class="w-full" icon="check">Update status</x-ui.button>
                </form>
                @if ($enquiry->handler)
                    <p class="mt-3 text-xs text-slate-500">Last handled by {{ $enquiry->handler->name }}.</p>
                @endif
            </x-ui.card>
            <x-ui.card title="Delete">
                <p class="text-sm text-slate-600">Removes the message permanently. The audit log keeps a record that it existed.</p>
                <x-ui.confirm-form class="mt-4 block" :action="route('workspace.messages.destroy', $enquiry)" message="Delete this message permanently?" label="Delete message" />
            </x-ui.card>
        </div>
    </div>
</x-layouts.workspace>
