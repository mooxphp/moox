@props([
    'media',
    'conversion' => '',
    'profile' => null,
    'sizes' => null,
    'alt' => null,
    'lazy' => false,
])

@php
    /** @var \Moox\Media\Models\Media $media */
    $htmlable = $media->img(is_string($conversion) ? $conversion : '');

    if (is_string($profile) && $profile !== '') {
        $htmlable->profile($profile);
    }

    if (is_string($sizes) && $sizes !== '') {
        $htmlable->sizes($sizes);
    }

    if (is_string($alt) && $alt !== '') {
        $htmlable->alt($alt);
    }

    if ($lazy) {
        $htmlable->lazy();
    }

    $extra = $attributes->except(['media', 'conversion', 'profile', 'sizes', 'alt', 'lazy'])->getAttributes();
    if ($extra !== []) {
        $htmlable->attributes($extra);
    }
@endphp

{!! $htmlable !!}
