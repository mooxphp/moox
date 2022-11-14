<?php

declare(strict_types=1);

namespace Usetall\TalluiCore\Components;

use Illuminate\View\Component as IlluminateComponent;

abstract class BladeComponent extends IlluminateComponent
{
    /** @var array<mixed> */
    protected static $assets = [];

    /** @return array<mixed> */
    public static function assets(): array
    {
        return static::$assets;
    }
}
