<label
primary-color
for="{{ $for }}" {{ $attributes }}>
    @if ($slot!= "")
    {{$slot}}
    @else
    {{ $fallback }}
    @endif

</label>
