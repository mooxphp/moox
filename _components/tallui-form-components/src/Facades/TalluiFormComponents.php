<?php

declare(strict_types=1);

namespace Usetall\TalluiFormComponents\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Usetall\TalluiFormComponents\TalluiFormComponents
 */
class TalluiFormComponents extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'tallui-form-components';
    }
}
