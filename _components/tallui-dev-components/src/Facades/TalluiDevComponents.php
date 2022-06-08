<?php

declare(strict_types=1);

namespace Usetall\TalluiDevComponents\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Usetall\TalluiDevComponents\TalluiDevComponents
 */
class TalluiDevComponents extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'tallui-dev-components';
    }
}
