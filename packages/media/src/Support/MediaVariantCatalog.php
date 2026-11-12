<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Moox\Media\Models\Media;

final class MediaVariantCatalog
{
    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     profile: string|null,
     *     generated: bool,
     *     url: string|null
     * }>
     */
    public function for(Media $media): array
    {
        $variants = [
            [
                'key' => 'original',
                'label' => 'Original',
                'profile' => null,
                'generated' => true,
                'url' => $this->safeUrl($media),
            ],
        ];

        if (MediaDeliveryConversions::isSvg($media)) {
            foreach (MediaDeliveryConversions::enabledProfileNames() as $profile) {
                if (! MediaDeliveryConversions::shouldApplyProfile($media, $profile)) {
                    continue;
                }

                if (MediaDeliveryConversions::profileSvgMode($profile) !== 'png') {
                    continue;
                }

                $variants[] = $this->conversionRow(
                    $media,
                    MediaDeliveryConversions::svgConversionName($profile),
                    sprintf('%s PNG', ucfirst(str_replace('_', ' ', $profile))),
                    $profile,
                );
            }

            return $variants;
        }

        foreach (MediaDeliveryConversions::sizes() as $name => $definition) {
            $width = is_array($definition) ? (int) ($definition['width'] ?? 0) : 0;
            if ($width > 0 && ! MediaDeliveryConversions::canProduceWidth($media, $width)) {
                $originalWidth = MediaDeliveryConversions::originalWidth($media);
                if ($originalWidth === null && $width > 300) {
                    continue;
                }
                if ($originalWidth !== null) {
                    continue;
                }
            }

            $variants[] = $this->conversionRow(
                $media,
                (string) $name,
                sprintf('%s (%spx)', $name, $width > 0 ? (string) $width : '?'),
                null,
            );
        }

        // GIF / skipped MIME: admin sizes only.
        if (! MediaDeliveryConversions::isDeliveryEligible($media)) {
            return $variants;
        }

        foreach (MediaDeliveryConversions::enabledProfileNames() as $profile) {
            if (! MediaDeliveryConversions::shouldApplyProfile($media, $profile)) {
                continue;
            }

            if (MediaDeliveryConversions::profileMode($profile) === MediaDeliveryConversions::MODE_RESPONSIVE) {
                $widths = MediaDeliveryConversions::applicableResponsiveWidths($media, $profile);
                $sameSize = MediaDeliveryConversions::needsSameSizeDelivery($media, $profile);

                foreach ($widths as $width) {
                    foreach (MediaDeliveryConversions::rasterFormatsForProfile($profile) as $format) {
                        $key = MediaDeliveryConversions::conversionName($profile, $width, $format);
                        $label = $sameSize
                            ? sprintf('%s same-size %spx · %s', ucfirst($profile), $width, strtoupper($format))
                            : sprintf('%s %spx · %s', ucfirst($profile), $width, strtoupper($format));

                        $variants[] = $this->conversionRow($media, $key, $label, $profile);
                    }
                }

                continue;
            }

            $variants[] = $this->conversionRow(
                $media,
                MediaDeliveryConversions::singleConversionName($profile),
                ucfirst(str_replace('_', ' ', $profile)),
                $profile,
            );
        }

        return $variants;
    }

    /**
     * @return array{key: string, label: string, profile: string|null, generated: bool, url: string|null}
     */
    private function conversionRow(Media $media, string $key, string $label, ?string $profile): array
    {
        $generated = false;
        $url = null;

        try {
            $generated = $media->hasGeneratedConversion($key);
            if ($generated) {
                $candidate = $media->getUrl($key);
                $url = $candidate !== '' ? $candidate : null;
            }
        } catch (\Throwable) {
            $generated = false;
            $url = null;
        }

        return [
            'key' => $key,
            'label' => $label,
            'profile' => $profile,
            'generated' => $generated,
            'url' => $url,
        ];
    }

    private function safeUrl(Media $media): ?string
    {
        try {
            $url = $media->getUrl();

            return $url !== '' ? $url : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
