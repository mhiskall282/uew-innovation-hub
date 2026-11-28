<x-layouts.workspace :title="$broadcast->subject">
    <x-ui.page-header :title="$broadcast->subject" :back="route('workspace.broadcasts.index')" back-label="Broadcasts">
        <x-slot:actions><x-ui.badge :tone="$broadcast->status->tone()" size="md" dot>{{ $broadcast->status->label() }}</x-ui.badge></x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <x-ui.card title="Message as sent" description="Shown with a sample name" class="xl:col-span-2">
            <p class="text-sm font-semibold text-slate-900">{{ $preview['subject'] }}</p>
            <div class="prose-uew mt-4 rounded-xl border border-slate-100 bg-slate-50/60 p-5">{!! $preview['html'] !!}</div>
        </x-ui.card>
        <x-ui.card title="Delivery">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Recipients</dt><dd class="font-mono font-semibold">{{ number_format($broadcast->recipients_count) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Sent</dt><dd class="font-medium">{{ $broadcast->sent_at?->format('j M Y, H:i') ?? ($broadcast->status === \App\Enums\BroadcastStatus::Failed ? 'Failed. Check the queue (failed_jobs) and send a new broadcast.' : 'Sending…') }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">By</dt><dd class="font-medium">{{ $broadcast->author?->name ?? 'System' }}</dd></div>
                @if ($broadcast->program)<div class="flex justify-between gap-3"><dt class="text-slate-500">Programme</dt><dd class="text-right font-medium">{{ $broadcast->program->title }}</dd></div>@endif
            </dl>
            <div class="mt-5 border-t border-slate-100 pt-4 text-xs text-slate-500">
                <p class="font-semibold text-slate-600">Audience</p>
                <p class="mt-1">Interests: {{ implode(', ', $broadcast->interests()) ?: 'Any' }}</p>
                <p>Affiliation: {{ implode(', ', array_map(fn ($a) => \App\Enums\Affiliation::from($a)->label(), $broadcast->affiliations())) ?: 'Any' }}</p>
            </div>
        </x-ui.card>
    </div>
</x-layouts.workspace>
