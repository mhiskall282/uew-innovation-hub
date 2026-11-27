<x-emails.layout subject="Your password was changed" eyebrow="Security alert" :mail="$message ?? null"
    preheader="The password for your UEW Innovation Hub account was just changed. If this wasn't you, act now.">
    <p style="margin: 0 0 16px;">Hello {{ $user->firstName() }},</p>
    <p style="margin: 0 0 16px;">The password for your UEW Innovation Hub account was changed.</p>
    <x-emails.panel>
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="font-size: 14px; line-height: 22px;">
            <tr><td style="padding-right: 16px; color: #64748b;">Account</td><td style="color: #0f172a; font-weight: 600;">{{ $user->email }}</td></tr>
            <tr><td style="padding-right: 16px; color: #64748b;">When</td><td style="color: #0f172a; font-weight: 600;">{{ now()->format('j F Y, H:i') }} GMT</td></tr>
        </table>
    </x-emails.panel>
    <p style="margin: 0 0 8px;"><strong style="color: #0f172a;">Was this you?</strong> Then there's nothing to do.</p>
    <x-emails.panel tone="red">
        <strong style="color: #991b1b;">Wasn't you?</strong> Reset your password now. That signs you out everywhere else. Then tell the Hub at {{ app(\App\Services\SettingsService::class)->get('contact_email') }}.
    </x-emails.panel>
    <x-emails.button :href="route('password.request')" tone="navy">Reset my password</x-emails.button>
</x-emails.layout>
