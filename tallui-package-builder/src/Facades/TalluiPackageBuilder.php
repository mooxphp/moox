<?php

namespace Usetall\TalluiPackageBuilder\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Usetall\TalluiPackageBuilder\TalluiPackageBuilder
 */
class TalluiPackageBuilder extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'tallui-package-builder';
    }
}
