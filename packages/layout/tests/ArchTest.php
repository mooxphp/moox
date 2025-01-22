<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Layout')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Layout\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Layout');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
