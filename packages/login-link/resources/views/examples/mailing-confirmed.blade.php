<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mailing confirmed</title>
    <style>
        body { margin: 0; padding: 32px 16px; background: #ecfdf5; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #064e3b; }
        .card { max-width: 520px; margin: 0 auto; background: #fff; border: 1px solid #a7f3d0; border-radius: 12px; padding: 28px; }
        h1 { margin: 0 0 12px; font-size: 24px; }
        p { margin: 0; color: #374151; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Thanks — mailing confirmed</h1>
        @if(is_array($result))
            <p>{{ $result['email'] ?? '' }}</p>
        @endif
    </div>
</body>
</html>
