<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Company')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Company\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Company');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
