@php
    $firstName = trim((string) data_get($user, 'first_name', ''));
    $lastName = trim((string) data_get($user, 'last_name', ''));
    $fullName = trim($firstName.' '.$lastName);
    $name = $fullName !== '' ? $fullName : trim((string) data_get($user, 'name', ''));
@endphp

<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ __('login-link::translations.mail_title') }}</title>
</head>
<body style="margin:0; padding:0; background:#f6f7f9; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7f9; padding:32px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:600px;">
                <tr>
                    <td align="center" style="padding:0 0 20px;">
                        @if(filled($logoUrl))
                            <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" style="height:44px; width:auto; display:block;">
                        @else
                            <div style="font-size:16px; font-weight:700; color:#111827;">
                                {{ config('app.name') }}
                            </div>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="background:#ffffff; border-radius:12px; padding:24px; box-shadow: 0 1px 2px rgba(16,24,40,.06);">
                        <h1 style="margin:0 0 12px; font-size:20px; line-height:28px;">
                            {{ __('login-link::translations.mail_title') }}
                        </h1>

                        <p style="margin:0 0 12px; font-size:14px; line-height:22px; color:#374151;">
                            {{ __('login-link::translations.mail_greeting') }} {{ $name ?: ' ' }},
                        </p>

                        <p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">
                            {{ __('login-link::translations.mail_intro') }}
                        </p>

                        <div style="margin:0 0 14px;">
                            <a href="{{ $url }}" style="display:inline-block; background:#111827; color:#ffffff; text-decoration:none; padding:10px 16px; border-radius:10px; font-size:14px; font-weight:600;">
                                {{ __('login-link::translations.mail_cta') }}
                            </a>
                        </div>

                        <p style="margin:0 0 14px; font-size:12px; line-height:18px; color:#6b7280;">
                            {{ __('login-link::translations.mail_expires', ['minutes' => $expiresMinutes]) }}
                        </p>

                        <p style="margin:0; font-size:12px; line-height:18px; color:#6b7280;">
                            {{ __('login-link::translations.mail_security_hint') }}
                        </p>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:18px 8px 0; font-size:12px; line-height:18px; color:#9ca3af;">
                        © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

