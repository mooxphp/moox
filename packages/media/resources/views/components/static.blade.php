@props([
    'path',
    'sizes' => '100vw',
    'alt' => '',
    'profile' => 'web',
    'loading' => 'lazy',
    'width' => null,
    'height' => null,
    'fetchpriority' => null,
    /** Optional disk fallback base for legacy <x-responsive-image> */
    'fallbackBase' => null,
])

@php
    $media = \Moox\Media\Support\StaticMedia::find((string) $path);
@endphp

@if ($media)
    <x-media::picture
        :media="$media"
        :sizes="$sizes"
        :alt="$alt"
        :profile="$profile"
        :loading="$loading"
        @if ($width) width="{{ $width }}" @endif
        @if ($height) height="{{ $height }}" @endif
        @if ($fetchpriority) fetchpriority="{{ $fetchpriority }}" @endif
        {{ $attributes }}
    />
@elseif ($fallbackBase)
    <x-responsive-image
        :base="$fallbackBase"
        :alt="$alt"
        :sizes="$sizes"
        :loading="$loading"
        :width="$width"
        :height="$height"
        :fetchpriority="$fetchpriority"
        {{ $attributes }}
    />
@endif
