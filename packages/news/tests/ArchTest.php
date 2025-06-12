<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\News')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\News\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\News');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
