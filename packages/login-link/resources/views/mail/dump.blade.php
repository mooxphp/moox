<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>login-link dump mail</title>
</head>
<body style="margin:0;padding:24px;background:#0f172a;color:#e2e8f0;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:13px;line-height:1.5;">
    <h1 style="margin:0 0 16px;font-size:18px;color:#38bdf8;">login-link · dump mail</h1>
    <p style="margin:0 0 16px;color:#94a3b8;">This is a demo template. Click the link, then you’ll see a dump of who/what was redeemed.</p>

    <div style="margin:0 0 20px;">
        <a href="{{ $url }}" style="display:inline-block;background:#38bdf8;color:#0f172a;text-decoration:none;padding:10px 14px;border-radius:8px;font-weight:700;">
            Open signed link
        </a>
    </div>

    <pre style="margin:0;padding:16px;background:#020617;border:1px solid #1e293b;border-radius:8px;overflow:auto;white-space:pre-wrap;">@php
$subjectType = $subject !== null ? $subject::class : null;
$subjectId = $subject !== null ? $subject->getKey() : null;
$subjectAttributes = $subject !== null ? $subject->toArray() : null;

$dump = [
    'process' => [
        'slug' => $process?->slug,
        'title' => $process?->title,
        'context' => $process?->context,
        'handler_key' => $process?->handler_key,
        'template_key' => $process?->template_key,
        'invalidate_prior' => $process?->invalidate_prior,
    ],
    'login_link' => [
        'id' => $loginLink->getKey(),
        'email' => $loginLink->email,
        'panel_id' => $loginLink->panel_id,
        'payload' => $payload ?? [],
        'expires_at' => (string) $loginLink->expires_at,
    ],
    'subject' => [
        'type' => $subjectType,
        'id' => $subjectId,
        'attributes' => $subjectAttributes,
    ],
    'url' => $url,
    'expires_minutes' => $expiresMinutes,
];
@endphp
{{ json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
</body>
</html>
