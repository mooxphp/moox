<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Verify your email</title>
</head>
<body style="margin:0; padding:0; background:#eef2ff; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2ff; padding:32px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:600px;">
                <tr>
                    <td align="center" style="padding:0 0 20px;">
                        @if(filled($logoUrl))
                            <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" style="height:44px; width:auto; display:block;">
                        @else
                            <div style="font-size:16px; font-weight:700; color:#312e81;">
                                {{ config('app.name') }}
                            </div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="background:#ffffff; border-radius:12px; padding:28px; border:1px solid #c7d2fe;">
                        <p style="margin:0 0 8px; font-size:12px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:#4f46e5;">
                            Email verification
                        </p>
                        <h1 style="margin:0 0 12px; font-size:22px; line-height:30px; color:#1e1b4b;">
                            Confirm this is your email
                        </h1>
                        <p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">
                            We need to verify <strong>{{ $loginLink->email }}</strong> before we can use it.
                            Click the button below to confirm you own this mailbox. This does not sign you in.
                        </p>
                        <div style="margin:0 0 16px;">
                            <a href="{{ $url }}" style="display:inline-block; background:#4f46e5; color:#ffffff; text-decoration:none; padding:12px 18px; border-radius:10px; font-size:14px; font-weight:600;">
                                Verify email address
                            </a>
                        </div>
                        <p style="margin:0; font-size:12px; line-height:18px; color:#6b7280;">
                            This link expires in {{ $expiresMinutes }} minutes. If you did not request this, you can ignore the email.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
