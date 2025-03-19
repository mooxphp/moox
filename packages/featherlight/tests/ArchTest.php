<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Featherlight')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Featherlight\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Featherlight');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
