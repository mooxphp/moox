<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Item')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Item\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Item');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
