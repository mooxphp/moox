@props([
    'media',
    'sizes' => null,
    'alt' => null,
    'profile' => 'web',
])

@php
    /** @var \Moox\Media\Models\Media $media */
    $html = app(\Moox\Media\Support\MediaPictureBuilder::class)->toHtml(
        $media,
        $sizes,
        $alt,
        $attributes->except(['media', 'sizes', 'alt', 'profile'])->getAttributes(),
        (string) $profile,
    );
@endphp

{!! $html !!}
