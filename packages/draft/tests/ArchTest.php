<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Draft')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Draft\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Draft');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
