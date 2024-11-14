<?php

declare(strict_types=1);

namespace Moox\Builder;

use Moox\Builder\Presets\AbstractPreset;

class PresetRegistry
{
    public static function getPreset(string $name): ?AbstractPreset
    {
        $presetClass = config('builder.presets')[$name]['class'] ?? null;

        if (! $presetClass) {
            \Log::error('Preset not found:', ['name' => $name]);

            return null;
        }

        try {
            $preset = new $presetClass;

            return $preset;
        } catch (\Throwable $e) {
            \Log::error('Error creating preset:', [
                'name' => $name,
                'class' => $presetClass,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    public static function getAvailablePresets(): array
    {
        return array_keys(config('builder.presets', []));
    }
}
