@if(filled($footer))
    {!! $footer !!}
@else
    <mj-divider border-color="#dddddd" />
    <mj-text font-size="12px" color="#777777" padding-top="16px">
        © {{ date('Y') }} {{ $brandName ?? config('app.name') }}
    </mj-text>
@endif
