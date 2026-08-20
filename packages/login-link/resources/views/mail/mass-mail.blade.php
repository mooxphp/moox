<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $payload['campaign'] ?? 'Mailing confirmation' }}</title>
</head>
<body style="margin:0; padding:0; background:#ecfdf5; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ecfdf5; padding:32px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:600px;">
                <tr>
                    <td align="center" style="padding:0 0 20px;">
                        @if(filled($logoUrl))
                            <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" style="height:44px; width:auto; display:block;">
                        @else
                            <div style="font-size:16px; font-weight:700; color:#065f46;">
                                {{ config('app.name') }}
                            </div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="background:#ffffff; border-radius:12px; padding:28px; border:1px solid #a7f3d0;">
                        <p style="margin:0 0 8px; font-size:12px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:#047857;">
                            Mass mail verification
                        </p>
                        <h1 style="margin:0 0 12px; font-size:22px; line-height:30px; color:#064e3b;">
                            {{ $payload['campaign'] ?? 'Did you receive this mailing?' }}
                        </h1>
                        <p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">
                            This is an example campaign confirmation. Confirming records that
                            <strong>{{ $loginLink->email }}</strong> received this mailing.
                            Other recipients keep their own links — this process does not invalidate prior sends.
                        </p>
                        @if(filled($payload['mailing_id'] ?? null))
                            <p style="margin:0 0 16px; font-size:12px; line-height:18px; color:#6b7280;">
                                Mailing ID: {{ $payload['mailing_id'] }}
                            </p>
                        @endif
                        <div style="margin:0 0 16px;">
                            <a href="{{ $url }}" style="display:inline-block; background:#059669; color:#ffffff; text-decoration:none; padding:12px 18px; border-radius:10px; font-size:14px; font-weight:600;">
                                Yes, I received this
                            </a>
                        </div>
                        <p style="margin:0; font-size:12px; line-height:18px; color:#6b7280;">
                            This link expires in {{ $expiresMinutes }} minutes.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
