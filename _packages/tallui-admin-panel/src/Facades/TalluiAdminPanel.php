<?php

declare(strict_types=1);

namespace Usetall\TalluiAdminPanel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Usetall\TalluiAdminPanel\TalluiAdminPanel
 */
class TalluiAdminPanel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Usetall\TalluiAdminPanel\TalluiAdminPanel::class;
    }
}
