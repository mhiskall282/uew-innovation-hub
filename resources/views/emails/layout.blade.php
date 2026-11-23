<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'UEW Innovation Hub Notification' }}</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.6; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f1f5f9; padding: 30px 0; }
        .content { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { background-color: #0a1b4d; background: linear-gradient(135deg, #0a1b4d 0%, #102a72 100%); padding: 30px 40px; text-align: center; border-bottom: 4px solid #f59e0b; }
        .header h1 { color: #ffffff; margin: 10px 0 0 0; font-size: 18px; font-weight: 800; letter-spacing: -0.02em; }
        .header p { color: #f59e0b; margin: 4px 0 0 0; font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700; font-family: monospace; }
        .body { padding: 36px 40px; }
        .button { display: inline-block; padding: 12px 28px; background-color: #102a72; background: linear-gradient(90deg, #102a72 0%, #3157d5 100%); color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; text-align: center; margin: 20px 0; box-shadow: 0 2px 4px rgba(16, 42, 114, 0.2); }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; font-family: monospace; text-transform: uppercase; }
        .badge-success { background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .badge-info { background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-gold { background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .badge-danger { background-color: #fff1f2; color: #be123c; border: 1px solid #fecdd3; }
        .card-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; margin: 20px 0; }
        .footer { padding: 24px 40px; background-color: #071336; color: #94a3b8; font-size: 11px; text-align: center; border-top: 1px solid #1e293b; }
        .footer a { color: #f59e0b; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="content">
            <!-- Header -->
            <div class="header">
                <h1>UNIVERSITY OF EDUCATION, WINNEBA</h1>
                <p>Directorate of Research & Innovation Hub</p>
            </div>

            <!-- Body -->
            <div class="body">
                {{ $slot }}
            </div>

            <!-- Footer -->
            <div class="footer">
                <p style="margin: 0 0 6px 0;"><strong>UEW Innovation Hub</strong> • P.O. Box 25, Winneba, Central Region, Ghana</p>
                <p style="margin: 0;">Protected by Institutional Security & Data Protection Act, 2012 (Act 843).</p>
            </div>
        </div>
    </div>
</body>
</html>
