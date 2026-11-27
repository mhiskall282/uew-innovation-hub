@props(['subject' => 'UEW Innovation Hub', 'preheader' => null, 'eyebrow' => null, 'mail' => null, 'footerNote' => null])
{{--
    Shared e-mail shell in the official UEW colours (sampled from the crest:
    blue #25267a, red #e41824). Table layout and inline styles so it renders
    the same in Gmail, Outlook, Apple Mail and phones.

    Images are hosted, never attached: one public HTTPS URL (MAIL_ASSET_URL,
    default APP_URL), the way large senders do it. Attached (CID) images are
    blocked or shown as attachments by many work and school mail systems.
--}}
@php
    $settings = app(\App\Services\SettingsService::class);
    $assets = rtrim((string) (config('mail.asset_url') ?: config('app.url')), '/');
    $crest = $assets.'/images/uew/email-crest.png';
    $font = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
    $contact = $settings->get('contact_email');
    $blue = '#25267a';
    $blueDark = '#18186c';
    $red = '#e41824';
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $subject }}</title>
    <style>
        @media only screen and (max-width: 620px) {
            .container { width: 100% !important; }
            .px { padding-left: 22px !important; padding-right: 22px !important; }
            .code { font-size: 30px !important; letter-spacing: 7px !important; }
            .hide-sm { display: none !important; }
        }
        a { color: {{ $blue }}; }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #eceef6; -webkit-text-size-adjust: 100%;">
    @if ($preheader)
        {{-- Inbox preview line; hidden in the message itself. --}}
        <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all; font-size: 1px; line-height: 1px; color: #eceef6; opacity: 0;">{{ \Illuminate\Support\Str::limit($preheader, 140) }}&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #eceef6;">
        <tr>
            <td align="center" style="padding: 28px 12px;">
                <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="width: 600px; max-width: 600px;">

                    {{-- Header: crest on a white disc, UEW blue band --}}
                    <tr>
                        <td style="background-color: {{ $blue }}; border-radius: 16px 16px 0 0; padding: 24px 32px;" class="px">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="76" valign="middle" style="padding-right: 16px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td bgcolor="#ffffff" style="background-color: #ffffff; border-radius: 36px; padding: 4px; line-height: 0;">
                                                    <img src="{{ $crest }}" width="64" height="63" alt="University of Education, Winneba crest"
                                                         style="display: block; width: 64px; height: 63px; border: 0; outline: none; text-decoration: none;">
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="middle" style="font-family: {{ $font }};">
                                        <div style="font-size: 20px; line-height: 24px; font-weight: 800; color: #ffffff; letter-spacing: -0.2px;">UEW Innovation Hub</div>
                                        <div style="margin-top: 3px; font-size: 11px; line-height: 16px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; color: #ffd6d8;">University of Education, Winneba</div>
                                    </td>
                                    <td class="hide-sm" align="right" valign="middle" style="font-family: {{ $font }}; font-size: 11px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; color: #c9cbf0;">
                                        Education<br>for Service
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    {{-- Crest red accent --}}
                    <tr>
                        <td height="5" style="background-color: {{ $red }}; font-size: 0; line-height: 0;">&nbsp;</td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td class="px" style="background-color: #ffffff; padding: 36px 40px 30px; font-family: {{ $font }}; font-size: 15px; line-height: 25px; color: #1f2937;">
                            @if ($eyebrow)
                                <div style="margin: 0 0 16px; font-size: 11px; line-height: 16px; font-weight: 800; letter-spacing: 1.6px; text-transform: uppercase; color: #b3121c;">{{ $eyebrow }}</div>
                            @endif
                            {{ $slot }}
                        </td>
                    </tr>

                    @if ($footerNote)
                        <tr>
                            <td class="px" style="background-color: #ffffff; padding: 0 40px 28px; font-family: {{ $font }};">
                                <div style="border-top: 1px solid #e5e7eb; padding-top: 16px; font-size: 12px; line-height: 19px; color: #6b7280;">{!! $footerNote !!}</div>
                            </td>
                        </tr>
                    @endif

                    {{-- Footer --}}
                    <tr>
                        <td class="px" style="background-color: {{ $blueDark }}; border-radius: 0 0 16px 16px; padding: 24px 40px; font-family: {{ $font }}; font-size: 12px; line-height: 19px; color: #c9cbf0;">
                            <div style="font-weight: 700; color: #ffffff;">UEW Innovation Hub &middot; University of Education, Winneba</div>
                            <div>{{ $settings->get('address') }}</div>
                            @if ($contact)
                                <div style="margin-top: 6px;">Questions? <a href="mailto:{{ $contact }}" style="color: #ffffff; font-weight: 600;">{{ $contact }}</a></div>
                            @endif
                            <div style="margin-top: 14px;">
                                <a href="{{ route('site.home') }}" style="color: #ffffff; text-decoration: none; font-weight: 600;">Website</a>
                                <span style="color: #6f72c4;">&nbsp;&middot;&nbsp;</span>
                                <a href="{{ route('site.programs.index') }}" style="color: #ffffff; text-decoration: none; font-weight: 600;">Programmes</a>
                                <span style="color: #6f72c4;">&nbsp;&middot;&nbsp;</span>
                                <a href="{{ route('site.gallery.index') }}" style="color: #ffffff; text-decoration: none; font-weight: 600;">Gallery</a>
                                <span style="color: #6f72c4;">&nbsp;&middot;&nbsp;</span>
                                <a href="{{ route('site.page', 'privacy') }}" style="color: #ffffff; text-decoration: none; font-weight: 600;">Privacy</a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 16px 20px 0; font-family: {{ $font }}; font-size: 11px; line-height: 17px; color: #6b7280;">
                            We will never ask for your password by e-mail. &copy; {{ now()->year }} University of Education, Winneba.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
