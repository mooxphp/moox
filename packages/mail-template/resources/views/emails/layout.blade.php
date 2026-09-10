<mjml>
    <mj-head>
        <mj-title>{{ $headline ?? $brandName ?? '' }}</mj-title>
        <mj-preview>{{ $headline ?? '' }}</mj-preview>
        <mj-attributes>
            <mj-text color="{{ $textColor ?? '#000000' }}" />
            <mj-button background-color="{{ $buttonColor ?? '#005CA3' }}" color="#ffffff" />
        </mj-attributes>
    </mj-head>

    <mj-body background-color="{{ $backgroundColor ?? '#ECF2F6' }}">
        @if (filled($logoUrl ?? null))
            <mj-section>
                <mj-column>
                    <mj-image src="{{ $logoUrl }}" alt="{{ $brandName ?? '' }}" />
                </mj-column>
            </mj-section>
        @endif

        <mj-section>
            <mj-column>
                {!! $mailContent ?? '' !!}
            </mj-column>
        </mj-section>

        @if (filled($footer ?? null))
            <mj-section>
                <mj-column>
                    {!! $footer !!}
                </mj-column>
            </mj-section>
        @endif
    </mj-body>
</mjml>
