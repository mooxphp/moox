<button {{ $attributes->merge(['class' => 'bg-primary-100 text-lg ']) }}
    name="{{ $name }}"
    type="{{ $type }}"
    id="{{ $id }}"
    @if($value)value="{{ $value }}"@endif
    {{ $attributes }}
>
{{$slot ?? null}}
</button>
