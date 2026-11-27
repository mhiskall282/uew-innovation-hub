<x-layouts.workspace title="Enquiries">
    <x-ui.page-header title="Enquiries" description="Messages sent through the public contact form." />

    <nav class="flex flex-wrap gap-2" aria-label="Filter by status">
        <a href="{{ route('workspace.messages.index') }}" @class(['inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 ring-inset', 'bg-uew-navy text-white ring-uew-navy' => ! $activeStatus, 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' => $activeStatus])>Inbox</a>
        @foreach ($statuses as $status)
            <a href="{{ route('workspace.messages.index', ['status' => $status->value]) }}" @class(['inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 ring-inset', 'bg-uew-navy text-white ring-uew-navy' => $activeStatus === $status, 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' => $activeStatus !== $status])>
                {{ $status->label() }} <span class="font-mono opacity-75">{{ $counts[$status->value] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" class="flex gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-card" role="search">
        @if ($activeStatus)<input type="hidden" name="status" value="{{ $activeStatus->value }}">@endif
        <label class="relative flex-1">
            <span class="sr-only">Search</span>
            <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-500" />
            <input type="search" name="search" value="{{ $search }}" placeholder="Name, e-mail, subject or text"
                   class="block w-full rounded-lg border border-slate-300 py-2 pr-3 pl-9 text-sm focus:border-uew-royal focus:ring-2 focus:ring-uew-royal/20 focus:outline-none">
        </label>
        <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
    </form>

    <x-ui.card padding="p-0">
        @if ($enquiries->isEmpty())
            <x-ui.empty title="No messages here" icon="chat" />
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($enquiries as $enquiry)
                    <li>
                        <a href="{{ route('workspace.messages.show', $enquiry) }}" class="flex items-start gap-4 px-5 py-4 hover:bg-slate-50/60">
                            <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-uew-gold' => $enquiry->status->value === 'new', 'bg-transparent' => $enquiry->status->value !== 'new'])></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <p @class(['truncate text-sm text-slate-900', 'font-bold' => $enquiry->status->value === 'new', 'font-medium' => $enquiry->status->value !== 'new'])>{{ $enquiry->name }} <span class="font-normal text-slate-500">· {{ $enquiry->email }}</span></p>
                                    <time class="shrink-0 text-xs text-slate-500" datetime="{{ $enquiry->created_at->toIso8601String() }}">{{ $enquiry->created_at->diffForHumans() }}</time>
                                </div>
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $enquiry->subject }}</p>
                                <p class="truncate text-xs text-slate-500">{{ Str::limit($enquiry->message, 140) }}</p>
                            </div>
                            <x-ui.badge :tone="$enquiry->status->tone()" size="xs">{{ $enquiry->status->label() }}</x-ui.badge>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="border-t border-slate-100 px-5 py-3">{{ $enquiries->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.workspace>
