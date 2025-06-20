<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Components')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Components\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Components');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
