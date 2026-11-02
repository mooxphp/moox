<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Moox\Media\Models\Media;

final class MediaPictureBuilder
{
    /**
     * @return array{
     *     alt: string,
     *     sizes: string|null,
     *     sources: list<array{type: string, srcset: string}>,
     *     img: array{src: string|null, srcset: string|null, loading: string, decoding: string}
     * }
     */
    public function toArray(Media $media, ?string $sizes = null, ?string $alt = null): array
    {
        $widths = MediaDeliveryConversions::widths();
        $sources = [];

        foreach (['avif', 'webp'] as $format) {
            $srcset = $this->srcsetForFormat($media, $format, $widths);
            if ($srcset === null) {
                continue;
            }

            $sources[] = [
                'type' => 'image/'.$format,
                'srcset' => $srcset,
            ];
        }

        $jpgSrcset = $this->srcsetForFormat($media, 'jpg', $widths);
        $jpgSrc = $this->preferredJpgSrc($media, $widths);

        return [
            'alt' => $alt ?? $this->resolveAlt($media),
            'sizes' => $sizes,
            'sources' => $sources,
            'img' => [
                'src' => $jpgSrc,
                'srcset' => $jpgSrcset,
                'loading' => 'lazy',
                'decoding' => 'async',
            ],
        ];
    }

    public function toHtml(Media $media, ?string $sizes = null, ?string $alt = null, array $attributes = []): string
    {
        $data = $this->toArray($media, $sizes, $alt);

        if ($data['img']['src'] === null && $data['sources'] === []) {
            return '';
        }

        $html = ['<picture>'];

        foreach ($data['sources'] as $source) {
            $html[] = sprintf(
                '  <source type="%s" srcset="%s"%s>',
                e($source['type']),
                e($source['srcset']),
                $sizes !== null && $sizes !== '' ? ' sizes="'.e($sizes).'"' : '',
            );
        }

        $imgAttrs = array_merge([
            'src' => $data['img']['src'] ?? $media->getUrl(),
            'alt' => $data['alt'],
            'loading' => $data['img']['loading'],
            'decoding' => $data['img']['decoding'],
        ], $attributes);

        if ($data['img']['srcset'] !== null) {
            $imgAttrs['srcset'] = $data['img']['srcset'];
        }

        if ($sizes !== null && $sizes !== '') {
            $imgAttrs['sizes'] = $sizes;
        }

        $attrString = collect($imgAttrs)
            ->filter(static fn (mixed $value): bool => $value !== null && $value !== '')
            ->map(static fn (mixed $value, string $key): string => sprintf('%s="%s"', $key, e((string) $value)))
            ->implode(' ');

        $html[] = '  <img '.$attrString.'>';
        $html[] = '</picture>';

        return implode("\n", $html);
    }

    /**
     * @param  list<int>  $widths
     */
    private function srcsetForFormat(Media $media, string $format, array $widths): ?string
    {
        $parts = [];

        foreach ($widths as $width) {
            $name = MediaDeliveryConversions::conversionName($width, $format);
            $url = $this->safeConversionUrl($media, $name);
            if ($url === null) {
                continue;
            }

            $parts[] = $url.' '.$width.'w';
        }

        if ($parts === []) {
            return null;
        }

        return implode(', ', $parts);
    }

    /**
     * @param  list<int>  $widths
     */
    private function preferredJpgSrc(Media $media, array $widths): ?string
    {
        $preferred = $widths[1] ?? $widths[0] ?? null;

        if ($preferred !== null) {
            $url = $this->safeConversionUrl(
                $media,
                MediaDeliveryConversions::conversionName($preferred, 'jpg'),
            );
            if ($url !== null) {
                return $url;
            }
        }

        foreach (array_reverse($widths) as $width) {
            $url = $this->safeConversionUrl(
                $media,
                MediaDeliveryConversions::conversionName($width, 'jpg'),
            );
            if ($url !== null) {
                return $url;
            }
        }

        try {
            $url = $media->getUrl();

            return $url !== '' ? $url : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function safeConversionUrl(Media $media, string $conversion): ?string
    {
        try {
            if (! $media->hasGeneratedConversion($conversion)) {
                return null;
            }

            $url = $media->getUrl($conversion);

            return $url !== '' ? $url : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveAlt(Media $media): string
    {
        $resolver = app(MediaLocaleResolver::class);
        $metadata = $resolver->mediaMetadata($media->loadMissing('translations'));

        return (string) ($metadata['alt'] ?? $metadata['title'] ?? $media->file_name ?? '');
    }
}
