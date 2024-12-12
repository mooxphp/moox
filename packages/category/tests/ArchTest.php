<?php

arch()
    ->expect('Moox\Category')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Category\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn('Moox\Category');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
