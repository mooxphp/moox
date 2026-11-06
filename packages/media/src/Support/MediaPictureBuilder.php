<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Moox\Media\Models\Media;

final class MediaPictureBuilder
{
    /**
     * @return array{
     *     profile: string,
     *     mode: 'svg'|'img'|'picture',
     *     alt: string,
     *     sizes: string|null,
     *     width: int|null,
     *     height: int|null,
     *     sources: list<array{type: string, srcset: string}>,
     *     img: array{src: string|null, srcset: string|null, loading: string, decoding: string}
     * }
     */
    public function toArray(
        Media $media,
        ?string $sizes = null,
        ?string $alt = null,
        string $profile = MediaDeliveryConversions::PROFILE_WEB,
    ): array {
        $profile = $this->normalizeProfile($profile);
        $dimensions = $this->dimensions($media);
        $altText = $alt ?? $this->resolveAlt($media);

        if (MediaDeliveryConversions::isSvg($media)) {
            return $this->svgPayload($media, $altText, $profile, $dimensions);
        }

        if ($profile === MediaDeliveryConversions::PROFILE_MAIL) {
            return $this->mailPayload($media, $altText, $dimensions);
        }

        if ($profile === MediaDeliveryConversions::PROFILE_CUSTOMER_API) {
            return $this->customerApiPayload($media, $altText, $dimensions);
        }

        return $this->webPayload($media, $sizes, $altText, $dimensions);
    }

