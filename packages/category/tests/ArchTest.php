<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Category')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Category\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Category');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
