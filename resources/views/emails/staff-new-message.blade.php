<x-emails.layout :subject="'New enquiry: ' . $contactMessage->subject">
    <p style="margin-top: 0;">{{ $contactMessage->name }} sent a message through the website contact form.</p>
    <div class="card-box">
        <p class="muted" style="margin: 0 0 4px 0;">Subject</p>
        <p style="margin: 0 0 12px 0; font-weight: 700;">{{ $contactMessage->subject }}</p>
        <p style="margin: 0; white-space: pre-line;">{{ Str::limit($contactMessage->message, 600) }}</p>
    </div>
    <p>Reply to this email to answer {{ $contactMessage->name }} directly ({{ $contactMessage->email }}).</p>
    <p style="text-align: center;"><a href="{{ route('workspace.messages.show', $contactMessage) }}" class="button">Open in the workspace</a></p>
    <p class="muted" style="margin-bottom: 0;">You receive this because enquiry notifications are on in your account settings.</p>
</x-emails.layout>
