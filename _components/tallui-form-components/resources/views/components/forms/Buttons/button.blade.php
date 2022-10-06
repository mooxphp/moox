<a
    name="{{ $name }}"
    type="{{ $type }}"
    id="{{ $id }}"
    @if($value)value="{{ $value }}"
    @endif
    {{ $attributes }}
>
{{$slot ?? null}}
</a>
