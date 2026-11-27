<x-emails.layout :subject="$rendered['subject']" :preheader="$preheader" :eyebrow="$eyebrow" :mail="$message ?? null" :footer-note="$footerNote">
    {!! $rendered['html'] !!}
    @if ($actionUrl)
        <x-emails.button :href="$actionUrl">{{ $actionLabel }}</x-emails.button>
        <p style="margin: 14px 0 0; text-align: center; font-size: 12px; line-height: 18px; color: #64748b;">
            Button not working? Copy this link into your browser:<br>
            <a href="{{ $actionUrl }}" style="color: #3157d5; word-break: break-all;">{{ $actionUrl }}</a>
        </p>
    @endif
</x-emails.layout>
