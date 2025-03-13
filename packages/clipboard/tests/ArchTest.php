<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Skeleton')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Skeleton\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Skeleton');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
