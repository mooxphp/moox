@props(['active'])

@php
$classes = ($active ?? false)
    ? 'text-pink-500 px-2 py-1'
    : 'text-gray-200 px-2 py-1 hover:text-pink-500 transition-colors';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
