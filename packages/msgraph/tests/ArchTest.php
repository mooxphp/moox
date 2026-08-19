<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Msgraph')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Msgraph\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Msgraph');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
