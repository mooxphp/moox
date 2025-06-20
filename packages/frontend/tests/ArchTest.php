<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Frontend')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Frontend\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Frontend');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
