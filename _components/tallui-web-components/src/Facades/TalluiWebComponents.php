<?php

namespace Usetall\TalluiWebComponents\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Usetall\TalluiWebComponents\TalluiWebComponents
 */
class TalluiWebComponents extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'tallui-web-components';
    }
}
