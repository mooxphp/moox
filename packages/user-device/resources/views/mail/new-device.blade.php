@php
    /** @var mixed $notifiable */
    $firstName = trim((string) data_get($notifiable, 'first_name', ''));
    $lastName = trim((string) data_get($notifiable, 'last_name', ''));
    $fullName = trim($firstName.' '.$lastName);
    $name = $fullName !== '' ? $fullName : trim((string) data_get($notifiable, 'name', ''));
    $systemParts = collect([$devicePlatform ?? null, $deviceBrowser ?? null, $deviceOs ?? null])->filter()->values();
    $locationParts = collect([$deviceCity ?? null, $deviceCountry ?? null])->filter()->values();
@endphp

<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ __('user-device::translations.mail_title_new_device') }}</title>
</head>
<body style="margin:0; padding:0; background:#f6f7f9; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7f9; padding:32px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:600px;">
                <tr>
                    <td align="center" style="padding:0 0 20px;">
                        <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" style="height:44px; width:auto; display:block;">
                    </td>
                </tr>

                <tr>
                    <td style="background:#ffffff; border-radius:12px; padding:24px; box-shadow: 0 1px 2px rgba(16,24,40,.06);">
                        <h1 style="margin:0 0 12px; font-size:20px; line-height:28px;">
                            {{ __('user-device::translations.mail_title_new_device') }}
                        </h1>

                        <p style="margin:0 0 12px; font-size:14px; line-height:22px; color:#374151;">
                            {{ __('user-device::translations.mail_greeting') }} {{ $name ?: ' ' }},
                        </p>

                        <p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">
                            {{ __('user-device::translations.mail_intro') }}
                        </p>

                        @if(filled($deviceTitle))
                            <p style="margin:0 0 8px; font-size:14px; line-height:22px; color:#111827;">
                                <strong>{{ __('user-device::translations.mail_label_device') }}:</strong> {{ $deviceTitle }}
                            </p>
                        @endif

                        @if($systemParts->isNotEmpty())
                            <p style="margin:0 0 8px; font-size:14px; line-height:22px; color:#111827;">
                                <strong>{{ __('user-device::translations.mail_label_system') }}:</strong> {{ $systemParts->join(' · ') }}
                            </p>
                        @endif

                        @if(filled($deviceIp))
                            <p style="margin:0 0 8px; font-size:14px; line-height:22px; color:#111827;">
                                <strong>{{ __('user-device::translations.mail_label_ip') }}:</strong> {{ $deviceIp }}
                            </p>
                        @endif

                        @if($locationParts->isNotEmpty())
                            <p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#111827;">
                                <strong>{{ __('user-device::translations.mail_label_location') }}:</strong> {{ $locationParts->join(', ') }}
                            </p>
                        @endif

                        <p style="margin:0 0 12px; font-size:14px; line-height:22px; color:#374151;">
                            {{ __('user-device::translations.mail_if_it_was_you') }}
                        </p>

                        <p style="margin:0 0 8px; font-size:14px; line-height:22px; color:#374151;">
                            {{ __('user-device::translations.mail_if_it_was_not_you') }}
                        </p>
                        <ul style="margin:0 0 18px 18px; padding:0; font-size:14px; line-height:22px; color:#374151;">
                            <li>{{ __('user-device::translations.mail_step_review_devices') }}</li>
                            <li>{{ __('user-device::translations.mail_step_change_password') }}</li>
                            <li>{{ __('user-device::translations.mail_step_check_mfa') }}</li>
                        </ul>

                        <div style="margin:0 0 14px;">
                            @if(filled($trustUrl))
                                <a href="{{ $trustUrl }}" style="display:inline-block; background:#111827; color:#ffffff; text-decoration:none; padding:10px 16px; border-radius:10px; font-size:14px; font-weight:600;">
                                    {{ __('user-device::translations.mail_cta_trust_device') }}
                                </a>
                            @endif
                        </div>

                        <p style="margin:0 0 14px; font-size:12px; line-height:18px; color:#6b7280;">
                            <a href="{{ $reviewUrl }}" style="color:#111827; text-decoration:underline;">
                                {{ __('user-device::translations.mail_cta_review_devices') }}
                            </a>
                        </p>

                        <p style="margin:0; font-size:12px; line-height:18px; color:#6b7280;">
                            {{ __('user-device::translations.mail_outro_secure_account') }}
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
