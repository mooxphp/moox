<mjml>
    <mj-head>
        <mj-attributes>
            <mj-all font-family="Arial, sans-serif" />
            <mj-text color="#222222" font-size="16px" line-height="24px" />
            <mj-button background-color="#2563eb" color="#ffffff" border-radius="6px" />
        </mj-attributes>
        <mj-title>{{ $headline ?? __('login-link::translations.mail_title') }}</mj-title>
    </mj-head>

    <mj-body background-color="#f5f5f5">
        <mj-section background-color="#ffffff" padding="24px 24px 8px">
            <mj-column>
                @if(filled($logoUrl ?? null))
                    <mj-image src="{{ $logoUrl }}" alt="{{ $brandName ?? config('app.name') }}" width="180px" />
                @else
                    <mj-text font-size="20px" font-weight="bold">
                        {{ $brandName ?? config('app.name') }}
                    </mj-text>
                @endif
            </mj-column>
        </mj-section>

        <mj-section background-color="#ffffff" padding="8px 24px">
            <mj-column>
                @include('login-link::mail.content')
            </mj-column>
        </mj-section>

        <mj-section background-color="#ffffff" padding="8px 24px 24px">
            <mj-column>
                @include('login-link::mail.partials.footer', [
                    'footer' => $footer ?? null,
                ])
            </mj-column>
        </mj-section>
    </mj-body>
</mjml>
