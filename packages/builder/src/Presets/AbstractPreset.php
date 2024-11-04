<?php

declare(strict_types=1);

namespace Moox\Builder\Presets;

abstract class AbstractPreset
{
    protected array $blocks = [];

    abstract protected function initializePreset(): void;

    public function __construct()
    {
        $this->initializePreset();
    }

    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public static function getName(): string
    {
        $parts = explode('\\', static::class);
        $className = end($parts);

        return strtolower(str_replace('Preset', '', $className));
    }
}
