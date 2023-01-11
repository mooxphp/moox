<?php

declare(strict_types=1);

namespace Usetall\TalluiPackageBuilder\Components;

use Livewire\Component;

abstract class LivewireComponent extends Component
{
    /** @var array<mixed> */
    protected static $assets = [];

    /** @return array<mixed> */
    public static function assets(): array
    {
        return static::$assets;
    }
}
