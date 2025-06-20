<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Build')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Build\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Build');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
