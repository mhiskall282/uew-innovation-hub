<x-emails.layout :subject="'Application update: ' . $application->reference_no" eyebrow="Application update" :mail="$message ?? null"
    :preheader="'Your application ' . $application->reference_no . ' is now: ' . $application->status->label()">
    <p style="margin: 0 0 16px;">Dear {{ $application->applicant_name }},</p>
    <p style="margin: 0 0 16px;">There is an update on your application <strong style="color: #0f172a;">{{ $application->project_title }}</strong>.</p>
    <x-emails.panel tone="navy">
        <div style="font-size: 11px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; color: #64748b;">Status</div>
        <div style="margin-top: 2px; font-size: 18px; font-weight: 800; color: #102a72;">{{ $application->status->label() }}</div>
        <div style="margin-top: 8px;">{{ $application->status->applicantMessage() }}</div>
        <div style="margin-top: 10px; font-size: 12px; color: #64748b;">Reference: <span style="font-family: Consolas, 'Courier New', monospace; color: #0f172a;">{{ $application->reference_no }}</span></div>
    </x-emails.panel>
    <x-emails.button :href="route('site.programs.index')">See open programmes</x-emails.button>
    <p style="margin: 18px 0 0; font-size: 13px; color: #64748b;">Questions about this decision? E-mail {{ config('hub.contact_email') }} and quote your reference.</p>
</x-emails.layout>
