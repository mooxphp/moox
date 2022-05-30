<?php

namespace Usetall\TalluiCore\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Usetall\TalluiCore\TalluiCore
 */
class TalluiCore extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'tallui-core';
    }
}
