<x-emails.layout :subject="$rendered['subject']">
    <div class="prose">{!! $rendered['html'] !!}</div>

    @if ($actionUrl)
        <p style="text-align: center; margin: 28px 0 8px;">
            <a href="{{ $actionUrl }}" class="button">{{ $actionLabel }}</a>
        </p>
        <p class="muted" style="text-align: center; margin-top: 0;">Or paste this link into your browser:<br><span style="word-break: break-all;">{{ $actionUrl }}</span></p>
    @endif

    @if ($footerNote)
        <p class="muted" style="margin-top: 28px; border-top: 1px solid #e2e8f0; padding-top: 16px;">{!! $footerNote !!}</p>
    @endif
</x-emails.layout>
