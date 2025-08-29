<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Record')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Record\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Record');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
