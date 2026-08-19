<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login-link example emails</title>
    <style>
        body { margin: 0; padding: 32px 16px; background: #f6f7f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #111827; }
        .wrap { max-width: 720px; margin: 0 auto; }
        h1 { margin: 0 0 8px; font-size: 24px; }
        .lead { margin: 0 0 24px; color: #6b7280; line-height: 1.5; }
        .grid { display: grid; gap: 16px; }
        a.card { display: block; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; text-decoration: none; color: inherit; }
        a.card:hover { border-color: #111827; }
        .kicker { margin: 0 0 6px; font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; }
        h2 { margin: 0 0 8px; font-size: 18px; }
        p { margin: 0; color: #4b5563; line-height: 1.5; font-size: 14px; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Example emails</h1>
        <p class="lead">These are the three packaged mail templates. Open one to preview the HTML that would be sent.</p>
        <div class="grid">
            <a class="card" href="{{ route('login-link.examples.mail', 'login') }}">
                <p class="kicker">Auth · login</p>
                <h2>Passwordless login</h2>
                <p>Signs the recipient into the Filament panel. Issued from the login form, not the example command.</p>
            </a>
            <a class="card" href="{{ route('login-link.examples.mail', 'verify-email') }}">
                <p class="kicker">Public · verify-email</p>
                <h2>Email verification</h2>
                <p>Confirms mailbox ownership. Does not sign anyone in.</p>
            </a>
            <a class="card" href="{{ route('login-link.examples.mail', 'mass-mail') }}">
                <p class="kicker">Public · mass-mail</p>
                <h2>Mass mail verification</h2>
                <p>Campaign confirmation. Other recipients keep their own links.</p>
            </a>
        </div>
    </div>
</body>
</html>
