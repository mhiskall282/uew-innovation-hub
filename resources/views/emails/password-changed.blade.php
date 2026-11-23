<x-emails.layout subject="Your password was changed">
    <p style="margin-top: 0;">Hello {{ $user->name }},</p>
    <p>The password for your UEW Innovation Hub staff account ({{ $user->email }}) was changed on {{ now()->format('j F Y \a\t H:i') }} (GMT).</p>
    <div class="card-box">
        <p style="margin: 0;">If you made this change, you do not need to do anything.</p>
        <p style="margin: 8px 0 0 0;"><strong>If you did not</strong>, contact the Hub Director immediately so the account can be locked.</p>
    </div>
    <p style="text-align: center;"><a href="{{ route('login') }}" class="button">Sign in</a></p>
</x-emails.layout>
