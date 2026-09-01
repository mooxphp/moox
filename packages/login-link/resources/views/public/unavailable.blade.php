@php
    $title = match ($reason ?? 'invalid') {
        'used' => __('login-link::translations.public_used_title'),
        'expired' => __('login-link::translations.public_expired_title'),
        default => __('login-link::translations.public_invalid_title'),
    };
    $body = match ($reason ?? 'invalid') {
        'used' => __('login-link::translations.public_used_body'),
        'expired' => __('login-link::translations.public_expired_body'),
        default => __('login-link::translations.public_invalid_body'),
    };
    $hasSupport = filled($supportEmail ?? null) || filled($supportPhone ?? null);
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body { margin: 0; padding: 32px 16px; background: #f3f6f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1f2937; }
        .card { max-width: 520px; margin: 0 auto; background: #fff; border: 1px solid #dbe3ea; border-radius: 12px; padding: 28px; }
        h1 { margin: 0 0 12px; font-size: 24px; }
        p { margin: 0 0 12px; color: #4b5563; line-height: 1.5; }
        .support { margin: 20px 0 0; padding: 16px; background: #f8fafc; border-radius: 8px; }
        .support h2 { margin: 0 0 8px; font-size: 16px; }
        .support p { margin: 0; }
        a { color: #4f46e5; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ $title }}</h1>
        <p>{{ $body }}</p>
        @if ($hasSupport)
            <div class="support">
                <h2>{{ __('login-link::translations.public_support_heading') }}</h2>
                <p>
                    @if (filled($supportName ?? null))
                        {{ $supportName }}<br>
                    @endif
                    @if (filled($supportEmail ?? null))
                        <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
                    @endif
                    @if (filled($supportEmail ?? null) && filled($supportPhone ?? null))
                        <br>
                    @endif
                    @if (filled($supportPhone ?? null))
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $supportPhone) }}">{{ $supportPhone }}</a>
                    @endif
                </p>
            </div>
        @endif
    </div>
</body>
</html>
