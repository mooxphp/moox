<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email verified</title>
    <style>
        body { margin: 0; padding: 32px 16px; background: #eef2ff; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1e1b4b; }
        .card { max-width: 520px; margin: 0 auto; background: #fff; border: 1px solid #c7d2fe; border-radius: 12px; padding: 28px; }
        .kicker { margin: 0 0 8px; font-size: 12px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #4f46e5; }
        h1 { margin: 0 0 12px; font-size: 24px; }
        p { margin: 0 0 12px; color: #374151; line-height: 1.5; }
        .meta { margin: 16px 0 0; font-size: 13px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="card">
        <p class="kicker">Email verification example</p>
        <h1>Your email is verified</h1>
        @if(is_array($result))
            <p>We confirmed <strong>{{ $result['email'] ?? 'this mailbox' }}</strong>. You are not signed in — this process only proves mailbox ownership.</p>
            <p class="meta">Process: {{ $result['process_title'] ?? 'Email verification' }}</p>
        @else
            <p>No verification in this session yet. Issue a <code>verify-email</code> link and open it.</p>
        @endif
    </div>
</body>
</html>
