@php
    // Styles inlines a l'envoi (compatibilite Gmail, Outlook...) : voir App\Services\EmailService.
    $contact = config('home.contact');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="supported-color-schemes" content="dark">
    <title>{{ $title }}</title>
    <style>
        body { margin: 0; padding: 0; background: #0a0a0a; color: #fafafa; font-family: Inter, -apple-system, 'Segoe UI', Helvetica, Arial, sans-serif; }
        .wrapper { width: 100%; background: #0a0a0a; padding: 32px 12px; }
        .container { width: 100%; max-width: 600px; margin: 0 auto; }
        .brand { padding: 0 8px 20px; color: #fafafa; font-size: 14px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; }
        .card { background: #18181b; border: 1px solid #27272a; border-radius: 16px; padding: 32px; }
        .content { color: #d4d4d8; font-size: 15px; line-height: 1.65; }
        .content h1 { margin: 0 0 16px; color: #fafafa; font-size: 24px; line-height: 1.3; font-weight: 600; }
        .content h2 { margin: 28px 0 10px; color: #fafafa; font-size: 17px; font-weight: 600; }
        .content p { margin: 0 0 16px; }
        .content ul { margin: 0 0 20px; padding: 16px 16px 16px 34px; background: #0a0a0a; border: 1px solid #27272a; border-radius: 12px; }
        .content li { margin: 4px 0; }
        .content strong { color: #fafafa; }
        .content a { color: #0a0a0a; background: #e4e4e7; display: inline-block; padding: 12px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; }
        .footer { padding: 20px 8px 0; color: #71717a; font-size: 12px; line-height: 1.6; text-align: center; }
        .footer a { color: #a1a1aa; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="brand">CLASS’AFFAIRE</div>
            <div class="card">
                <div class="content">{!! $content !!}</div>
            </div>
            <div class="footer">
                {{ config('app.name') }} · <a href="tel:{{ $contact['phone_href'] }}">{{ $contact['phone'] }}</a> · <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a><br>
                {{ $contact['address'] }}
            </div>
        </div>
    </div>
</body>
</html>
