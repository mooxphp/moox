<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

final class MediaIntegration
{
    public static function isAvailable(): bool
    {
        return class_exists('Moox\Media\Forms\Components\MediaPicker');
    }
}
