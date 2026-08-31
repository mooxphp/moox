@if(filled($mailContent ?? null))
    {!! $mailContent !!}
@else
    <mj-text font-size="28px" font-weight="600" line-height="36px" padding="0 0 20px">
        {{ $headline ?? __('login-link::translations.mail_title') }}
    </mj-text>

    <mj-text padding="0 0 16px">
        {{ __('login-link::translations.mail_greeting') }}
        @php
            $lastName = trim((string) data_get($user ?? null, 'last_name', ''));
            $name = $lastName !== '' ? $lastName : trim((string) data_get($user ?? null, 'name', ''));
        @endphp
        @if($name !== '')
            {{ $name }},
        @endif
    </mj-text>

    <mj-text padding="0 0 24px">{{ __('login-link::translations.mail_intro') }}</mj-text>

    <mj-button href="{{ $magicLink ?? $url }}" css-class="mail-button" padding="0 0 24px">
        {{ $cta ?? __('login-link::translations.mail_cta') }}
    </mj-button>

    @isset($expiresMinutes)
        <mj-text font-size="12px" color="#6b7280" padding="0 0 8px">
            {{ __('login-link::translations.mail_expires', ['minutes' => $expiresMinutes]) }}
        </mj-text>
    @endisset

    <mj-text font-size="12px" color="#6b7280">
        {{ __('login-link::translations.mail_security_hint') }}
    </mj-text>
@endif
