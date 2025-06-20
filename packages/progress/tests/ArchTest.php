<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Progress')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Progress\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Progress');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
