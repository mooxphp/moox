<?php

declare(strict_types=1);

namespace Usetall\TalluiIconsSearch\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Usetall\TalluiIconsSearch\TalluiIconsSearch
 */
class TalluiIconsSearch extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Usetall\TalluiIconsSearch\TalluiIconsSearch::class;
    }
}
