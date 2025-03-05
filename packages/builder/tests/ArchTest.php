<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Builder')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Builder\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Builder');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
