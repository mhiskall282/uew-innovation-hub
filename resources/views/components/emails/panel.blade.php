@props(['tone' => 'slate'])
{{-- Highlighted box for references, statuses and security notes. --}}
@php
    [$bg, $border] = match ($tone) {
        'gold' => ['#fffbeb', '#f59e0b'],
        'navy' => ['#eef0fb', '#25267a'],
        'red' => ['#fdecec', '#e41824'],
        default => ['#f8fafc', '#cbd5e1'],
    };
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 20px 0;">
    <tr>
        <td style="background-color: {{ $bg }}; border-left: 4px solid {{ $border }}; border-radius: 8px; padding: 16px 20px; font-size: 14px; line-height: 22px; color: #1e293b;">
            {{ $slot }}
        </td>
    </tr>
</table>
