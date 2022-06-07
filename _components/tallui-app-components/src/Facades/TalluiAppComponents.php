<?php

declare(strict_types=1);

namespace Usetall\TalluiAppComponents\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Usetall\TalluiAppComponents\TalluiAppComponents
 */
class TalluiAppComponents extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'tallui-app-components';
    }
}
