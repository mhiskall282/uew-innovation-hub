<x-emails.layout :subject="'Determination Notification: ' . $application->reference_no">
    <p style="font-size: 14px; margin-top: 0;">Dear <strong>{{ $application->applicant_name }}</strong>,</p>

    <p style="font-size: 13px; color: #475569;">
        This is an official communication regarding your innovation grant proposal <strong>"{{ $application->project_title }}"</strong> (Ref: <code style="font-family: monospace;">{{ $application->reference_no }}</code>).
    </p>

    @php
        $badgeClass = match($application->status) {
            'approved' => 'badge-success',
            'shortlisted' => 'badge-gold',
            'under_review' => 'badge-info',
            'rejected' => 'badge-danger',
            default => 'badge-info',
        };
    @endphp

    <div class="card-box" style="border-left: 4px solid #102a72;">
        <p style="margin: 0 0 6px 0; font-size: 11px; font-family: monospace; color: #64748b; text-transform: uppercase;">Updated Application Status</p>
        <p style="margin: 0 0 12px 0;">
            <span class="badge {{ $badgeClass }}" style="font-size: 13px; padding: 6px 14px;">
                {{ strtoupper(str_replace('_', ' ', $application->status)) }}
            </span>
        </p>

        @if ($application->reviewer_notes)
            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed #cbd5e1;">
                <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 700; color: #334155; text-transform: uppercase;">Committee Evaluation Notes:</p>
                <p style="margin: 0; font-size: 13px; color: #1e293b; font-style: italic;">
                    "{{ $application->reviewer_notes }}"
                </p>
            </div>
        @endif
    </div>

    @if ($application->status === 'approved')
        <p style="font-size: 13px; color: #047857; font-weight: 600;">
            Congratulations! The Directorate will contact you regarding disbursement scheduling, ethics compliance, and laboratory onboarding at the UEW Innovation Hub.
        </p>
    @elseif ($application->status === 'shortlisted')
        <p style="font-size: 13px; color: #b45309;">
            Your project has been shortlisted for presentation. You will receive an invitation to present your prototype to the Dean's panel.
        </p>
    @endif

    <div style="text-align: center;">
        <a href="{{ url('/') }}" class="button">Innovation Hub Portal</a>
    </div>
</x-emails.layout>
