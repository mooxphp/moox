<?php

declare(strict_types=1);

namespace Moox\Builder;

use Moox\Builder\Presets\AbstractPreset;

class PresetRegistry
{
    public static function getPreset(string $name): ?AbstractPreset
    {
        $presetConfig = config("builder.presets.{$name}");
        if (! $presetConfig || ! isset($presetConfig['class'])) {
            return null;
        }

        $presetClass = $presetConfig['class'];
        if (! class_exists($presetClass)) {
            return null;
        }

        return new $presetClass;
    }

    public static function getAvailablePresets(): array
    {
        return array_keys(config('builder.presets', []));
    }
}
