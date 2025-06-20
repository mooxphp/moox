<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Slug')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Slug\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Slug');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
