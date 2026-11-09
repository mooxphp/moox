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
            // SVGs: mail gets a rasterized PNG; customer_api is product-raster only.
            $variants[] = $this->conversionRow(
                $media,
                MediaDeliveryConversions::MAIL_PNG,
                'Mail PNG',
                MediaDeliveryConversions::PROFILE_MAIL,
            );

            return $variants;
        }

        foreach (MediaDeliveryConversions::sizes() as $name => $definition) {
            $width = is_array($definition) ? (int) ($definition['width'] ?? 0) : 0;
            // No-upscale: do not list sizes that will never be generated for this file.
            if ($width > 0 && ! MediaDeliveryConversions::canProduceWidth($media, $width)) {
                continue;
            }

            $variants[] = $this->conversionRow(
                $media,
                (string) $name,
                sprintf('%s (%spx)', $name, $width > 0 ? (string) $width : '?'),
                null,
            );
        }

        foreach (MediaDeliveryConversions::applicableResponsiveWidths($media) as $width) {
            foreach (MediaDeliveryConversions::webFormats() as $format) {
                if ($format === 'original') {
                    continue;
                }

                $key = MediaDeliveryConversions::conversionName(MediaDeliveryConversions::PROFILE_WEB, $width, $format);
                $variants[] = $this->conversionRow(
                    $media,
                    $key,
                    sprintf('Web %spx · %s', $width, strtoupper($format)),
                    MediaDeliveryConversions::PROFILE_WEB,
                );
            }
        }

        $variants[] = $this->conversionRow(
            $media,
            MediaDeliveryConversions::MAIL_RASTER,
            'Mail',
            MediaDeliveryConversions::PROFILE_MAIL,
        );

        if (MediaDeliveryConversions::shouldProtectForCustomerApi($media)) {
            $variants[] = $this->conversionRow(
                $media,
                MediaDeliveryConversions::CUSTOMER_API,
                'Customer API',
                MediaDeliveryConversions::PROFILE_CUSTOMER_API,
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
