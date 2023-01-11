<?php

declare(strict_types=1);

namespace Usetall\Skeleton\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Usetall\Skeleton\Skeleton
 */
class Skeleton extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Usetall\Skeleton\Skeleton::class;
    }
}
