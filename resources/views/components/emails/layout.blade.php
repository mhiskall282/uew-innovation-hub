@props(['subject' => 'UEW Innovation Hub'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f7fb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.6; }
        .wrapper { width: 100%; background-color: #f4f7fb; padding: 32px 0; }
        .content { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
        .header { background-color: #0a1128; padding: 28px 40px; border-bottom: 4px solid #f59e0b; }
        .header h1 { color: #ffffff; margin: 0; font-size: 18px; font-weight: 800; }
        .header p { color: #fbbf24; margin: 4px 0 0 0; font-size: 12px; font-weight: 600; }
        .body { padding: 32px 40px; font-size: 14px; }
        .button { display: inline-block; padding: 12px 24px; background-color: #102a72; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 14px; margin: 16px 0; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .badge-green { background-color: #ecfdf5; color: #047857; }
        .badge-blue { background-color: #eff6ff; color: #1d4ed8; }
        .badge-gold { background-color: #fffbeb; color: #b45309; }
        .badge-red { background-color: #fef2f2; color: #b91c1c; }
        .badge-slate { background-color: #f1f5f9; color: #334155; }
        .card-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; margin: 20px 0; }
        .muted { color: #64748b; font-size: 12px; }
        .prose p { margin: 0 0 14px 0; }
        .prose h1 { margin: 18px 0; padding: 16px; background-color: #f4f7fb; border: 1px dashed #c7d2fe; border-radius: 10px; text-align: center; font-family: 'Courier New', monospace; font-size: 30px; letter-spacing: 8px; color: #102a72; }
        .prose h2 { margin: 20px 0 8px 0; font-size: 18px; color: #102a72; }
        .prose ul, .prose ol { margin: 0 0 14px 0; padding-left: 20px; }
        .prose a { color: #3157d5; }
        .prose blockquote { margin: 0 0 14px 0; padding: 8px 14px; border-left: 4px solid #f59e0b; background: #fffbeb; }
        .footer { padding: 20px 40px; background-color: #f8fafc; color: #64748b; font-size: 12px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="content">
            <div class="header">
                <h1>UEW Innovation Hub</h1>
                <p>University of Education, Winneba</p>
            </div>
            <div class="body">
                {{ $slot }}
            </div>
            <div class="footer">
                @php $mailSettings = app(\App\Services\SettingsService::class); @endphp
                <p style="margin: 0 0 4px 0;">UEW Innovation Hub · {{ $mailSettings->get('address') }}</p>
                <p style="margin: 0;">Questions? Email <a href="mailto:{{ $mailSettings->get('contact_email') }}" style="color: #3157d5;">{{ $mailSettings->get('contact_email') }}</a>.</p>
            </div>
        </div>
    </div>
</body>
</html>
