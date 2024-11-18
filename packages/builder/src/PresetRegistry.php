<?php

declare(strict_types=1);

namespace Moox\Builder;

class PresetRegistry
{
    public static function register(string $name, string $presetClass): void
    {
        config(['builder.presets.'.$name.'.class' => $presetClass]);
    }

    public static function getPreset(string $name): object
    {
        $presetConfig = config('builder.presets.'.$name);

        if (! $presetConfig || ! isset($presetConfig['class'])) {
            throw new \RuntimeException("Preset {$name} not found in configuration");
        }

        $presetClass = $presetConfig['class'];

        return new $presetClass;
    }

    public static function getPresetNames(): array
    {
        return array_keys(config('builder.presets', []));
    }

    public static function getPresetBlocks(string $presetName): array
    {
        $presets = config('builder.presets', []);
        if (! isset($presets[$presetName])) {
            throw new \RuntimeException("Preset {$presetName} not found");
        }

        return $presets[$presetName]['blocks'] ?? [];
    }
}
