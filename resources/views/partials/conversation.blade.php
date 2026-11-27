{{-- Application conversation. Expects $messages, $action (post URL or null), $staffView (bool) --}}
<x-ui.card id="messages" title="Messages" :description="$staffView ? 'Visible to the applicant. Use internal notes for anything private.' : 'Ask the Hub team a question about this application.'" padding="p-0">
    <ol class="max-h-[32rem] space-y-4 overflow-y-auto px-5 py-5" aria-live="polite">
        @forelse ($messages as $message)
            @php $mine = $staffView ? $message->from_staff : ! $message->from_staff; @endphp
            <li @class(['flex', 'justify-end' => $mine])>
                <div @class(['max-w-[85%] rounded-2xl px-4 py-3', 'bg-uew-navy text-white rounded-br-md' => $mine, 'bg-slate-100 text-slate-800 rounded-bl-md' => ! $mine])>
                    <p @class(['text-[11px] font-semibold', 'text-uew-gold-light' => $mine, 'text-slate-500' => ! $mine])>
                        {{ $message->from_staff ? ($staffView ? ($message->author?->name ?? 'Staff') : 'UEW Innovation Hub') : ($message->author?->name ?? 'Applicant') }}
                    </p>
                    <p class="mt-1 text-sm leading-relaxed whitespace-pre-line">{{ $message->body }}</p>
                    <p @class(['mt-1.5 text-[10px]', 'text-white/60' => $mine, 'text-slate-500' => ! $mine])><time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('j M Y, H:i') }}</time></p>
                </div>
            </li>
        @empty
            <li class="py-6 text-center text-sm text-slate-500">No messages yet.</li>
        @endforelse
    </ol>

    @if ($action)
        <form method="POST" action="{{ $action }}" class="border-t border-slate-100 p-5" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            <x-ui.textarea name="body" :label="$staffView ? 'Reply to the applicant' : 'Your message'" rows="3" required maxlength="3000"
                :placeholder="$staffView ? 'e.g. Please send your updated budget before Friday.' : 'e.g. Can I add a team member after submitting?'" />
            <div class="mt-3 flex justify-end">
                <x-ui.button type="submit" icon="send" ::class="busy && 'opacity-60 pointer-events-none'">Send message</x-ui.button>
            </div>
        </form>
    @endif
</x-ui.card>
