<?php

arch()
    ->expect('Moox\Connect')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Connect\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn('Moox\Connect');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
