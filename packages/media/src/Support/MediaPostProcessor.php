<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Moox\Media\Models\Media;

final class MediaPostProcessor
{
    public function __construct(
        private readonly SvgSanitizer $svgSanitizer = new SvgSanitizer,
    ) {
    }

    public function process(Media $media): void
    {
        if (MediaDeliveryConversions::isSvg($media)) {
            $path = $media->getPath();
            if (is_string($path) && $path !== '') {
                $this->svgSanitizer->sanitizeFile($path);
            }

            return;
        }

        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            return;
        }

        if ($media->getCustomProperty('dimensions')) {
            return;
        }

        try {
            $path = $media->getPath();
            if (! is_string($path) || $path === '') {
                return;
            }

            $size = @getimagesize($path);
            if ($size === false) {
                return;
            }

            $media->setCustomProperty('dimensions', [
                'width' => (int) $size[0],
                'height' => (int) $size[1],
            ]);
        } catch (\Throwable) {
            //
        }
    }
}
