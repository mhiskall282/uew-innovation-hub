<x-emails.layout :subject="'New enquiry: ' . $contactMessage->subject" eyebrow="New enquiry" :mail="$message ?? null"
    :preheader="$contactMessage->name . ': ' . \Illuminate\Support\Str::limit($contactMessage->message, 110)"
    footer-note="You get this because enquiry notifications are on in your account settings.">
    <p style="margin: 0 0 16px;"><strong style="color: #0f172a;">{{ $contactMessage->name }}</strong> sent a message through the website contact form.</p>
    <x-emails.panel tone="gold">
        <div style="font-size: 11px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; color: #92400e;">Subject</div>
        <div style="margin: 2px 0 12px; font-size: 16px; font-weight: 800; color: #0f172a;">{{ $contactMessage->subject }}</div>
        <div style="white-space: pre-line;">{{ \Illuminate\Support\Str::limit($contactMessage->message, 600) }}</div>
    </x-emails.panel>
    <p style="margin: 0 0 4px;">Reply to this e-mail to answer {{ $contactMessage->name }} directly at <a href="mailto:{{ $contactMessage->email }}" style="color: #3157d5; font-weight: 600;">{{ $contactMessage->email }}</a>.</p>
    <x-emails.button :href="route('workspace.messages.show', $contactMessage)" tone="navy">Open in the workspace</x-emails.button>
</x-emails.layout>
