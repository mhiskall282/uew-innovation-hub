<x-emails.layout :subject="'Security Alert: UEW Account Password Changed'">
    <p style="font-size: 14px; margin-top: 0;">Hello <strong>{{ $user->name }}</strong>,</p>

    <div class="card-box" style="border-left: 4px solid #f59e0b;">
        <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: 700; color: #b45309;">
            Security Notice: Passkey Modified
        </p>
        <p style="margin: 0; font-size: 12px; color: #475569;">
            The password for your UEW Innovation Hub staff account (<code style="font-family: monospace;">{{ $user->email }}</code>) was successfully updated on <strong>{{ now()->format('d M Y \a\t H:i:s') }} GMT</strong>.
        </p>
    </div>

    <p style="font-size: 13px; color: #475569;">
        If you initiated this change, no further action is necessary. If you did not authorize this modification, please contact the Directorate of Research & Innovation immediately to freeze the account.
    </p>

    <div style="text-align: center;">
        <a href="{{ url('/login') }}" class="button">Access Staff Cockpit</a>
    </div>
</x-emails.layout>
