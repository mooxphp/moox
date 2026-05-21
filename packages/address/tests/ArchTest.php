<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Address')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Address\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Address');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
