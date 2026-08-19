<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mailing confirmed</title>
    <style>
        body { margin: 0; padding: 32px 16px; background: #ecfdf5; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #064e3b; }
        .card { max-width: 520px; margin: 0 auto; background: #fff; border: 1px solid #a7f3d0; border-radius: 12px; padding: 28px; }
        .kicker { margin: 0 0 8px; font-size: 12px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #047857; }
        h1 { margin: 0 0 12px; font-size: 24px; }
        p { margin: 0 0 12px; color: #374151; line-height: 1.5; }
        .meta { margin: 16px 0 0; font-size: 13px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="card">
        <p class="kicker">Mass mail verification example</p>
        <h1>Thanks — mailing confirmed</h1>
        @if(is_array($result))
            <p>We recorded that <strong>{{ $result['email'] ?? 'this recipient' }}</strong> received this mailing. Other recipients keep their own links.</p>
            @if(filled($result['payload']['campaign'] ?? null))
                <p class="meta">Campaign: {{ $result['payload']['campaign'] }}</p>
            @endif
        @else
            <p>No mailing confirmation in this session yet. Issue a <code>mass-mail</code> link and open it.</p>
        @endif
    </div>
</body>
</html>
