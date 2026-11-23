<x-emails.layout :subject="'Cockpit Access Alert — ' . $user->name">
    <p style="font-size: 14px; margin-top: 0;">Hello <strong>{{ $user->name }}</strong>,</p>

    <div class="card-box" style="border-left: 4px solid #3157d5;">
        <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: 700; color: #102a72;">
            Institutional Cockpit Sign-In Detected
        </p>
        <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
            <tr>
                <td style="padding: 4px 0; color: #64748b;">Account:</td>
                <td style="padding: 4px 0; font-weight: 600;">{{ $user->email }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 0; color: #64748b;">Role:</td>
                <td style="padding: 4px 0;"><span class="badge badge-info">{{ strtoupper($user->role) }}</span></td>
            </tr>
            <tr>
                <td style="padding: 4px 0; color: #64748b;">Timestamp:</td>
                <td style="padding: 4px 0; font-family: monospace;">{{ now()->format('d M Y | H:i:s') }} GMT</td>
            </tr>
            <tr>
                <td style="padding: 4px 0; color: #64748b;">IP Address:</td>
                <td style="padding: 4px 0; font-family: monospace;">{{ $ipAddress }}</td>
            </tr>
        </table>
    </div>

    <p style="font-size: 12px; color: #64748b;">
        This automated notification is dispatched pursuant to institutional SRE audit standards.
    </p>
</x-emails.layout>
