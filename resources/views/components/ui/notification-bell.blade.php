@props(['user'])
{{-- Bell with unread count and the five latest notifications. Links go through notifications.open, which marks them read. --}}
@php
    // A missing table (migration not yet run) must never take a page down.
    try {
        $unread = $user->unreadNotifications()->count();
        $latest = $user->notifications()->take(5)->get();
    } catch (\Illuminate\Database\QueryException $e) {
        report($e);
        $unread = 0;
        $latest = collect();
    }
@endphp
<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
    <button type="button" @click="open = ! open" :aria-expanded="open" aria-haspopup="true"
            class="relative inline-flex size-10 items-center justify-center rounded-full text-slate-600 ring-1 ring-slate-200 ring-inset transition hover:bg-slate-50 hover:text-uew-navy focus-visible:outline-2 focus-visible:outline-uew-royal">
        <x-ui.icon name="bell" class="size-5" />
        <span class="sr-only">Notifications{{ $unread ? ", {$unread} unread" : '' }}</span>
        @if ($unread)
            <span class="absolute -top-0.5 -right-0.5 flex min-w-5 items-center justify-center rounded-full bg-red-500 px-1 font-mono text-[10px] leading-5 font-bold text-white ring-2 ring-white" aria-hidden="true">{{ $unread > 99 ? '99+' : $unread }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition.origin.top.right
         class="absolute right-0 z-50 mt-2 w-[22rem] max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card-hover">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <p class="text-sm font-bold text-slate-900">Notifications</p>
            @if ($unread)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-uew-royal hover:underline">Mark all read</button>
                </form>
            @endif
        </div>
        <ul class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
            @forelse ($latest as $notification)
                <li>
                    <a href="{{ route('notifications.open', $notification->id) }}" @class(['flex gap-3 px-4 py-3 hover:bg-slate-50', 'bg-blue-50/40' => ! $notification->read_at])>
                        <span @class(['mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full', 'bg-uew-navy text-white' => ! $notification->read_at, 'bg-slate-100 text-slate-500' => $notification->read_at])>
                            <x-ui.icon :name="$notification->data['icon'] ?? 'bell'" class="size-4" />
                        </span>
                        <span class="min-w-0">
                            <span class="block text-[13px] font-semibold text-slate-900">{{ $notification->data['title'] ?? 'Notification' }}</span>
                            <span class="mt-0.5 line-clamp-2 block text-xs text-slate-600">{{ $notification->data['body'] ?? '' }}</span>
                            <span class="mt-1 block text-[11px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                    </a>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-sm text-slate-500">You're all caught up.</li>
            @endforelse
        </ul>
        <a href="{{ route('notifications.index') }}" class="block border-t border-slate-100 bg-slate-50/60 px-4 py-2.5 text-center text-xs font-semibold text-uew-navy hover:bg-slate-100">See all notifications</a>
    </div>
</div>
