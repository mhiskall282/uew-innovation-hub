@props(['href', 'tone' => 'blue'])
{{-- Bulletproof button: a padded table cell, so it stays a button in Outlook. UEW crest blue by default; red for urgent actions. --}}
@php
    $bg = match ($tone) {
        'red' => '#b3121c',
        default => '#25267a',
    };
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin: 28px auto 10px;">
    <tr>
        <td align="center" bgcolor="{{ $bg }}" style="border-radius: 10px; background-color: {{ $bg }}; border-bottom: 3px solid #e41824;">
            <a href="{{ $href }}" target="_blank" rel="noopener"
               style="display: inline-block; padding: 15px 34px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 20px; font-weight: 800; color: #ffffff; text-decoration: none; border-radius: 10px;">{{ $slot }}</a>
        </td>
    </tr>
</table>
