<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login-link examples</title>
    <style>
        body { margin: 0; padding: 32px 16px; background: #f6f7f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #111827; }
        .wrap { max-width: 720px; margin: 0 auto; }
        h1 { margin: 0 0 8px; font-size: 24px; }
        .lead { margin: 0 0 24px; color: #6b7280; line-height: 1.5; }
        .stack { display: grid; gap: 12px; }
        a.card { display: block; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; text-decoration: none; color: inherit; }
        a.card:hover { border-color: #111827; }
        .kicker { margin: 0 0 6px; font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; }
        h2 { margin: 0 0 8px; font-size: 18px; }
        p { margin: 0; color: #4b5563; line-height: 1.5; font-size: 14px; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>HTML demos</h1>
        <p class="lead">Packaged pages with no host theme. Mail can be branded via a mail_templates row; used/expired/invalid links always stay on this demo.</p>
        <div class="stack">
            <a class="card" href="{{ route('login-link.examples.mail') }}">
                <p class="kicker">Mail</p>
                <h2>Process link</h2>
                <p>Plain HTML: title, optional body, button, expiry. Used when no mail-template row matches.</p>
            </a>
            <a class="card" href="{{ route('login-link.examples.unavailable', ['reason' => 'expired']) }}">
                <p class="kicker">Consume</p>
                <h2>Expired link</h2>
                <p>Standalone HTML for an expired signed URL. Same page for used and invalid.</p>
            </a>
            <a class="card" href="{{ route('login-link.examples.unavailable', ['reason' => 'used']) }}">
                <p class="kicker">Consume</p>
                <h2>Used link</h2>
                <p>The link was already redeemed.</p>
            </a>
            <a class="card" href="{{ route('login-link.examples.unavailable', ['reason' => 'invalid']) }}">
                <p class="kicker">Consume</p>
                <h2>Invalid link</h2>
                <p>Missing or tampered link.</p>
            </a>
        </div>
    </div>
</body>
</html>
