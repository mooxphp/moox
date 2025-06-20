<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Tag')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Tag\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Tag');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
