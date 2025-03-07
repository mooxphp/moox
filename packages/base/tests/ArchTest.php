<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\ThemeBase')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\ThemeBase\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\ThemeBase');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
