<?php

declare(strict_types=1);

namespace Moox\Builder;

use RuntimeException;

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
            throw new RuntimeException(sprintf('Preset %s not found in configuration', $name));
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
        $presetConfig = config('builder.presets.'.$presetName);

        if (! $presetConfig) {
            throw new RuntimeException(sprintf("Preset '%s' not found in configuration", $presetName));
        }

        $presetClass = $presetConfig['class'];

        if (! class_exists($presetClass)) {
            throw new RuntimeException('Preset class not found: '.$presetClass);
        }

        $preset = new $presetClass;
        $blocks = $preset->getBlocks();

        if (empty($blocks)) {
            throw new RuntimeException(
                sprintf("Preset '%s' initialization failed. ", $presetName).
                ('Class: '.$presetClass)
            );
        }

        return $blocks;
    }

    public static function getPresetGenerators(string $presetName): array
    {
        $presetConfig = config('builder.presets.'.$presetName);

        if (! $presetConfig) {
            throw new RuntimeException(sprintf("Preset '%s' not found in configuration", $presetName));
        }

        return $presetConfig['generators'] ?? [];
    }
}
