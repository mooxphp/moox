<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Contact')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Contact\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Contact');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
