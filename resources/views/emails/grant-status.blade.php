<x-emails.layout :subject="'Application update: ' . $application->reference_no">
    <p style="margin-top: 0;">Dear {{ $application->applicant_name }},</p>
    <p>There is an update on your application <strong>{{ $application->project_title }}</strong> ({{ $application->reference_no }}).</p>

    <div class="card-box">
        <p class="muted" style="margin: 0 0 6px 0;">Current status</p>
        <p style="margin: 0;"><span class="badge badge-{{ $application->status->tone() }}">{{ $application->status->label() }}</span></p>
        <p style="margin: 12px 0 0 0;">{{ $application->status->applicantMessage() }}</p>
    </div>

    <p style="text-align: center;"><a href="{{ route('site.programs.index') }}" class="button">See current programmes</a></p>
    <p class="muted" style="margin-bottom: 0;">If you have questions about this decision, reply to {{ config('hub.contact_email') }} quoting your reference number.</p>
</x-emails.layout>
