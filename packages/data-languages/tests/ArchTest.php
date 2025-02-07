<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\DataLanguages')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\DataLanguages\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\DataLanguages');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
