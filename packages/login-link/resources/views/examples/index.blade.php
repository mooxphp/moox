<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login-link examples</title>
    <style>
        body { margin: 0; padding: 32px 16px; background: #f6f7f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #111827; }
        .wrap { max-width: 720px; margin: 0 auto; }
        h1 { margin: 0 0 24px; font-size: 24px; }
        .stack { display: grid; gap: 12px; }
        a.card { display: block; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; text-decoration: none; color: inherit; }
        a.card:hover { border-color: #111827; }
        h2 { margin: 0; font-size: 18px; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>HTML demos</h1>
        <div class="stack">
            <a class="card" href="{{ route('login-link.examples.mail') }}">
                <h2>Process link</h2>
            </a>
            <a class="card" href="{{ route('login-link.examples.unavailable', ['reason' => 'expired']) }}">
                <h2>Expired link</h2>
            </a>
            <a class="card" href="{{ route('login-link.examples.unavailable', ['reason' => 'used']) }}">
                <h2>Used link</h2>
            </a>
            <a class="card" href="{{ route('login-link.examples.unavailable', ['reason' => 'invalid']) }}">
                <h2>Invalid link</h2>
            </a>
        </div>
    </div>
</body>
</html>
