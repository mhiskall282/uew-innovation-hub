<x-emails.layout :subject="'Grant Application Received — ' . $application->reference_no">
    <p style="font-size: 14px; margin-top: 0;">Dear <strong>{{ $application->applicant_name }}</strong>,</p>

    <p style="font-size: 13px; color: #475569;">
        Thank you for submitting your research and innovation proposal to the <strong>University of Education, Winneba (UEW) Innovation Hub</strong>.
    </p>

    <div class="card-box">
        <p style="margin: 0 0 8px 0; font-size: 11px; font-family: monospace; color: #64748b; text-transform: uppercase;">Tracking Reference Number</p>
        <p style="margin: 0 0 12px 0; font-size: 16px; font-weight: 800; font-family: monospace; color: #102a72;">{{ $application->reference_no }}</p>

        <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
            <tr>
                <td style="padding: 4px 0; color: #64748b;">Initiative Title:</td>
                <td style="padding: 4px 0; font-weight: 600; color: #1e293b;">{{ $application->project_title }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 0; color: #64748b;">Research Pillar:</td>
                <td style="padding: 4px 0; font-weight: 600; color: #1e293b;">{{ $application->category }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 0; color: #64748b;">Requested Funding:</td>
                <td style="padding: 4px 0; font-weight: 700; color: #047857;">GH₵ {{ number_format($application->funding_requested, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 0; color: #64748b;">Current Status:</td>
                <td style="padding: 4px 0;"><span class="badge badge-info">Submitted & Queued</span></td>
            </tr>
        </table>
    </div>

    <p style="font-size: 13px; color: #475569;">
        Your proposal is now in the review queue. The Academic Review Panel assesses all applications according to pedagogical relevance, innovation potential, and feasibility in Ghanaian communities.
    </p>

    <div style="text-align: center;">
        <a href="{{ url('/') }}" class="button">Visit Innovation Portal</a>
    </div>

    <p style="font-size: 11px; color: #94a3b8; margin-bottom: 0;">
        Please quote your tracking reference <strong>{{ $application->reference_no }}</strong> in any correspondence with the Directorate.
    </p>
</x-emails.layout>
