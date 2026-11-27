<x-dynamic-component :component="$layout" title="Notifications" description="Updates about your applications, messages, reports and badges.">
    @if ($layout === 'layouts.workspace')
        <x-ui.page-header title="Notifications" description="New applications, messages from applicants and progress reports." />
    @endif

    <div class="mx-auto max-w-3xl">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <nav class="inline-flex rounded-lg bg-slate-100 p-1 text-sm font-semibold" aria-label="Filter notifications">
                @foreach (['all' => 'All', 'unread' => 'Unread' . ($unread ? " ({$unread})" : '')] as $key => $label)
                    <a href="{{ route('notifications.index', $key === 'all' ? [] : ['filter' => $key]) }}"
                       @if ($filter === $key) aria-current="page" @endif
                       @class(['rounded-md px-3 py-1.5', 'bg-white text-uew-navy shadow-sm' => $filter === $key, 'text-slate-600 hover:text-slate-900' => $filter !== $key])>{{ $label }}</a>
                @endforeach
            </nav>
            @if ($unread)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" size="sm" icon="check">Mark all as read</x-ui.button>
                </form>
            @endif
        </div>

        <x-ui.card padding="p-0">
            @if ($notifications->isEmpty())
                <x-ui.empty :title="$filter === 'unread' ? 'No unread notifications' : 'No notifications yet'" icon="bell">
                    We'll let you know here when something changes.
                </x-ui.empty>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($notifications as $notification)
                        <li>
                            <a href="{{ route('notifications.open', $notification->id) }}" @class(['flex gap-4 px-5 py-4 hover:bg-slate-50', 'bg-blue-50/40' => ! $notification->read_at])>
                                <span @class(['flex size-10 shrink-0 items-center justify-center rounded-full', 'bg-uew-navy text-white' => ! $notification->read_at, 'bg-slate-100 text-slate-500' => $notification->read_at])>
                                    <x-ui.icon :name="$notification->data['icon'] ?? 'bell'" class="size-5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-start justify-between gap-3">
                                        <span class="text-sm font-semibold text-slate-900">{{ $notification->data['title'] ?? 'Notification' }}</span>
                                        @unless ($notification->read_at)<span class="mt-1.5 size-2 shrink-0 rounded-full bg-uew-royal" aria-label="Unread"></span>@endunless
                                    </span>
                                    <span class="mt-0.5 block text-sm text-slate-600">{{ $notification->data['body'] ?? '' }}</span>
                                    <span class="mt-1 block text-xs text-slate-500"><time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->format('j M Y, H:i') }}</time> · {{ $notification->created_at->diffForHumans() }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <div class="mt-6">{{ $notifications->links() }}</div>
    </div>
</x-dynamic-component>
