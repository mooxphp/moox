<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>login-link dump redeem</title>
    <style>
        body { margin: 0; padding: 24px; background: #0f172a; color: #e2e8f0; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 13px; line-height: 1.5; }
        h1 { margin: 0 0 8px; font-size: 20px; color: #4ade80; }
        .meta { color: #94a3b8; margin: 0 0 20px; }
        pre { margin: 0; padding: 16px; background: #020617; border: 1px solid #1e293b; border-radius: 8px; overflow: auto; white-space: pre-wrap; }
        .ok { color: #4ade80; }
    </style>
</head>
<body>
    <h1>login-link · dump redeem</h1>
    <p class="meta">
        Redeemed without Auth.
        <span class="ok">auth.check = {{ $authCheck ? 'true' : 'false' }}</span>
    </p>
    <pre>{{ json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
</body>
</html>