    public function toHtml(
        Media $media,
        ?string $sizes = null,
        ?string $alt = null,
        array $attributes = [],
        string $profile = MediaDeliveryConversions::PROFILE_WEB,
    ): string {
        $data = $this->toArray($media, $sizes, $alt, $profile);

        if ($data['img']['src'] === null && $data['sources'] === []) {
            return '';
        }

        if ($data['mode'] !== 'picture') {
            return $this->renderImg($data, $attributes);
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

        $html[] = '  '.$this->renderImg($data, $attributes, includePictureSizes: $sizes);
        $html[] = '</picture>';

        return implode("\n", $html);
    }

    public function urlForProfile(Media $media, string $profile = MediaDeliveryConversions::PROFILE_WEB): ?string
    {
        $data = $this->toArray($media, profile: $profile);

        return $data['img']['src'];
    }

    /**
     * @param  array{width: int|null, height: int|null}  $dimensions
     * @return array<string, mixed>
     */
    private function svgPayload(Media $media, string $alt, string $profile, array $dimensions): array
    {
        $src = match ($profile) {
            MediaDeliveryConversions::PROFILE_MAIL => $this->safeConversionUrl($media, MediaDeliveryConversions::MAIL_PNG)
                ?? $this->originalUrl($media),
            MediaDeliveryConversions::PROFILE_CUSTOMER_API => $this->safeConversionUrl($media, MediaDeliveryConversions::CUSTOMER_API)
                ?? $this->safeConversionUrl($media, MediaDeliveryConversions::MAIL_PNG)
                ?? $this->originalUrl($media),
            default => $this->originalUrl($media),
        };

        return [
            'profile' => $profile,
            'mode' => 'svg',
            'alt' => $alt,
            'sizes' => null,
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'sources' => [],
            'img' => [
                'src' => $src,
                'srcset' => null,
                'loading' => 'lazy',
                'decoding' => 'async',
            ],
        ];
    }

    /**
     * @param  array{width: int|null, height: int|null}  $dimensions
     * @return array<string, mixed>
     */
    private function mailPayload(Media $media, string $alt, array $dimensions): array
    {
        $src = $this->safeConversionUrl($media, MediaDeliveryConversions::MAIL_RASTER)
            ?? $this->originalUrl($media);

        return [
            'profile' => MediaDeliveryConversions::PROFILE_MAIL,
            'mode' => 'img',
            'alt' => $alt,
            'sizes' => null,
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'sources' => [],
            'img' => [
                'src' => $src,
                'srcset' => null,
                'loading' => 'lazy',
                'decoding' => 'async',
            ],
        ];
    }

    /**
     * @param  array{width: int|null, height: int|null}  $dimensions
     * @return array<string, mixed>
     */
    private function customerApiPayload(Media $media, string $alt, array $dimensions): array
    {
        $src = $this->safeConversionUrl($media, MediaDeliveryConversions::CUSTOMER_API)
            ?? $this->safeConversionUrl($media, MediaDeliveryConversions::MAIL_RASTER)
            ?? $this->originalUrl($media);

        return [
            'profile' => MediaDeliveryConversions::PROFILE_CUSTOMER_API,
            'mode' => 'img',
            'alt' => $alt,
            'sizes' => null,
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'sources' => [],
            'img' => [
                'src' => $src,
                'srcset' => null,
                'loading' => 'lazy',
                'decoding' => 'async',
            ],
        ];
    }

    /**
     * @param  array{width: int|null, height: int|null}  $dimensions
     * @return array<string, mixed>
     */
    private function webPayload(Media $media, ?string $sizes, string $alt, array $dimensions): array
    {
        $widths = MediaDeliveryConversions::applicableResponsiveWidths($media);
        $formats = array_values(array_filter(
            MediaDeliveryConversions::webFormats(),
            static fn (string $format): bool => ! in_array($format, ['original', 'jpg', 'jpeg', 'png'], true),
        ));

        $sources = [];
        foreach ($formats as $format) {
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
        $jpgSrc = $this->preferredFallbackSrc($media, $widths);

        $mode = ($widths === [] || ($jpgSrcset === null && $sources === []))
            ? 'img'
            : (count($widths) <= 1 && $sources === [] ? 'img' : 'picture');

        if (count($widths) <= 1) {
            $mode = $sources === [] ? 'img' : 'picture';
            if (count($widths) <= 1) {
                $jpgSrcset = null;
                foreach ($sources as $i => $source) {
                    // Keep single-URL sources without density list noise when only one width
                    if (! str_contains($source['srcset'], ',')) {
                        $sources[$i]['srcset'] = preg_replace('/\s+\d+w$/', '', $source['srcset']) ?? $source['srcset'];
                    }
                }
            }
        }

        return [
            'profile' => MediaDeliveryConversions::PROFILE_WEB,
            'mode' => $mode === 'picture' && ($sources !== [] || $jpgSrcset !== null) ? 'picture' : 'img',
            'alt' => $alt,
            'sizes' => $sizes,
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'sources' => $sources,
            'img' => [
                'src' => $jpgSrc,
                'srcset' => count($widths) > 1 ? $jpgSrcset : null,
                'loading' => 'lazy',
                'decoding' => 'async',
            ],
        ];
    }

    /**
     * @param  list<int>  $widths
     */
    private function srcsetForFormat(Media $media, string $format, array $widths): ?string
    {
        if ($widths === []) {
            $url = $this->sameSizeFormatUrl($media, $format);
            if ($url === null) {
                return null;
            }

            return $url;
        }

        $parts = [];

        foreach ($widths as $width) {
            $name = MediaDeliveryConversions::conversionName(MediaDeliveryConversions::PROFILE_WEB, $width, $format);
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

    private function sameSizeFormatUrl(Media $media, string $format): ?string
    {
        foreach (MediaDeliveryConversions::responsiveWidths() as $width) {
            $url = $this->safeConversionUrl(
                $media,
                MediaDeliveryConversions::conversionName(MediaDeliveryConversions::PROFILE_WEB, $width, $format),
            );
            if ($url !== null) {
                return $url;
            }
        }

        return null;
    }

    /**
     * @param  list<int>  $widths
     */
    private function preferredFallbackSrc(Media $media, array $widths): ?string
    {
        if ($widths !== []) {
            $preferred = $widths[intdiv(count($widths), 2)] ?? $widths[0];
            foreach (array_unique(array_merge([$preferred], array_reverse($widths))) as $width) {
                $url = $this->safeConversionUrl(
                    $media,
                    MediaDeliveryConversions::conversionName(MediaDeliveryConversions::PROFILE_WEB, $width, 'jpg'),
                );
                if ($url !== null) {
                    return $url;
                }

                $url = $this->safeConversionUrl(
                    $media,
                    MediaDeliveryConversions::conversionName(MediaDeliveryConversions::PROFILE_WEB, $width, 'png'),
                );
                if ($url !== null) {
                    return $url;
                }
            }
        }

        foreach (['medium_large', 'medium', 'large', 'thumbnail'] as $size) {
            $url = $this->safeConversionUrl($media, $size);
            if ($url !== null) {
                return $url;
            }
        }

        return $this->originalUrl($media);
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

    private function originalUrl(Media $media): ?string
    {
        try {
            $url = $media->getUrl();

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

    /**
     * @return array{width: int|null, height: int|null}
     */
    private function dimensions(Media $media): array
    {
        $dimensions = $media->getCustomProperty('dimensions');

        return [
            'width' => is_array($dimensions) && isset($dimensions['width']) ? (int) $dimensions['width'] : null,
            'height' => is_array($dimensions) && isset($dimensions['height']) ? (int) $dimensions['height'] : null,
        ];
    }

    private function normalizeProfile(string $profile): string
    {
        return match ($profile) {
            MediaDeliveryConversions::PROFILE_MAIL => MediaDeliveryConversions::PROFILE_MAIL,
            MediaDeliveryConversions::PROFILE_CUSTOMER_API => MediaDeliveryConversions::PROFILE_CUSTOMER_API,
            default => MediaDeliveryConversions::PROFILE_WEB,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $attributes
     */
    private function renderImg(array $data, array $attributes, ?string $includePictureSizes = null): string
    {
        $imgAttrs = array_merge([
            'src' => $data['img']['src'],
            'alt' => $data['alt'],
            'loading' => $data['img']['loading'],
            'decoding' => $data['img']['decoding'],
        ], $attributes);

        if (! empty($data['img']['srcset'])) {
            $imgAttrs['srcset'] = $data['img']['srcset'];
        }

        if ($includePictureSizes !== null && $includePictureSizes !== '') {
            $imgAttrs['sizes'] = $includePictureSizes;
        } elseif (! empty($data['sizes']) && ! empty($data['img']['srcset'])) {
            $imgAttrs['sizes'] = $data['sizes'];
        }

        if (! empty($data['width'])) {
            $imgAttrs['width'] = $data['width'];
        }

        if (! empty($data['height'])) {
            $imgAttrs['height'] = $data['height'];
        }

        $attrString = collect($imgAttrs)
            ->filter(static fn (mixed $value): bool => $value !== null && $value !== '')
            ->map(static fn (mixed $value, string $key): string => sprintf('%s="%s"', $key, e((string) $value)))
            ->implode(' ');

        return '<img '.$attrString.'>';
    }
}
