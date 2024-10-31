<?php

declare(strict_types=1);

namespace Moox\Builder\Builder;

use Moox\Builder\Builder\Presets\AbstractPreset;
use Moox\Builder\Builder\Presets\FullItemPreset;
use Moox\Builder\Builder\Presets\NestedTaxonomyPreset;
use Moox\Builder\Builder\Presets\PublishableItemPreset;
use Moox\Builder\Builder\Presets\SimpleItemPreset;
use Moox\Builder\Builder\Presets\SimpleTaxonomyPreset;

class PresetRegistry
{
    private static array $presets = [
        'simple-item' => SimpleItemPreset::class,
        'publishable-item' => PublishableItemPreset::class,
        'full-item' => FullItemPreset::class,
        'simple-taxonomy' => SimpleTaxonomyPreset::class,
        'nested-taxonomy' => NestedTaxonomyPreset::class,
    ];

    public static function getPreset(string $name): ?AbstractPreset
    {
        $presetClass = self::$presets[$name] ?? null;

        return $presetClass ? new $presetClass : null;
    }

    public static function getAvailablePresets(): array
    {
        return array_keys(self::$presets);
    }
}
