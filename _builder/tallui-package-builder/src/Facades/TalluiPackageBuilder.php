<?php

declare(strict_types=1);

namespace Usetall\TalluiPackageBuilder\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Usetall\TalluiPackageBuilder\TalluiPackageBuilder
 */
class TalluiPackageBuilder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Usetall\TalluiPackageBuilder\TalluiPackageBuilder::class;
    }
}
