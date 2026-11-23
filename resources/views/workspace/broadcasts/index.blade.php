<x-layouts.workspace title="Broadcasts">
    <x-ui.page-header title="Broadcasts" :description="number_format($subscribers) . ' verified members currently receive announcements.'">
        <x-slot:actions>
            <x-ui.button :href="route('workspace.broadcasts.create')" icon="plus">New broadcast</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card padding="p-0">
        @if ($broadcasts->isEmpty())
            <x-ui.empty title="No broadcasts yet" icon="bell">Announce new programmes, events and calls to members who asked to hear about them.
                <x-slot:action><x-ui.button :href="route('workspace.broadcasts.create')" icon="plus" size="sm">Write a broadcast</x-ui.button></x-slot:action>
            </x-ui.empty>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($broadcasts as $broadcast)
                    <li class="flex flex-wrap items-center gap-4 px-5 py-4">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-uew-gold/15 text-uew-gold-dark"><x-ui.icon name="bell" class="size-5" /></span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ $broadcast->status->value === 'draft' ? route('workspace.broadcasts.edit', $broadcast) : route('workspace.broadcasts.show', $broadcast) }}" class="block truncate font-semibold text-slate-900 hover:text-uew-royal">{{ $broadcast->subject }}</a>
                            <p class="text-xs text-slate-500">
                                {{ $broadcast->author?->name ?? 'System' }} ·
                                @if ($broadcast->sent_at) Sent {{ $broadcast->sent_at->format('j M Y, H:i') }} to {{ number_format($broadcast->recipients_count) }} @else Created {{ $broadcast->created_at->diffForHumans() }} @endif
                                @if ($broadcast->program) · {{ $broadcast->program->title }} @endif
                            </p>
                        </div>
                        <x-ui.badge :tone="$broadcast->status->tone()" dot>{{ $broadcast->status->label() }}</x-ui.badge>
                    </li>
                @endforeach
            </ul>
            <div class="border-t border-slate-100 px-5 py-3">{{ $broadcasts->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.workspace>
